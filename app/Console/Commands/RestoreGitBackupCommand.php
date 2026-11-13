<?php

namespace App\Console\Commands;

use App\Backup\GitBackupCipher;
use App\Backup\GitBackupObserver;
use App\Backup\PureGitClient;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class RestoreGitBackupCommand extends Command
{
    protected $signature = 'git-backup:restore {--commit=HEAD : Specific commit hash or reference to restore from}';

    protected $description = 'Decrypt and restore database snapshot from the most up-to-date Git remote';

    public function handle(GitBackupCipher $crypto, PureGitClient $gitClient): int
    {
        $commitRef = trim((string) ($this->option('commit') ?: 'HEAD'));
        $repoPath = (string) config('git_backup.local_repository_path', storage_path('app/git_backups_repo'));
        $branch = (string) config('git_backup.branch', 'main');
        $remotes = (array) config('git_backup.remotes', []);
        $tables = $this->normalizedTables();

        $this->warn("⚠️ Starting data restoration from Git (Target: $commitRef)...");

        File::ensureDirectoryExists("$repoPath/data");

        $bestSnapshot = null;
        $bestRemoteName = null;
        $latestTimestamp = -1;

        if ($remotes !== []) {
            $this->info('🔄 Fetching snapshot from configured remotes over HTTPS...');

            foreach ($remotes as $index => $remoteUrl) {
                $remoteName = 'remote_' . ($index + 1);
                $maskedUrl = $gitClient->maskUrl($remoteUrl);

                try {
                    $snapshot = $gitClient->fetchRemoteSnapshot($remoteUrl, $branch, $commitRef);
                    $commitTime = (int) ($snapshot['timestamp'] ?? 0);

                    if ($commitTime > $latestTimestamp) {
                        $latestTimestamp = $commitTime;
                        $bestSnapshot = $snapshot;
                        $bestRemoteName = $remoteName;
                    }
                } catch (Throwable $e) {
                    $this->warn("⚠️ Could not fetch from [$remoteName] ($maskedUrl): " . $e->getMessage());
                }
            }
        }

        if ($bestSnapshot !== null) {
            $shortSha = substr($bestSnapshot['commit_sha'], 0, 8);
            $humanTime = $latestTimestamp > 0
                ? Carbon::createFromTimestamp($latestTimestamp)->diffForHumans()
                : 'unknown time';

            $this->info("🎯 Selected remote [$bestRemoteName] at commit [$shortSha] ($humanTime)");
            $extractedCount = $gitClient->extractSnapshotToDirectory($bestSnapshot, $repoPath);
            $this->info("📦 Unpacked $extractedCount encrypted record files from Git tree.");
        } elseif (! File::isDirectory("$repoPath/data") || File::allFiles("$repoPath/data") === []) {
            $this->error('❌ Failed to fetch snapshot from any remote and no local backup files exist!');

            return self::FAILURE;
        } else {
            $this->warn('⚠️ Using existing local backup files in ' . "$repoPath/data");
        }

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
                        $this->error("❌ [$table / {$file->getFilename()}]: " . $e->getMessage());
                    }
                }
            }
        } finally {
            Schema::enableForeignKeyConstraints();
            config(['git_backup.enabled' => $wasEnabled]);
            Cache::forget(GitBackupObserver::IMMEDIATE_DIRTY_KEY);
            Cache::forget(GitBackupObserver::PERIODIC_DIRTY_KEY);
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
}
