<?php

namespace App\Jobs;

use App\Backup\GitBackupCipher;
use App\Backup\GitBackupObserver;
use App\Backup\PureGitClient;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BackupUserDataToGitJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 240;

    public function __construct()
    {
        $this->afterCommit();
    }

    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('tameshk-git-backup-lock'))
                ->expireAfter(240)
                ->releaseAfter(30),
        ];
    }

    public function handle(GitBackupCipher $crypto, ?PureGitClient $gitClient = null): void
    {
        if (! config('git_backup.enabled', false)) {
            Cache::forget(GitBackupObserver::IMMEDIATE_PENDING_KEY);

            return;
        }

        $gitClient ??= app(PureGitClient::class);

        ignore_user_abort(true);
        @set_time_limit(240);

        $lock = Cache::lock('tameshk_git_backup_mutex', 240);

        if (! $lock->get()) {
            return;
        }

        Cache::forget(GitBackupObserver::IMMEDIATE_DIRTY_KEY);
        Cache::forget(GitBackupObserver::PERIODIC_DIRTY_KEY);

        try {
            $repoPath = (string) config('git_backup.local_repository_path', storage_path('app/git_backups_repo'));
            $branch = (string) config('git_backup.branch', 'main');
            $remotes = (array) config('git_backup.remotes', []);
            $tables = $this->normalizedTables();

            $dataDir = "$repoPath/data";
            File::ensureDirectoryExists($dataDir);

            $globalExcluded = array_merge(
                ['updated_at'],
                (array) config('git_backup.global_excluded_columns', ['remember_token'])
            );

            foreach ($tables as $table => $tableConfig) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $tableDir = "$dataDir/$table";
                File::ensureDirectoryExists($tableDir);

                $pk = (string) ($tableConfig['primary_key'] ?? 'id');
                $excludedColumns = array_unique(array_merge(
                    $globalExcluded,
                    (array) ($tableConfig['excluded_columns'] ?? [])
                ));

                $currentFileNames = [];

                $query = DB::table($table);
                if (Schema::hasColumn($table, $pk)) {
                    $query->orderBy($pk);
                }

                foreach ($query->cursor() as $record) {
                    $recordArray = (array) $record;

                    foreach ($excludedColumns as $col) {
                        unset($recordArray[$col]);
                    }

                    $rawId = $recordArray[$pk]
                        ?? $recordArray['id']
                        ?? $recordArray['public_id']
                        ?? md5((string) json_encode($recordArray));

                    $safeId = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string) $rawId);
                    $fileName = "rec_$safeId.json.enc";
                    $filePath = "$tableDir/$fileName";
                    $currentFileNames[$fileName] = true;

                    $encryptedContent = $crypto->encryptData($table, $safeId, $recordArray);

                    if (! is_file($filePath) || file_get_contents($filePath) !== $encryptedContent) {
                        File::put($filePath, $encryptedContent);
                    }
                }

                if (is_dir($tableDir) && ($handle = opendir($tableDir))) {
                    while (($file = readdir($handle)) !== false) {
                        if ($file !== '.' && $file !== '..' && ! isset($currentFileNames[$file])) {
                            @unlink("$tableDir/$file");
                        }
                    }
                    closedir($handle);
                }
            }

            $activeDirs = array_keys($tables);
            foreach (File::directories($dataDir) as $existingDir) {
                $dirName = basename($existingDir);
                if (! in_array($dirName, $activeDirs, true)) {
                    File::deleteDirectory($existingDir);
                }
            }

            if ($remotes === []) {
                return;
            }

            $treeData = $gitClient->buildTreeObjects($dataDir);
            $rootTreeSha = $treeData['root_tree_sha'];
            $treeObjects = $treeData['objects'];

            $stateFile = "$repoPath/state.json";
            $state = File::exists($stateFile)
                ? (json_decode(File::get($stateFile), true) ?: [])
                : [];

            $botName = (string) config('git_backup.bot_name', 'Tameshk Backup Bot');
            $botEmail = (string) config('git_backup.bot_email', 'backup@tameshkflow.ir');
            $commitMsg = 'Snapshot Backup at ' . now()->setTimezone('Asia/Tehran')->format('Y-m-d H:i:s');

            $successfulRemotes = 0;

            foreach ($remotes as $remoteUrl) {
                $remoteKey = md5($remoteUrl);
                $knownTreeSha = $state['remotes'][$remoteKey]['tree_sha'] ?? null;
                $knownCommitSha = $state['remotes'][$remoteKey]['commit_sha'] ?? null;

                try {
                    $pushResult = $gitClient->pushSnapshot(
                        remoteUrl: $remoteUrl,
                        branch: $branch,
                        rootTreeSha: $rootTreeSha,
                        treeObjects: $treeObjects,
                        botName: $botName,
                        botEmail: $botEmail,
                        commitMessage: $commitMsg,
                        knownTreeSha: $knownTreeSha,
                        knownCommitSha: $knownCommitSha,
                    );

                    $state['remotes'][$remoteKey] = [
                        'tree_sha' => $pushResult['tree_sha'],
                        'commit_sha' => $pushResult['commit_sha'],
                        'updated_at' => now()->toIso8601String(),
                    ];

                    $successfulRemotes++;
                } catch (Throwable $e) {
                    Log::warning('Git backup push failed for remote: ' . $gitClient->maskUrl($remoteUrl), [
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            File::put($stateFile, json_encode($state, JSON_PRETTY_PRINT));

            if ($successfulRemotes === 0) {
                throw new Exception('All configured Git remotes failed to accept push.');
            }
        } catch (Throwable $e) {
            Log::error('BackupUserDataToGitJob Error: ' . $e->getMessage());
            throw $e;
        } finally {
            $lock->release();
            Cache::forget(GitBackupObserver::IMMEDIATE_PENDING_KEY);

            if (Cache::pull(GitBackupObserver::IMMEDIATE_DIRTY_KEY)) {
                GitBackupObserver::triggerImmediate();
            }
        }
    }

    public function failed(Throwable $exception): void
    {
        Cache::forget(GitBackupObserver::IMMEDIATE_PENDING_KEY);
        Log::error('Git Backup Completely Failed: ' . $exception->getMessage());
    }

    private function normalizedTables(): array
    {
        $raw = (array) config('git_backup.tables', []);
        $result = [];

        foreach ($raw as $key => $value) {
            if (is_int($key) && is_string($value)) {
                $result[$value] = ['primary_key' => 'id', 'mode' => 'periodic'];
            } elseif (is_string($key) && is_array($value)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
