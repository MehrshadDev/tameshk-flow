<?php

namespace App\Terminal;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

class TerminalExecutor
{
    private const BLOCKED_DAEMONS = [
        'serve' => 'Command "serve" is disabled on shared hosting as it blocks the web worker process.',
        'pail' => 'Command "pail" is a continuous stream. Use "tail -n 60 storage/logs/laravel.log" instead.',
        'schedule:work' => 'Command "schedule:work" is a daemon. Use "schedule:run" instead.',
        'queue:listen' => 'Command "queue:listen" is a continuous daemon. Use "queue:work --stop-when-empty" instead.',
        'tinker' => 'Interactive REPL "tinker" cannot run over stateless HTTP requests.',
    ];

    public function execute(string $rawCommand, string $relativeCwd = '', bool $confirmed = false, ?string $userEmail = null): array
    {
        $startedAt = microtime(true);
        $cwd = $this->resolveSafeDirectory($relativeCwd);
        $segments = $this->splitChainedCommands($rawCommand);

        if ($segments === []) {
            return $this->response('', $this->relativePath($cwd), 0, $startedAt);
        }

        if (! $confirmed) {
            foreach ($segments as $segment) {
                $danger = $this->detectDanger($segment);

                if ($danger !== null) {
                    return [
                        'status' => 'confirm_required',
                        'command' => $rawCommand,
                        'dangerous_command' => $danger['command'],
                        'warning' => $danger['warning'],
                        'cwd' => $this->relativePath($cwd),
                        'exit_code' => 0,
                        'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                    ];
                }
            }
        }

        Log::info('Web terminal command executed', [
            'user' => $userEmail,
            'command' => $rawCommand,
            'cwd' => $this->relativePath($cwd),
        ]);

        $outputs = [];
        $lastExitCode = 0;

        foreach ($segments as $segment) {
            $result = $this->runSingleSegment($segment, $cwd);
            $cwd = $result['cwd'];
            $lastExitCode = $result['exit_code'];

            if (($result['clear'] ?? false) === true) {
                return [
                    'status' => 'clear',
                    'output' => '',
                    'cwd' => $this->relativePath($cwd),
                    'exit_code' => 0,
                    'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                ];
            }

            if ($result['output'] !== '') {
                $outputs[] = rtrim($result['output']);
            }

            if ($lastExitCode !== 0 && $segment['operator'] === '&&') {
                break;
            }
        }

        return $this->response(implode("\n\n", $outputs), $this->relativePath($cwd), $lastExitCode, $startedAt);
    }

    public function autocompleteCatalog(string $relativeCwd = ''): array
    {
        $shellCommands = [
            'ls', 'ls -la', 'll', 'tree', 'tree -L 2', 'pwd', 'cd', 'cat',
            'tail -n 60 storage/logs/laravel.log', 'head -n 30', 'clear',
            'help', 'whoami', 'php -v', 'php -m', 'df -h', 'du -sh', 'date',
            'uname -a', 'env', 'log:clear',
        ];

        $artisanCommands = [];

        try {
            foreach (Artisan::all() as $name => $command) {
                $artisanCommands[] = [
                    'name' => $name,
                    'description' => $command->getDescription(),
                ];
            }
        } catch (Throwable) {
        }

        usort($artisanCommands, fn (array $a, array $b) => strcmp($a['name'], $b['name']));

        $cwd = $this->resolveSafeDirectory($relativeCwd);
        $entries = [];

        try {
            foreach (scandir($cwd) ?: [] as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }

                $full = $cwd . DIRECTORY_SEPARATOR . $item;
                $entries[] = is_dir($full) ? $item . '/' : $item;
            }
        } catch (Throwable) {
        }

        return [
            'artisan' => $artisanCommands,
            'shell' => $shellCommands,
            'files' => array_slice($entries, 0, 150),
        ];
    }

    private function runSingleSegment(array $segment, string $cwd): array
    {
        $line = trim($segment['command']);

        if ($line === '') {
            return ['output' => '', 'cwd' => $cwd, 'exit_code' => 0];
        }

        $tokens = $this->tokenize($line);
        $first = $tokens[0] ?? '';

        if ($first === 'php' && ($tokens[1] ?? '') === '-v') {
            return [
                'output' => sprintf("PHP %s (cli-web-emulated) (%s)\nZend Engine v%s\nOS: %s", PHP_VERSION, PHP_SAPI, zend_version(), php_uname()),
                'cwd' => $cwd,
                'exit_code' => 0,
            ];
        }

        if ($first === 'php' && ($tokens[1] ?? '') === '-m') {
            $mods = get_loaded_extensions();
            natcasesort($mods);

            return [
                'output' => "[PHP Modules]\n" . implode("\n", $mods),
                'cwd' => $cwd,
                'exit_code' => 0,
            ];
        }

        $normalized = $this->stripArtisanPrefix($line);
        $normTokens = $this->tokenize($normalized);
        $cmd = strtolower($normTokens[0] ?? '');
        $args = array_slice($normTokens, 1);

        $wasExplicitArtisan = $normalized !== $line;

        if (! $wasExplicitArtisan) {
            return match ($cmd) {
                'clear', 'cls' => ['output' => '', 'cwd' => $cwd, 'exit_code' => 0, 'clear' => true],
                'pwd' => ['output' => '/' . ltrim($this->relativePath($cwd), '/'), 'cwd' => $cwd, 'exit_code' => 0],
                'cd' => $this->handleCd($args, $cwd),
                'ls', 'dir' => ['output' => $this->handleLs($args, $cwd, false), 'cwd' => $cwd, 'exit_code' => 0],
                'll' => ['output' => $this->handleLs($args, $cwd, true), 'cwd' => $cwd, 'exit_code' => 0],
                'tree' => ['output' => $this->handleTree($args, $cwd), 'cwd' => $cwd, 'exit_code' => 0],
                'cat' => $this->handleCat($args, $cwd),
                'tail' => $this->handleTailOrHead($args, $cwd, true),
                'head' => $this->handleTailOrHead($args, $cwd, false),
                'whoami' => ['output' => get_current_user() ?: 'www-data', 'cwd' => $cwd, 'exit_code' => 0],
                'uname' => ['output' => php_uname(), 'cwd' => $cwd, 'exit_code' => 0],
                'date' => ['output' => now()->setTimezone('Asia/Tehran')->format('D M d H:i:s T Y'), 'cwd' => $cwd, 'exit_code' => 0],
                'df' => ['output' => $this->handleDiskFree($cwd), 'cwd' => $cwd, 'exit_code' => 0],
                'du' => ['output' => $this->handleDiskUsage($args, $cwd), 'cwd' => $cwd, 'exit_code' => 0],
                'env' => ['output' => $this->handleSafeEnv(), 'cwd' => $cwd, 'exit_code' => 0],
                'log:clear' => $this->handleLogClear($cwd),
                'help' => ['output' => $this->handleHelp(), 'cwd' => $cwd, 'exit_code' => 0],
                default => $this->runArtisan($normalized, $cmd, $args, $cwd),
            };
        }

        if ($cmd === 'log:clear') {
            return $this->handleLogClear($cwd);
        }

        return $this->runArtisan($normalized, $cmd, $args, $cwd);
    }

    private function runArtisan(string $normalizedLine, string $cmd, array $args, string $cwd): array
    {
        if (isset(self::BLOCKED_DAEMONS[$cmd])) {
            return [
                'output' => '[BLOCKED] ' . self::BLOCKED_DAEMONS[$cmd],
                'cwd' => $cwd,
                'exit_code' => 1,
            ];
        }

        if ($cmd === 'queue:work' && ! in_array('--once', $args, true) && ! in_array('--stop-when-empty', $args, true)) {
            $normalizedLine .= ' --stop-when-empty';
        }

        $available = array_keys(Artisan::all());

        if ($cmd !== 'list' && ! in_array($cmd, $available, true)) {
            $suggestions = $this->findSimilarCommands($cmd, $available);
            $hint = $suggestions !== []
                ? "\nDid you mean one of these?\n  - " . implode("\n  - ", $suggestions)
                : "\nType 'help' or 'list' to see available commands.";

            return [
                'output' => "Command \"$cmd\" is not defined." . $hint,
                'cwd' => $cwd,
                'exit_code' => 127,
            ];
        }

        $commandDefinition = Artisan::all()[$cmd] ?? null;

        if ($commandDefinition) {
            $definition = $commandDefinition->getDefinition();

            if ($definition->hasOption('no-interaction') && ! str_contains($normalizedLine, '--no-interaction') && ! str_contains($normalizedLine, '-n')) {
                $normalizedLine .= ' --no-interaction';
            }

            if ($definition->hasOption('force') && ! str_contains($normalizedLine, '--force')) {
                $normalizedLine .= ' --force';
            }
        }

        set_time_limit(180);

        try {
            $input = new StringInput($normalizedLine);
            $input->setInteractive(false);
            $output = new BufferedOutput();

            $exitCode = Artisan::handle($input, $output);
            $text = trim($output->fetch());

            if ($text === '') {
                $text = $exitCode === 0
                    ? "[OK] Command \"$cmd\" executed successfully (0 output)."
                    : "[ERROR] Command \"$cmd\" exited with code $exitCode.";
            }

            return [
                'output' => $text,
                'cwd' => $cwd,
                'exit_code' => (int) $exitCode,
            ];
        } catch (Throwable $e) {
            return [
                'output' => "[EXCEPTION] " . $e->getMessage(),
                'cwd' => $cwd,
                'exit_code' => 1,
            ];
        }
    }

    private function handleCd(array $args, string $cwd): array
    {
        $target = $args[0] ?? '';

        if ($target === '' || $target === '~' || $target === '/') {
            return ['output' => '', 'cwd' => base_path(), 'exit_code' => 0];
        }

        $candidate = str_starts_with($target, '/')
            ? base_path(ltrim($target, '/'))
            : $cwd . DIRECTORY_SEPARATOR . $target;

        $real = realpath($candidate);
        $base = realpath(base_path());

        if ($real === false || $base === false || ! str_starts_with($real, $base)) {
            return [
                'output' => "cd: $target: No such directory or outside project root",
                'cwd' => $cwd,
                'exit_code' => 1,
            ];
        }

        if (! is_dir($real)) {
            return [
                'output' => "cd: $target: Not a directory",
                'cwd' => $cwd,
                'exit_code' => 1,
            ];
        }

        return ['output' => '', 'cwd' => $real, 'exit_code' => 0];
    }

    private function handleLs(array $args, string $cwd, bool $forceLong): string
    {
        $showAll = $forceLong;
        $longFormat = $forceLong;
        $targetPath = null;

        foreach ($args as $arg) {
            if (str_starts_with($arg, '-')) {
                $flags = substr($arg, 1);
                if (str_contains($flags, 'a') || str_contains($flags, 'A')) {
                    $showAll = true;
                }
                if (str_contains($flags, 'l')) {
                    $longFormat = true;
                }
            } else {
                $targetPath = $arg;
            }
        }

        $dir = $targetPath !== null ? $this->resolveTargetInBase($targetPath, $cwd) : $cwd;

        if ($dir === null || ! file_exists($dir)) {
            return "ls: cannot access '$targetPath': No such file or directory";
        }

        if (is_file($dir)) {
            return $this->formatLsLine($dir, basename($dir));
        }

        $items = @scandir($dir);

        if ($items === false) {
            return "ls: cannot open directory: Permission denied";
        }

        $filtered = [];

        foreach ($items as $item) {
            if (! $showAll && str_starts_with($item, '.')) {
                continue;
            }
            $filtered[] = $item;
        }

        usort($filtered, function (string $a, string $b) use ($dir) {
            $aDir = is_dir($dir . DIRECTORY_SEPARATOR . $a);
            $bDir = is_dir($dir . DIRECTORY_SEPARATOR . $b);

            if ($aDir !== $bDir) {
                return $aDir ? -1 : 1;
            }

            return strnatcasecmp($a, $b);
        });

        if (! $longFormat) {
            $names = array_map(function (string $item) use ($dir) {
                $full = $dir . DIRECTORY_SEPARATOR . $item;

                return is_dir($full) ? $item . '/' : $item;
            }, $filtered);

            return implode("    ", $names);
        }

        $lines = ['total ' . count($filtered)];

        foreach ($filtered as $item) {
            $lines[] = $this->formatLsLine($dir . DIRECTORY_SEPARATOR . $item, $item);
        }

        return implode("\n", $lines);
    }

    private function formatLsLine(string $fullPath, string $name): string
    {
        $perms = @fileperms($fullPath) ?: 0;
        $isDir = is_dir($fullPath);
        $isLink = is_link($fullPath);
        $permStr = $this->formatPermissions($perms, $isDir, $isLink);
        $size = $isDir ? 4096 : (@filesize($fullPath) ?: 0);
        $mtime = @filemtime($fullPath) ?: time();
        $dateStr = date('Y-m-d H:i', $mtime);
        $displayName = $isDir && ! in_array($name, ['.', '..'], true) ? $name . '/' : $name;

        return sprintf('%s  %10s  %s  %s', $permStr, $this->humanBytes($size), $dateStr, $displayName);
    }

    private function handleTree(array $args, string $cwd): string
    {
        $maxDepth = 2;
        $targetPath = null;

        for ($i = 0, $count = count($args); $i < $count; $i++) {
            if ($args[$i] === '-L' && isset($args[$i + 1]) && is_numeric($args[$i + 1])) {
                $maxDepth = max(1, min(4, (int) $args[$i + 1]));
                $i++;
            } elseif (! str_starts_with($args[$i], '-')) {
                $targetPath = $args[$i];
            }
        }

        $dir = $targetPath !== null ? $this->resolveTargetInBase($targetPath, $cwd) : $cwd;

        if ($dir === null || ! is_dir($dir)) {
            return "tree: '$targetPath': Directory not found";
        }

        $rootLabel = '/' . ltrim($this->relativePath($dir), '/');
        $lines = [$rootLabel];
        $stats = ['dirs' => 0, 'files' => 0, 'items' => 0];

        $this->buildTreeLines($dir, '', 1, $maxDepth, $lines, $stats);

        $lines[] = '';
        $lines[] = sprintf('%d directories, %d files', $stats['dirs'], $stats['files']);

        return implode("\n", $lines);
    }

    private function buildTreeLines(string $dir, string $prefix, int $depth, int $maxDepth, array &$lines, array &$stats): void
    {
        if ($depth > $maxDepth || $stats['items'] >= 250) {
            return;
        }

        $ignored = ['.', '..', '.git', 'vendor', 'node_modules'];
        $raw = @scandir($dir) ?: [];
        $items = array_values(array_filter($raw, fn ($i) => ! in_array($i, $ignored, true)));

        usort($items, function (string $a, string $b) use ($dir) {
            $aDir = is_dir($dir . DIRECTORY_SEPARATOR . $a);
            $bDir = is_dir($dir . DIRECTORY_SEPARATOR . $b);

            return $aDir === $bDir ? strnatcasecmp($a, $b) : ($aDir ? -1 : 1);
        });

        $count = count($items);

        foreach ($items as $idx => $item) {
            if ($stats['items'] >= 250) {
                $lines[] = $prefix . '└── ... (truncated)';
                return;
            }

            $stats['items']++;
            $isLast = ($idx === $count - 1);
            $connector = $isLast ? '└── ' : '├── ';
            $full = $dir . DIRECTORY_SEPARATOR . $item;
            $isDir = is_dir($full);

            if ($isDir) {
                $stats['dirs']++;
                $lines[] = $prefix . $connector . $item . '/';
                $nextPrefix = $prefix . ($isLast ? '    ' : '│   ');
                $this->buildTreeLines($full, $nextPrefix, $depth + 1, $maxDepth, $lines, $stats);
            } else {
                $stats['files']++;
                $lines[] = $prefix . $connector . $item;
            }
        }
    }

    private function handleCat(array $args, string $cwd): array
    {
        $target = $args[0] ?? '';

        if ($target === '') {
            return ['output' => 'cat: missing file operand', 'cwd' => $cwd, 'exit_code' => 1];
        }

        $file = $this->resolveTargetInBase($target, $cwd);

        if ($file === null || ! is_file($file)) {
            return ['output' => "cat: $target: No such file", 'cwd' => $cwd, 'exit_code' => 1];
        }

        if (basename($file) === '.env') {
            return [
                'output' => 'cat: .env: Permission denied (Protected environment file. Use "env" for safe summary)',
                'cwd' => $cwd,
                'exit_code' => 1,
            ];
        }

        $size = @filesize($file) ?: 0;

        if ($size > 102400) {
            return [
                'output' => "cat: $target: File exceeds 100 KB ({$this->humanBytes($size)}). Use 'tail -n 60 $target' instead.",
                'cwd' => $cwd,
                'exit_code' => 1,
            ];
        }

        return [
            'output' => (string) file_get_contents($file),
            'cwd' => $cwd,
            'exit_code' => 0,
        ];
    }

    private function handleTailOrHead(array $args, string $cwd, bool $isTail): array
    {
        $linesCount = 40;
        $target = null;

        for ($i = 0, $c = count($args); $i < $c; $i++) {
            if ($args[$i] === '-n' && isset($args[$i + 1]) && is_numeric($args[$i + 1])) {
                $linesCount = max(1, min(300, (int) $args[$i + 1]));
                $i++;
            } elseif (preg_match('/^-(\d+)$/', $args[$i], $m)) {
                $linesCount = max(1, min(300, (int) $m[1]));
            } elseif (! str_starts_with($args[$i], '-')) {
                $target = $args[$i];
            }
        }

        $cmdName = $isTail ? 'tail' : 'head';

        if ($target === null) {
            return ['output' => "$cmdName: missing file operand", 'cwd' => $cwd, 'exit_code' => 1];
        }

        $file = $this->resolveTargetInBase($target, $cwd);

        if ($file === null || ! is_file($file)) {
            return ['output' => "$cmdName: cannot open '$target' for reading: No such file", 'cwd' => $cwd, 'exit_code' => 1];
        }

        if (basename($file) === '.env') {
            return ['output' => "$cmdName: .env: Permission denied", 'cwd' => $cwd, 'exit_code' => 1];
        }

        $content = $this->readFileLines($file, $linesCount, $isTail);

        return [
            'output' => $content !== '' ? $content : '(empty file)',
            'cwd' => $cwd,
            'exit_code' => 0,
        ];
    }

    private function readFileLines(string $file, int $linesCount, bool $fromEnd): string
    {
        $size = @filesize($file) ?: 0;

        if ($size === 0) {
            return '';
        }

        $handle = @fopen($file, 'rb');

        if (! $handle) {
            return '';
        }

        if (! $fromEnd) {
            $lines = [];
            while (! feof($handle) && count($lines) < $linesCount) {
                $line = fgets($handle);
                if ($line === false) {
                    break;
                }
                $lines[] = rtrim($line, "\r\n");
            }
            fclose($handle);

            return implode("\n", $lines);
        }

        $readBytes = min($size, 262144);
        fseek($handle, -$readBytes, SEEK_END);
        $buffer = fread($handle, $readBytes);
        fclose($handle);

        if ($buffer === false) {
            return '';
        }

        $allLines = explode("\n", str_replace("\r\n", "\n", trim($buffer, "\r\n")));
        $slice = array_slice($allLines, -$linesCount);

        return implode("\n", $slice);
    }

    private function handleDiskFree(string $cwd): string
    {
        $total = @disk_total_space($cwd) ?: 0;
        $free = @disk_free_space($cwd) ?: 0;
        $used = max(0, $total - $free);
        $percent = $total > 0 ? round(($used / $total) * 100, 1) . '%' : 'N/A';

        return sprintf(
            "Filesystem      Size        Used        Avail       Use%%\n%-14s  %-10s  %-10s  %-10s  %s",
            'shared-disk',
            $this->humanBytes($total),
            $this->humanBytes($used),
            $this->humanBytes($free),
            $percent
        );
    }

    private function handleDiskUsage(array $args, string $cwd): string
    {
        $target = null;
        foreach ($args as $arg) {
            if (! str_starts_with($arg, '-')) {
                $target = $arg;
            }
        }

        $dir = $target !== null ? $this->resolveTargetInBase($target, $cwd) : $cwd;

        if ($dir === null || ! file_exists($dir)) {
            return "du: cannot access '$target': No such file or directory";
        }

        if (is_file($dir)) {
            return $this->humanBytes(@filesize($dir) ?: 0) . "\t" . basename($dir);
        }

        $bytes = 0;
        $count = 0;

        foreach (File::allFiles($dir) as $file) {
            $bytes += $file->getSize();
            if (++$count > 5000) {
                break;
            }
        }

        return $this->humanBytes($bytes) . "\t/" . ltrim($this->relativePath($dir), '/');
    }

    private function handleSafeEnv(): string
    {
        $rows = [
            'APP_NAME' => config('app.name'),
            'APP_ENV' => config('app.env'),
            'APP_DEBUG' => config('app.debug') ? 'true' : 'false',
            'APP_URL' => config('app.url'),
            'APP_TIMEZONE' => config('app.timezone'),
            'DB_CONNECTION' => config('database.default'),
            'CACHE_STORE' => config('cache.default'),
            'QUEUE_CONNECTION' => config('queue.default'),
            'SESSION_DRIVER' => config('session.driver'),
            'SAAS_ENABLED' => config('saas.enabled') ? 'true' : 'false',
        ];

        $lines = [];
        foreach ($rows as $k => $v) {
            $lines[] = sprintf('%-20s = %s', $k, (string) $v);
        }

        return implode("\n", $lines);
    }

    private function handleLogClear(string $cwd): array
    {
        $logFile = storage_path('logs/laravel.log');

        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }

        return [
            'output' => '[OK] storage/logs/laravel.log has been truncated (0 B).',
            'cwd' => $cwd,
            'exit_code' => 0,
        ];
    }

