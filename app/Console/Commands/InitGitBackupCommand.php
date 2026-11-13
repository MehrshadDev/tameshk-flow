<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class InitGitBackupCommand extends Command
{
    protected $signature = 'git-backup:init';

    protected $description = 'Initialize local Git backup repository and configure remote repositories';

    public function handle(): int
    {
        $this->info('🚀 Initializing Tameshk Git Backup System...');

        $repoPath = (string) config('git_backup.local_repository_path', storage_path('app/git_backups_repo'));
        $branch = (string) config('git_backup.branch', 'main');
        $remotes = (array) config('git_backup.remotes', []);
        $botName = (string) config('git_backup.bot_name', 'Tameshk Backup Bot');
        $botEmail = (string) config('git_backup.bot_email', 'backup@tameshkflow.ir');

        if (! File::exists($repoPath)) {
            File::makeDirectory($repoPath, 0755, true);
            $this->line("📁 Created directory: $repoPath");
        }

        if (! File::exists("$repoPath/.git")) {
            $this->git($repoPath, ['git', 'init']);
            $this->git($repoPath, ['git', 'symbolic-ref', 'HEAD', "refs/heads/$branch"]);
            $this->info("✅ Local Git repository initialized on branch [$branch].");
        }

        $this->git($repoPath, ['git', 'config', 'user.name', $botName]);
        $this->git($repoPath, ['git', 'config', 'user.email', $botEmail]);

        if ($remotes === []) {
            $this->warn('⚠️ No Git remotes defined in GIT_REMOTES environment variable.');

            return self::FAILURE;
        }

        foreach ($remotes as $index => $remoteUrl) {
            $remoteName = 'remote_' . ($index + 1);
            $maskedUrl = $this->maskUrl($remoteUrl);

            $this->git($repoPath, ['git', 'remote', 'remove', $remoteName]);
            $result = $this->git($repoPath, ['git', 'remote', 'add', $remoteName, $remoteUrl]);

            if ($result->successful()) {
                $this->info("🔗 Configured [$remoteName]: $maskedUrl");
            } else {
                $this->error("❌ Failed to configure [$remoteName]: " . trim($result->errorOutput()));
            }
        }

        $this->info('🎉 Git backup initialization completed successfully!');

        return self::SUCCESS;
    }

    private function git(string $repoPath, array $command)
    {
        return Process::path($repoPath)
            ->timeout(30)
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
