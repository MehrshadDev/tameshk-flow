<?php

namespace App\Console\Commands;

use App\Backup\GitBackupCipher;
use App\Backup\GitBackupObserver;
use App\Jobs\BackupUserDataToGitJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RunGitBackupCommand extends Command
{
    protected $signature = 'git-backup:run
                            {--periodic : Run scheduled periodic check}
                            {--queue : Dispatch backup job to background queue instead of running synchronously}';

    protected $description = 'Run or dispatch encrypted user data backup to Git remotes';

    public function handle(GitBackupCipher $crypto): int
    {
        if (! config('git_backup.enabled', false)) {
            if (! $this->option('periodic')) {
                $this->warn('⚠️ Git backup is disabled (BACKUP_GIT_ENABLED=false).');
            }

            return self::SUCCESS;
        }

        if ($this->option('periodic')) {
            $isDirty = Cache::get(GitBackupObserver::PERIODIC_DIRTY_KEY, false)
                || Cache::get(GitBackupObserver::IMMEDIATE_DIRTY_KEY, false);

            $lastPeriodicScan = Cache::get('git_backup_last_full_scan_at');
            $needsFullScan = $lastPeriodicScan === null || now()->diffInMinutes($lastPeriodicScan) >= 30;

            if (! $isDirty && ! $needsFullScan) {
                $this->line('No pending periodic changes to backup.');

                return self::SUCCESS;
            }

            Cache::put('git_backup_last_full_scan_at', now(), now()->addHours(2));
            BackupUserDataToGitJob::dispatch();
            $this->info('📦 Periodic Git backup job dispatched to queue.');

            return self::SUCCESS;
        }

        if ($this->option('queue')) {
            BackupUserDataToGitJob::dispatch();
            $this->info('📦 Git backup job dispatched to background queue.');

            return self::SUCCESS;
        }

        $this->info('⏳ Running encrypted Git snapshot backup...');

        try {
            $job = new BackupUserDataToGitJob();
            $job->handle($crypto);
            Cache::put('git_backup_last_full_scan_at', now(), now()->addHours(2));

            $this->info('✅ Git backup completed and pushed successfully!');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('❌ Git backup failed: ' . $e->getMessage());

            return self::FAILURE;
        }
    }
}