    private function handleHelp(): string
    {
        return implode("\n", [
            '+-------------------------------------------------------------------------+',
            '|                    TAMESHK FLOW - WEB CLI REFERENCE                     |',
            '+-------------------------------------------------------------------------+',
            '',
            '1. Artisan Commands (direct or with prefix):',
            '   optimize:clear                     Clear all cached bootstrap files',
            '   artisan migrate:status             Check database migration status',
            '   php artisan workflow:run-scheduled Run due scheduled workflows',
            '   queue:work --stop-when-empty       Process pending jobs and exit',
            '',
            '2. Emulated Shell Commands (Shared-Hosting Safe):',
            '   ls [-la] | ll [path]               List directory contents & permissions',
            '   tree [-L 1..4] [path]              Display directory tree structure',
            '   cd <dir> | pwd                     Navigate within project root',
            '   cat <file>                         Output file contents (<= 100 KB)',
            '   tail [-n N] <file>                 Output last N lines of a file',
            '   head [-n N] <file>                 Output first N lines of a file',
            '   log:clear                          Truncate storage/logs/laravel.log',
            '   php -v | php -m                    Display PHP version & loaded modules',
            '   df -h  | du -sh [path]             Disk free space & directory size',
            '   env    | whoami | uname -a | date  Safe environment & host info',
            '   clear  | cls                       Clear terminal screen',
            '',
            '3. Chaining Multiple Commands:',
            '   optimize:clear && migrate && config:cache',
        ]);
    }

