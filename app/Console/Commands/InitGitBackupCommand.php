<?php

namespace App\Console\Commands;

use App\Backup\PureGitClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Throwable;

class InitGitBackupCommand extends Command
{
    protected $signature = 'git-backup:init';

    protected $description = 'Initialize local backup directory and verify remote Git repositories over HTTPS';

    public function handle(PureGitClient $gitClient): int
    {
        $this->info('🚀 Initializing Tameshk Git Backup System (Pure-PHP Engine)...');

        $repoPath = (string) config('git_backup.local_repository_path', storage_path('app/git_backups_repo'));
        $branch = (string) config('git_backup.branch', 'main');
        $remotes = (array) config('git_backup.remotes', []);

        if (! File::exists("$repoPath/data")) {
            File::makeDirectory("$repoPath/data", 0755, true);
            $this->line("📁 Created backup storage directory: $repoPath/data");
        }

        if ($remotes === []) {
            $this->warn('⚠️ No Git remotes defined in GIT_REMOTES environment variable.');

            return self::FAILURE;
        }

        $reachableCount = 0;

        foreach ($remotes as $index => $remoteUrl) {
            $remoteName = 'remote_' . ($index + 1);
            $maskedUrl = $gitClient->maskUrl($remoteUrl);

            try {
                $status = $gitClient->verifyRemote($remoteUrl, $branch);
                $headInfo = $status['is_empty'] ? 'empty repository' : 'HEAD: ' . substr($status['head_sha'], 0, 8);
                $this->info("🔗 Connected [$remoteName]: $maskedUrl ($headInfo)");
                $reachableCount++;
            } catch (Throwable $e) {
                $this->error("❌ Failed [$remoteName] ($maskedUrl): " . $e->getMessage());
            }
        }

        if ($reachableCount === 0) {
            $this->error('❌ Could not connect to any configured Git remote. Check your token and repository URL.');

            return self::FAILURE;
        }

        $this->info('🎉 Git backup initialization completed successfully!');

        return self::SUCCESS;
    }
}
