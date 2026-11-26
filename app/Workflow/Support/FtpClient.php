<?php

namespace App\Workflow\Support;

use App\Models\Credential;
use App\Workflow\Exceptions\NodeExecutionException;
use Carbon\Carbon;
use CurlHandle;
use Throwable;

final class FtpClient
{
    public const MAX_FILE_BYTES = 10485760;
    private const MAX_LIST_BUFFER_BYTES = 2097152;
    private const MAX_TEXT_PREVIEW_BYTES = 524288;
    private const MAX_RECURSIVE_DEPTH = 5;
    private const MAX_LIST_ITEMS = 500;
    private const MAX_DELETE_ITEMS = 200;
    private const CONNECT_TIMEOUT = 10;
    private const TRANSFER_TIMEOUT = 30;
    private const RESPONSE_TIMEOUT = 20;

    private ?CurlHandle $ch = null;
    private string $protocol;
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private bool $verifySsl;
    private ?string $resolvedIp = null;

    public function __construct(private readonly Credential $credential)
    {
        if (! extension_loaded('curl')) {
            throw NodeExecutionException::because('افزونه cURL روی سرور فعال نیست.');
        }

        if ($this->credential->type !== 'ftp') {
            throw NodeExecutionException::because('نوع اعتبارنامه انتخابی برای FTP معتبر نیست.');
        }

        $data = is_array($this->credential->data) ? $this->credential->data : [];

        $this->protocol = trim((string) ($data['protocol'] ?? 'ftps_explicit'));
        if (! in_array($this->protocol, ['ftp', 'ftps_explicit', 'ftps_implicit'], true)) {
            $this->protocol = 'ftps_explicit';
        }

        $this->host = trim((string) ($data['host'] ?? ''));
        $this->port = (int) ($data['port'] ?? ($this->protocol === 'ftps_implicit' ? 990 : 21));
        $this->username = trim((string) ($data['username'] ?? ''));
        $this->password = (string) ($data['password'] ?? '');
        $this->verifySsl = ((string) ($data['verify_ssl'] ?? '1')) !== '0';

        if ($this->host === '' || $this->username === '' || $this->password === '') {
            throw NodeExecutionException::because('اطلاعات اتصال در اعتبارنامه FTP ناقص است.');
        }

        if ($this->port < 1 || $this->port > 65535) {
            throw NodeExecutionException::because('پورت FTP نامعتبر است.');
        }

        if (preg_match('/[\r\n\0]/', $this->host . $this->username . $this->password)) {
            throw NodeExecutionException::because('کاراکترهای غیرمجاز در اطلاعات اتصال FTP وجود دارد.');
        }

        $this->resolveAndAssertSafeTarget();
    }

    public function __destruct()
    {
        $this->ch = null;
    }

    public function list(string $path = '/', bool $recursive = false): array
    {
        $cleanPath = $this->normalizePath($path, allowRoot: true);
        $items = [];
        $truncated = false;

        $this->collectDirectoryItems($cleanPath, $recursive, 0, $items, $truncated);

        return [
            'path'      => $cleanPath,
            'recursive' => $recursive,
            'count'     => count($items),
            'truncated' => $truncated,
            'items'     => $items,
            'rows'      => $items,
            'first'     => $items[0] ?? null,
        ];
    }

    public function download(string $path): array
    {
        $cleanPath = $this->normalizePath($path, allowRoot: false);
        $url = $this->buildUrl($cleanPath, false);

        $stream = fopen('php://temp/maxmemory:2097152', 'r+b');
        if ($stream === false) {
            throw NodeExecutionException::because('امکان ایجاد حافظه موقت برای دریافت فایل وجود ندارد.');
        }

        $totalBytes = 0;
        $overflowed = false;

        try {
            $ch = $this->prepareHandle($url);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($handle, string $chunk) use ($stream, &$totalBytes, &$overflowed): int {
                $len = strlen($chunk);
                $totalBytes += $len;

                if ($totalBytes > self::MAX_FILE_BYTES) {
                    $overflowed = true;

                    return 0;
                }

                $written = fwrite($stream, $chunk);

                return $written === false ? 0 : $written;
            });

            $ok = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);

