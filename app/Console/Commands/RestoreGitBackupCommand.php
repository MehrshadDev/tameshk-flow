<?php

namespace App\Console\Commands;

use App\Backup\GitBackupCipher;
use App\Backup\GitBackupObserver;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class RestoreGitBackupCommand extends Command
{
    protected $signature = 'git-backup:restore {--commit=HEAD : Specific commit hash or reference to restore from}';

    protected $description = 'Decrypt and restore database snapshot from the most up-to-date Git remote';

    public function handle(GitBackupCipher $crypto): int
    {
        $commitRef = trim((string) ($this->option('commit') ?: 'HEAD'));
        $repoPath = (string) config('git_backup.local_repository_path', storage_path('app/git_backups_repo'));
        $branch = (string) config('git_backup.branch', 'main');
        $remotes = (array) config('git_backup.remotes', []);
        $tables = $this->normalizedTables();

        $this->warn("⚠️ Starting data restoration from Git (Target: $commitRef)...");

        if (! File::exists("$repoPath/.git")) {
            $this->info('⚙️ Local repository missing. Running initialization...');
            Artisan::call('git-backup:init');
        }

        $lockFile = "$repoPath/.git/index.lock";
        if (File::exists($lockFile)) {
            File::delete($lockFile);
        }

        $this->info('🔄 Fetching latest history from configured remotes...');

        $bestRemote = null;
        $latestTimestamp = 0;

        foreach ($remotes as $index => $remoteUrl) {
            $remoteName = 'remote_' . ($index + 1);
            $maskedUrl = $this->maskUrl($remoteUrl);

            $this->git($repoPath, ['git', 'remote', 'remove', $remoteName]);
            $this->git($repoPath, ['git', 'remote', 'add', $remoteName, $remoteUrl]);

            $fetchResult = $this->git($repoPath, ['git', 'fetch', $remoteName, $branch], 45);

            if ($fetchResult->failed()) {
                $this->warn("⚠️ Could not fetch from [$remoteName] ($maskedUrl). Skipping...");
                continue;
            }

            $logResult = $this->git($repoPath, ['git', 'log', '-1', '--format=%ct', "$remoteName/$branch"]);

            if ($logResult->successful()) {
                $commitTime = (int) trim($logResult->output());

                if ($commitTime > $latestTimestamp) {
                    $latestTimestamp = $commitTime;
                    $bestRemote = $remoteName;
                }
            }
        }

        $hasLocalCommits = $this->git($repoPath, ['git', 'rev-parse', '--verify', 'HEAD'])->successful();
        $targetRef = $commitRef;

        if ($commitRef === 'HEAD') {
            if ($bestRemote !== null) {
                $targetRef = "$bestRemote/$branch";
                $humanTime = Carbon::createFromTimestamp($latestTimestamp)->diffForHumans();
                $this->info("🎯 Selected most up-to-date remote: [$bestRemote] (Last commit: $humanTime)");
            } elseif (! $hasLocalCommits) {
                $this->error('❌ No accessible remote repository or local history found!');

                return self::FAILURE;
            }
        }

        $checkoutResult = $this->git($repoPath, ['git', 'checkout', '-f', $targetRef]);

        if ($checkoutResult->failed()) {
            $this->error("❌ Failed to checkout [$targetRef]: " . trim($checkoutResult->errorOutput()));

            return self::FAILURE;
        }

        $this->info("📌 Checked out snapshot at [$targetRef]");

        $wasEnabled = config('git_backup.enabled');
        config(['git_backup.enabled' => false]);

        Schema::disableForeignKeyConstraints();

        $totalRestored = 0;

        try {
            foreach ($tables as $table => $tableConfig) {
                if (! Schema::hasTable($table)) {
                    $this->warn("⚠️ Table [$table] does not exist in database. Skipping...");
                    continue;
                }

                $folderPath = "$repoPath/data/$table";

                if (! File::exists($folderPath)) {
                    $this->line("ℹ️ No backup files found for table [$table].");
                    continue;
                }

                $files = File::files($folderPath);
                $pk = (string) ($tableConfig['primary_key'] ?? 'id');
                $hasUpdatedAt = Schema::hasColumn($table, 'updated_at');

                $this->info("📥 Restoring table [$table] (" . count($files) . ' records)...');

                foreach ($files as $file) {
                    if ($file->getExtension() !== 'enc') {
                        continue;
                    }

                    try {
                        $fileName = $file->getFilename();
                        if (! preg_match('/^rec_(.+)\.json\.enc$/', $fileName, $matches)) {
                            continue;
                        }

                        $recordId = $matches[1];
                        $encryptedContent = File::get($file->getRealPath());
                        $recordData = $crypto->decryptData($table, $recordId, $encryptedContent);

                        if ($recordData === []) {
                            continue;
                        }

                        $sanitized = $this->sanitizeRecord($table, $recordData, $hasUpdatedAt);
                        $matchKey = $sanitized[$pk] ?? $recordId;

                        DB::table($table)->updateOrInsert(
                            [$pk => $matchKey],
                            $sanitized
                        );

                        $totalRestored++;
                    } catch (Exception $e) {
                        $this->error("❌ [{$table} / {$file->getFilename()}]: " . $e->getMessage());
                    }
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
            config(['git_backup.enabled' => $wasEnabled]);
            Cache::forget(GitBackupObserver::IMMEDIATE_DIRTY_KEY);
            Cache::forget(GitBackupObserver::PERIODIC_DIRTY_KEY);
        }

        if ($bestRemote !== null) {
            $this->git($repoPath, ['git', 'checkout', '-B', $branch, "$bestRemote/$branch"]);
        }

        $this->info("🎉 Restoration completed! Total records restored: $totalRestored");

        return self::SUCCESS;
    }

    private function sanitizeRecord(string $table, array $data, bool $hasUpdatedAt): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $clean[$key] = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                continue;
            }

            if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $value)) {
                try {
                    $value = Carbon::parse($value)->format('Y-m-d H:i:s');
                } catch (Throwable) {
                }
            }

            $clean[$key] = $value;
        }

        if ($hasUpdatedAt && ! array_key_exists('updated_at', $clean)) {
            $clean['updated_at'] = $clean['created_at'] ?? now()->format('Y-m-d H:i:s');
        }

        if ($table === 'users' && ! array_key_exists('password', $clean)) {
            $clean['password'] = bcrypt(Str::random(32));
        }

        return $clean;
    }

    private function normalizedTables(): array
    {
        $raw = (array) config('git_backup.tables', []);
        $result = [];

        foreach ($raw as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $result[$value] = ['primary_key' => 'id'];
            } elseif (is_string($key) && is_array($value)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function git(string $repoPath, array $command, int $timeout = 30)
    {
        return Process::path($repoPath)
            ->timeout($timeout)
            ->env([
                'GIT_TERMINAL_PROMPT' => '0',
                'GIT_ASKPASS' => 'echo',
                'HOME' => $repoPath,
            ])
            ->run($command);
    }

    private function maskUrl(string $url): string
    {
        return (string) preg_replace('#(https?://)([^/@]+@)#i', '$1***@', $url);
    }
}