    private function detectDanger(array $segment): ?array
    {
        $normalized = $this->stripArtisanPrefix($segment['command']);
        $tokens = $this->tokenize($normalized);
        $cmd = strtolower($tokens[0] ?? '');

        $dangerousMap = config('terminal.dangerous_commands', []);

        if (isset($dangerousMap[$cmd])) {
            return [
                'command' => $cmd,
                'warning' => $dangerousMap[$cmd],
            ];
        }

        return null;
    }

    private function stripArtisanPrefix(string $line): string
    {
        $trimmed = trim($line);

        if (preg_match('/^php\s+artisan\s+(.+)$/i', $trimmed, $m)) {
            return trim($m[1]);
        }

        if (preg_match('/^artisan\s+(.+)$/i', $trimmed, $m)) {
            return trim($m[1]);
        }

        if (preg_match('/^(?:php\s+)?artisan$/i', $trimmed)) {
            return 'list';
        }

        return $trimmed;
    }

    private function splitChainedCommands(string $raw): array
    {
        $length = strlen($raw);
        $segments = [];
        $current = '';
        $quote = null;

        for ($i = 0; $i < $length; $i++) {
            $ch = $raw[$i];

            if (($ch === '"' || $ch === "'") && ($i === 0 || $raw[$i - 1] !== '\\')) {
                if ($quote === $ch) {
                    $quote = null;
                } elseif ($quote === null) {
                    $quote = $ch;
                }
                $current .= $ch;
                continue;
            }

            if ($quote === null) {
                if ($ch === '&' && ($raw[$i + 1] ?? '') === '&') {
                    if (trim($current) !== '') {
                        $segments[] = ['command' => trim($current), 'operator' => '&&'];
                    }
                    $current = '';
                    $i++;
                    continue;
                }

                if ($ch === ';') {
                    if (trim($current) !== '') {
                        $segments[] = ['command' => trim($current), 'operator' => ';'];
                    }
                    $current = '';
                    continue;
                }
            }

            $current .= $ch;
        }

        if (trim($current) !== '') {
            $segments[] = ['command' => trim($current), 'operator' => ''];
        }

        return array_slice($segments, 0, 10);
    }