            if ($overflowed) {
                $maxMb = (int) (self::MAX_FILE_BYTES / 1048576);
                throw NodeExecutionException::because("حجم فایل درخواستی بیش از سقف مجاز ($maxMb مگابایت) است.");
            }

            if ($ok === false) {
                throw NodeExecutionException::because($this->describeCurlError($errno, $error), secrets: [$this->password]);
            }

            rewind($stream);
            $binary = stream_get_contents($stream);
            $binary = is_string($binary) ? $binary : '';
        } finally {
            fclose($stream);
        }

        $fileName = basename($cleanPath);
        $mimeType = $this->detectMimeType($fileName, $binary);
        $base64 = base64_encode($binary);
        $text = $this->isTextMime($mimeType) && strlen($binary) <= self::MAX_TEXT_PREVIEW_BYTES
            ? $binary
            : null;

        return [
            'success'   => true,
            'path'      => $cleanPath,
            'file_name' => $fileName,
            'size'      => strlen($binary),
            'mime_type' => $mimeType,
            'data_url'  => "data:$mimeType;base64,$base64",
            'base64'    => $base64,
            'text'      => $text,
        ];
    }

    public function upload(string $path, string $binaryContent, bool $createDirectories = true): array
    {
        $cleanPath = $this->normalizePath($path, allowRoot: false);
        $size = strlen($binaryContent);

        if ($size > self::MAX_FILE_BYTES) {
            $maxMb = (int) (self::MAX_FILE_BYTES / 1048576);
            throw NodeExecutionException::because("حجم فایل برای آپلود بیش از سقف مجاز ($maxMb مگابایت) است.");
        }

        $stream = fopen('php://temp/maxmemory:2097152', 'r+b');
        if ($stream === false) {
            throw NodeExecutionException::because('امکان ایجاد حافظه موقت برای ارسال فایل وجود ندارد.');
        }

        try {
            if ($size > 0) {
                fwrite($stream, $binaryContent);
                rewind($stream);
            }

            $ch = $this->prepareHandle($this->buildUrl($cleanPath, false));
            curl_setopt($ch, CURLOPT_UPLOAD, true);
            curl_setopt($ch, CURLOPT_INFILE, $stream);
            curl_setopt($ch, CURLOPT_INFILESIZE, $size);

            if ($createDirectories) {
                curl_setopt($ch, CURLOPT_FTP_CREATE_MISSING_DIRS, CURLFTP_CREATE_DIR_RETRY);
            }

            $ok = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);

            if ($ok === false) {
                throw NodeExecutionException::because($this->describeCurlError($errno, $error), secrets: [$this->password]);
            }
        } finally {
            fclose($stream);
        }

        return [
            'success'   => true,
            'path'      => $cleanPath,
            'file_name' => basename($cleanPath),
            'size'      => $size,
        ];
    }

    public function createFolder(string $path, bool $recursive = true): array
    {
        $cleanPath = $this->normalizePath($path, allowRoot: false);
        $commands = [];

        if ($recursive) {
            foreach ($this->parentPaths($cleanPath, includeSelf: true) as $dir) {
                $commands[] = '*MKD ' . $dir;
            }
        } else {
            $commands[] = 'MKD ' . $cleanPath;
        }

        $this->runQuoteCommands($commands);

        return [
            'success' => true,
            'path'    => $cleanPath,
        ];
    }

    public function rename(string $oldPath, string $newPath, bool $createDirectories = false): array
    {
        $cleanOld = $this->normalizePath($oldPath, allowRoot: false);
        $cleanNew = $this->normalizePath($newPath, allowRoot: false);

        $commands = [];

        if ($createDirectories) {
            foreach ($this->parentPaths($cleanNew, includeSelf: false) as $dir) {
                $commands[] = '*MKD ' . $dir;
            }
        }

        $commands[] = 'RNFR ' . $cleanOld;
        $commands[] = 'RNTO ' . $cleanNew;

        $this->runQuoteCommands($commands);

        return [
            'success'  => true,
            'old_path' => $cleanOld,
            'new_path' => $cleanNew,
        ];
    }

    public function delete(string $path, bool $isFolder = false, bool $recursive = false): array
    {
        $cleanPath = $this->normalizePath($path, allowRoot: false);

        if (! $isFolder) {
            $this->runQuoteCommands(['DELE ' . $cleanPath]);

            return [
                'success'       => true,
                'path'          => $cleanPath,
                'deleted_type'  => 'file',
                'deleted_count' => 1,
            ];
        }

        if (! $recursive) {
            $this->runQuoteCommands(['RMD ' . $cleanPath]);

            return [
                'success'       => true,
                'path'          => $cleanPath,
                'deleted_type'  => 'folder',
                'deleted_count' => 1,
            ];
        }

        $commands = [];
        $counter = 0;

        $this->collectDeleteCommands($cleanPath, 0, $commands, $counter);
        $commands[] = 'RMD ' . $cleanPath;

        foreach (array_chunk($commands, 40) as $batch) {
            $this->runQuoteCommands($batch);
        }

        return [
            'success'       => true,
            'path'          => $cleanPath,
            'deleted_type'  => 'folder',
            'recursive'     => true,
            'deleted_count' => count($commands),
        ];
    }

    public function normalizePath(string $rawPath, bool $allowRoot = false): string
    {
        if (preg_match('/[\x00-\x1F\x7F]/', $rawPath)) {
            throw NodeExecutionException::because('مسیر فایل یا پوشه شامل کاراکترهای غیرمجاز است.');
        }

        $trimmed = trim(str_replace('\\', '/', $rawPath));
        $trimmed = preg_replace('#/+#', '/', $trimmed) ?? $trimmed;

        if ($trimmed === '' || $trimmed === '/' || $trimmed === '.') {
            if ($allowRoot) {
                return '/';
            }

            throw NodeExecutionException::because('مسیر فایل یا پوشه نمی‌تواند خالی یا ریشه اصلی (/) باشد.');
        }

        $isAbsolute = str_starts_with($trimmed, '/');
        $rawSegments = explode('/', trim($trimmed, '/'));
        $cleanSegments = [];

        foreach ($rawSegments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                throw NodeExecutionException::because('استفاده از مسیرهای نسبی ناامن (..) در FTP مجاز نیست.');
            }

            $cleanSegments[] = $segment;
        }

        if ($cleanSegments === []) {
            if ($allowRoot) {
                return '/';
            }

            throw NodeExecutionException::because('مسیر واردشده معتبر نیست.');
        }

        $joined = implode('/', $cleanSegments);

        return $isAbsolute ? '/' . $joined : $joined;
    }

    public function resolveSandboxedPath(string $requestedPath, string $basePath, bool $allowBase = false): string
    {
        $cleanBase = $this->normalizePath($basePath, allowRoot: true);
        $raw = trim(str_replace('\\', '/', $requestedPath));

        if ($raw === '' || $raw === '/' || $raw === '.') {
            if ($allowBase) {
                return $cleanBase;
            }

            throw NodeExecutionException::because('مسیر فایل مشخص نشده است.');
        }

        $cleanRequested = $this->normalizePath($raw, allowRoot: false);

        if ($cleanBase === '/') {
            return str_starts_with($cleanRequested, '/') ? $cleanRequested : '/' . $cleanRequested;
        }

        $baseTrimmed = rtrim($cleanBase, '/');

        if ($cleanRequested === $baseTrimmed || str_starts_with($cleanRequested, $baseTrimmed . '/')) {
            return $cleanRequested;
        }

        return $baseTrimmed . '/' . ltrim($cleanRequested, '/');
    }

    private function collectDirectoryItems(
        string $dirPath,
        bool $recursive,
        int $depth,
        array &$items,
        bool &$truncated,
    ): void {
        if ($truncated) {
            return;
        }

        $entries = $this->listSingleDirectory($dirPath);

        foreach ($entries as $entry) {
            if (count($items) >= self::MAX_LIST_ITEMS) {
                $truncated = true;

                return;
            }

            $items[] = $entry;

            if ($recursive && $entry['type'] === 'directory' && $depth < self::MAX_RECURSIVE_DEPTH) {
                $this->collectDirectoryItems($entry['path'], true, $depth + 1, $items, $truncated);
            }
        }
    }

    private function collectDeleteCommands(
        string $dirPath,
        int $depth,
        array &$commands,
        int &$counter,
    ): void {
        if ($depth > self::MAX_RECURSIVE_DEPTH) {
            throw NodeExecutionException::because('عمق پوشه‌های تو در تو بیش از سقف مجاز (' . self::MAX_RECURSIVE_DEPTH . ') است.');
        }

        $entries = $this->listSingleDirectory($dirPath);

        foreach ($entries as $entry) {
            if (++$counter > self::MAX_DELETE_ITEMS) {
                throw NodeExecutionException::because('تعداد فایل‌ها برای حذف بازگشتی بیش از سقف مجاز (' . self::MAX_DELETE_ITEMS . ' مورد) است.');
            }

            if ($entry['type'] === 'directory') {
                $this->collectDeleteCommands($entry['path'], $depth + 1, $commands, $counter);
                $commands[] = 'RMD ' . $entry['path'];
            } else {
                $commands[] = 'DELE ' . $entry['path'];
            }
        }
    }

    private function listSingleDirectory(string $dirPath): array
    {
        $url = $this->buildUrl($dirPath, true);

        try {
            $raw = $this->fetchDirectoryListing($url, 'MLSD');

            return $this->parseMlsdOutput($raw, $dirPath);
        } catch (NodeExecutionException $e) {
            $raw = $this->fetchDirectoryListing($url, null);

            return $this->parseListOutput($raw, $dirPath);
        }
    }

    private function fetchDirectoryListing(string $url, ?string $customCommand): string
    {
        $ch = $this->prepareHandle($url);

        if ($customCommand !== null) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $customCommand);
        }

        $buffer = '';
        $overflowed = false;

        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($handle, string $chunk) use (&$buffer, &$overflowed): int {
            if ((strlen($buffer) + strlen($chunk)) > self::MAX_LIST_BUFFER_BYTES) {
                $overflowed = true;

                return 0;
            }

            $buffer .= $chunk;

            return strlen($chunk);
        });

        $ok = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        if ($overflowed) {
            throw NodeExecutionException::because('تعداد فایل‌های این پوشه بیش از ظرفیت مجاز خواندن است.');
        }

        if ($ok === false) {
            throw NodeExecutionException::because($this->describeCurlError($errno, $error), secrets: [$this->password]);
        }

        return $buffer;
    }

    private function runQuoteCommands(array $commands): void
    {
        if ($commands === []) {
            return;
        }

        foreach ($commands as $cmd) {
            if (preg_match('/[\r\n\0]/', $cmd)) {
                throw NodeExecutionException::because('دستور FTP شامل کاراکترهای غیرمجاز است.');
            }
        }

        $ch = $this->prepareHandle($this->buildUrl('/', true));
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_QUOTE, $commands);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, static fn ($handle, string $chunk): int => strlen($chunk));

        $ok = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);

        if ($ok === false) {
            throw NodeExecutionException::because($this->describeCurlError($errno, $error), secrets: [$this->password]);
        }
    }

    private function parseMlsdOutput(string $raw, string $basePath): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($raw)) ?: [];
        $items = [];

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            $spacePos = strpos($line, ' ');
            if ($spacePos === false) {
                continue;
            }

            $factsPart = substr($line, 0, $spacePos);
            $name = substr($line, $spacePos + 1);

            if ($name === '' || $name === '.' || $name === '..') {
                continue;
            }

            $facts = [];
            foreach (explode(';', $factsPart) as $pair) {
                $eqPos = strpos($pair, '=');
                if ($eqPos !== false) {
                    $k = strtolower(trim(substr($pair, 0, $eqPos)));
                    $v = trim(substr($pair, $eqPos + 1));
                    $facts[$k] = $v;
                }
            }

            $rawType = strtolower($facts['type'] ?? 'file');
            if ($rawType === 'cdir' || $rawType === 'pdir') {
                continue;
            }

            $type = match (true) {
                $rawType === 'dir' => 'directory',
                str_starts_with($rawType, 'os.unix=symlink') || $rawType === 'symlink' => 'symlink',
                default => 'file',
            };

            $modifyTime = '';
            if (! empty($facts['modify']) && preg_match('/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $facts['modify'], $m)) {
                $modifyTime = sprintf('%s-%s-%sT%s:%s:%sZ', $m[1], $m[2], $m[3], $m[4], $m[5], $m[6]);
            }

            $items[] = [
                'name'        => $name,
                'path'        => $this->joinPath($basePath, $name),
                'type'        => $type,
                'size'        => isset($facts['size']) && is_numeric($facts['size']) ? (int) $facts['size'] : 0,
                'modify_time' => $modifyTime,
                'rights'      => $facts['unix.mode'] ?? ($facts['perm'] ?? ''),
                'owner'       => $facts['unix.owner'] ?? ($facts['unix.uid'] ?? ''),
                'group'       => $facts['unix.group'] ?? ($facts['unix.gid'] ?? ''),
            ];
        }

        return $items;
    }

    private function parseListOutput(string $raw, string $basePath): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($raw)) ?: [];
        $items = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with(strtolower($trimmed), 'total ')) {
                continue;
            }

            if (preg_match(
                '/^([\-dlcbps])([rwxstST\-]{9})\+?\s+\d+\s+(\S+)\s+(\S+)\s+(\d+)\s+([A-Za-z]{3}\s+\d{1,2}\s+(?:\d{2}:\d{2}|\d{4}))\s+(.+)$/',
                $trimmed,
                $m
            )) {
                $rawName = $m[7];
                $typeChar = $m[1];

                if ($typeChar === 'l' && str_contains($rawName, ' -> ')) {
                    $rawName = explode(' -> ', $rawName, 2)[0];
                }

                if ($rawName === '.' || $rawName === '..') {
                    continue;
                }

                $type = match ($typeChar) {
                    'd' => 'directory',
                    'l' => 'symlink',
                    default => 'file',
                };

                $modifyTime = $m[6];
                try {
                    $modifyTime = Carbon::parse($m[6])->toIso8601String();
                } catch (Throwable) {
                }

                $items[] = [
                    'name'        => $rawName,
                    'path'        => $this->joinPath($basePath, $rawName),
                    'type'        => $type,
                    'size'        => (int) $m[5],
                    'modify_time' => $modifyTime,
                    'rights'      => $m[2],
                    'owner'       => $m[3],
                    'group'       => $m[4],
                ];

                continue;
            }

            if (preg_match(
                '/^(\d{2}-\d{2}-\d{2,4}\s+\d{2}:\d{2}(?:AM|PM)?)\s+(<DIR>|\d+)\s+(.+)$/i',
                $trimmed,
                $m
            )) {
                $rawName = trim($m[3]);
                if ($rawName === '.' || $rawName === '..') {
                    continue;
                }

                $isDir = strtoupper($m[2]) === '<DIR>';

                $items[] = [
                    'name'        => $rawName,
                    'path'        => $this->joinPath($basePath, $rawName),
                    'type'        => $isDir ? 'directory' : 'file',
                    'size'        => $isDir ? 0 : (int) $m[2],
                    'modify_time' => $m[1],
                    'rights'      => '',
                    'owner'       => '',
                    'group'       => '',
                ];
            }
        }

        return $items;
    }

    private function prepareHandle(string $url): CurlHandle
    {
        if ($this->ch === null) {
            $handle = curl_init();
            if ($handle === false) {
                throw NodeExecutionException::because('امکان راه‌اندازی اتصال cURL وجود ندارد.');
            }
            $this->ch = $handle;
        } else {
            curl_reset($this->ch);
        }

        $ch = $this->ch;

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERNAME, $this->username);
        curl_setopt($ch, CURLOPT_PASSWORD, $this->password);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::CONNECT_TIMEOUT);
        curl_setopt($ch, CURLOPT_TIMEOUT, self::TRANSFER_TIMEOUT);
        curl_setopt($ch, CURLOPT_FTP_RESPONSE_TIMEOUT, self::RESPONSE_TIMEOUT);
        curl_setopt($ch, CURLOPT_FTP_SKIP_PASV_IP, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
        curl_setopt($ch, CURLOPT_NOSIGNAL, true);

        if (defined('CURLOPT_PROTOCOLS_STR')) {
            curl_setopt($ch, CURLOPT_PROTOCOLS_STR, 'ftp,ftps');
        } elseif (defined('CURLOPT_PROTOCOLS')) {
            curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_FTP | CURLPROTO_FTPS);
        }

        if ($this->resolvedIp !== null && ! filter_var($this->host, FILTER_VALIDATE_IP)) {
            $formattedIp = str_contains($this->resolvedIp, ':') ? "[$this->resolvedIp]" : $this->resolvedIp;
            curl_setopt($ch, CURLOPT_RESOLVE, ["$this->host:$this->port:$formattedIp"]);
        }

        if ($this->protocol === 'ftps_explicit' || $this->protocol === 'ftps_implicit') {
            curl_setopt($ch, CURLOPT_USE_SSL, CURLUSESSL_ALL);
            curl_setopt($ch, CURLOPT_FTPSSLAUTH, CURLFTPAUTH_TLS);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySsl);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verifySsl ? 2 : 0);
        }

        return $ch;
    }

    private function buildUrl(string $path, bool $isDirectory): string
    {
        $scheme = $this->protocol === 'ftps_implicit' ? 'ftps' : 'ftp';
        $urlHost = filter_var($this->host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)
            ? "[$this->host]"
            : $this->host;

        $base = "$scheme://$urlHost:$this->port";

        if ($path === '' || $path === '/') {
            return $base . '/';
        }

        $isAbsolute = str_starts_with($path, '/');
        $segments = array_map('rawurlencode', explode('/', ltrim($path, '/')));
        $encoded = implode('/', $segments);

        $prefix = $isAbsolute ? '/%2F' : '/';
        $suffix = $isDirectory ? '/' : '';

        return $base . $prefix . $encoded . $suffix;
    }

    private function joinPath(string $basePath, string $name): string
    {
        if ($basePath === '/' || $basePath === '') {
            return '/' . ltrim($name, '/');
        }

        return rtrim($basePath, '/') . '/' . ltrim($name, '/');
    }

    private function parentPaths(string $path, bool $includeSelf): array
    {
        $isAbsolute = str_starts_with($path, '/');
        $segments = array_values(array_filter(explode('/', trim($path, '/')), fn (string $s) => $s !== ''));

        if (! $includeSelf) {
            array_pop($segments);
        }

        $paths = [];
        $current = '';

        foreach ($segments as $segment) {
            $current = $current === ''
                ? ($isAbsolute ? '/' . $segment : $segment)
                : $current . '/' . $segment;
            $paths[] = $current;
        }

        return $paths;
    }

    private function resolveAndAssertSafeTarget(): void
    {
        if (! config('workflow.http_block_private', true)) {
            return;
        }

        $ips = $this->resolveHostToIps($this->host);

        if ($ips === []) {
            throw NodeExecutionException::because('آدرس سرور FTP قابل شناسایی یا دسترسی نیست.');
        }

        foreach ($ips as $ip) {
            if ($this->isPrivateIp($ip)) {
                throw NodeExecutionException::because(
                    config('app.debug')
                        ? "اتصال به آدرس‌های داخلی یا رزرو‌شده مجاز نیست. ($this->host → $ip)"
                        : 'اتصال به آدرس‌های داخلی یا رزرو‌شده مجاز نیست.'
                );
            }
        }

        $ipv4 = array_values(array_filter($ips, fn (string $ip) => filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false));
        $this->resolvedIp = $ipv4[0] ?? $ips[0];
    }

    private function resolveHostToIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = [];

        $ipv4 = @gethostbynamel($host);
        if (is_array($ipv4)) {
            $ips = array_merge($ips, $ipv4);
        }

        $records = @dns_get_record($host, DNS_AAAA);
        if (is_array($records)) {
            foreach ($records as $record) {
                if (isset($record['ipv6'])) {
                    $ips[] = $record['ipv6'];
                }
            }
        }

        return array_values(array_unique($ips));
    }

    private function isPrivateIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = @inet_pton($ip);
            if ($packed === false || strlen($packed) !== 16) {
                return true;
            }

            if ($ip === '::1' || $ip === '::') {
                return true;
            }

            if (substr($packed, 0, 12) === "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\xff\xff") {
                $mappedIpv4 = inet_ntop(substr($packed, 12, 4));

                return $mappedIpv4 === false || $this->isPrivateIp($mappedIpv4);
            }

            $first = ord($packed[0]);
            $second = ord($packed[1]);

            if ($first === 0xfe && ($second & 0xc0) === 0x80) {
                return true;
            }

            if (($first & 0xfe) === 0xfc || $first === 0xff) {
                return true;
            }

            return false;
        }

        if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }

        $long = ip2long($ip);
        if ($long === false) {
            return true;
        }

        $ranges = [
            ['10.0.0.0', '10.255.255.255'],
            ['172.16.0.0', '172.31.255.255'],
            ['192.168.0.0', '192.168.255.255'],
            ['127.0.0.0', '127.255.255.255'],
            ['169.254.0.0', '169.254.255.255'],
            ['0.0.0.0', '0.255.255.255'],
            ['100.64.0.0', '100.127.255.255'],
            ['192.0.0.0', '192.0.0.255'],
            ['192.0.2.0', '192.0.2.255'],
            ['198.18.0.0', '198.19.255.255'],
            ['198.51.100.0', '198.51.100.255'],
            ['203.0.113.0', '203.0.113.255'],
            ['224.0.0.0', '255.255.255.255'],
        ];

        foreach ($ranges as [$min, $max]) {
            if ($long >= ip2long($min) && $long <= ip2long($max)) {
                return true;
            }
        }

        return false;
    }

    private function detectMimeType(string $fileName, string $binary): string
    {
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $byExt = match ($ext) {
            'txt', 'log', 'env', 'ini' => 'text/plain',
            'csv'                      => 'text/csv',
            'json'                     => 'application/json',
            'xml'                      => 'application/xml',
            'html', 'htm'              => 'text/html',
            'css'                      => 'text/css',
            'js', 'mjs'                => 'application/javascript',
            'md', 'markdown'           => 'text/markdown',
            'yaml', 'yml'              => 'application/x-yaml',
            'sql'                      => 'application/sql',
            'pdf'                      => 'application/pdf',
            'zip'                      => 'application/zip',
            'gz', 'gzip'               => 'application/gzip',
            'jpg', 'jpeg'              => 'image/jpeg',
            'png'                      => 'image/png',
            'webp'                     => 'image/webp',
            'gif'                      => 'image/gif',
            'svg'                      => 'image/svg+xml',
            'mp3'                      => 'audio/mpeg',
            'ogg', 'oga', 'opus'       => 'audio/ogg',
            'wav'                      => 'audio/wav',
            'mp4'                      => 'video/mp4',
            'webm'                     => 'video/webm',
            default                    => null,
        };

        if ($byExt !== null) {
            return $byExt;
        }

        if (str_starts_with($binary, '%PDF-')) {
            return 'application/pdf';
        }

        if (str_starts_with($binary, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }

        if (str_starts_with($binary, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }

        if (str_starts_with($binary, "PK\x03\x04")) {
            return 'application/zip';
        }

        return 'application/octet-stream';
    }

    private function isTextMime(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'text/')
            || in_array($mimeType, [
                'application/json',
                'application/xml',
                'application/javascript',
                'application/x-yaml',
                'application/sql',
                'image/svg+xml',
            ], true);
    }

    private function describeCurlError(int $errno, string $rawError): string
    {
        $clean = trim(strip_tags($rawError));

        return match ($errno) {
            6 => 'دامنه سرور FTP یافت نشد. آدرس Host را بررسی کنید.',
            7 => 'امکان برقراری اتصال به سرور FTP وجود ندارد. آدرس، پورت و فایروال سرور را بررسی کنید.',
            9 => 'دسترسی به مسیر مورد نظر در سرور FTP رد شد (Permission Denied).',
            19, 78 => 'فایل یا پوشه مورد نظر در سرور FTP یافت نشد.',
            21 => 'اجرای دستور FTP روی سرور با خطا مواجه شد. از وجود مسیر، مجوز دسترسی یا خالی بودن پوشه مطمئن شوید.',
            28 => 'مهلت زمانی ارتباط با سرور FTP به پایان رسید (Timeout).',
            35, 51, 60, 64 => 'خطا در اتصال امن SSL/TLS به سرور FTP. پروتکل اتصال یا گزینه «اعتبارسنجی گواهی SSL» را در اعتبارنامه بررسی کنید.',
            67 => 'خطای احراز هویت FTP: نام کاربری یا رمز عبور اشتباه است.',
            default => 'خطای ارتباط با سرور FTP: ' . mb_substr($clean !== '' ? $clean : "کد $errno", 0, 300),
        };
    }
}