    private function tokenize(string $line): array
    {
        preg_match_all('/"([^"\\\\]*(?:\\\\.[^"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'|(\S+)/u', $line, $matches, PREG_SET_ORDER);

        $tokens = [];
        foreach ($matches as $match) {
            if (isset($match[1]) && $match[1] !== '') {
                $tokens[] = stripcslashes($match[1]);
            } elseif (isset($match[2]) && $match[2] !== '') {
                $tokens[] = stripcslashes($match[2]);
            } elseif (isset($match[3])) {
                $tokens[] = $match[3];
            }
        }

        return $tokens;
    }

    private function resolveSafeDirectory(string $relativeCwd): string
    {
        $base = realpath(base_path()) ?: base_path();
        $clean = trim(str_replace(['\\', '..'], ['/', ''], $relativeCwd), '/');

        if ($clean === '') {
            return $base;
        }

        $candidate = realpath($base . DIRECTORY_SEPARATOR . $clean);

        if ($candidate !== false && is_dir($candidate) && str_starts_with($candidate, $base)) {
            return $candidate;
        }

        return $base;
    }

    private function resolveTargetInBase(string $target, string $cwd): ?string
    {
        $base = realpath(base_path());

        if ($base === false) {
            return null;
        }

        $candidate = str_starts_with($target, '/')
            ? $base . DIRECTORY_SEPARATOR . ltrim($target, '/')
            : $cwd . DIRECTORY_SEPARATOR . $target;

        $real = realpath($candidate);

        if ($real === false || ! str_starts_with($real, $base)) {
            return null;
        }

        return $real;
    }

    private function relativePath(string $fullPath): string
    {
        $base = realpath(base_path()) ?: base_path();
        $normFull = str_replace('\\', '/', $fullPath);
        $normBase = str_replace('\\', '/', $base);

        if ($normFull === $normBase) {
            return '';
        }

        if (str_starts_with($normFull, $normBase . '/')) {
            return substr($normFull, strlen($normBase) + 1);
        }

        return '';
    }

    private function formatPermissions(int $perms, bool $isDir, bool $isLink): string
    {
        $info = $isDir ? 'd' : ($isLink ? 'l' : '-');

        $info .= (($perms & 0x0100) ? 'r' : '-');
        $info .= (($perms & 0x0080) ? 'w' : '-');
        $info .= (($perms & 0x0040) ? 'x' : '-');

        $info .= (($perms & 0x0020) ? 'r' : '-');
        $info .= (($perms & 0x0010) ? 'w' : '-');
        $info .= (($perms & 0x0008) ? 'x' : '-');

        $info .= (($perms & 0x0004) ? 'r' : '-');
        $info .= (($perms & 0x0002) ? 'w' : '-');
        $info .= (($perms & 0x0001) ? 'x' : '-');

        return $info;
    }

    private function humanBytes(int|float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $pow = min($pow, count($units) - 1);
        $value = $bytes / (1024 ** $pow);

        return $pow === 0 ? (int) $value . ' B' : round($value, 1) . ' ' . $units[$pow];
    }

    private function findSimilarCommands(string $input, array $available): array
    {
        $matches = [];

        foreach ($available as $cmd) {
            if (str_contains($cmd, $input) || levenshtein($input, $cmd) <= 3) {
                $matches[] = $cmd;
            }
        }

        return array_slice($matches, 0, 4);
    }

    private function response(string $output, string $cwd, int $exitCode, float $startedAt): array
    {
        return [
            'status' => 'ok',
            'output' => $output,
            'cwd' => $cwd,
            'exit_code' => $exitCode,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ];
    }
}
