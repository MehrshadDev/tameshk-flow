<?php

return [
    'allowed_emails' => array_values(array_filter(array_map(
        fn (string $email) => strtolower(trim($email)),
        explode(',', (string) env('TERMINAL_ALLOWED_EMAILS', ''))
    ))),

    'dangerous_commands' => [
        'migrate:fresh' => 'Drops all database tables and re-runs all migrations from scratch. All existing data will be permanently lost!',
        'migrate:refresh' => 'Rolls back all migrations and re-runs them. Entire database data will be wiped!',
        'migrate:reset' => 'Rolls back all database migrations. All table data will be lost!',
        'migrate:rollback' => 'Rolls back the last batch of database migrations.',
        'db:wipe' => 'Drops all tables, views, and types from the database permanently!',
        'db:seed' => 'Runs database seeders on the current environment.',
        'key:generate' => 'Regenerates APP_KEY. All encrypted credentials and active sessions will become undecryptable!',
        'down' => 'Puts the application into maintenance mode (503 Service Unavailable).',
        'cache:clear' => 'Flushes the entire application cache store including locks and rate limiters.',
        'queue:flush' => 'Deletes all failed queue jobs from the failed_jobs table.',
        'queue:clear' => 'Deletes all pending jobs from the specified queue.',
        'log:clear' => 'Truncates storage/logs/laravel.log and permanently removes existing log entries.',
        'git-backup:restore' => 'Overwrites current database records with the encrypted snapshot from the Git backup repository!',
    ],

    'quick_groups' => [
        [
            'title' => 'Cache & Optimize',
            'icon' => 'bolt',
            'items' => [
                ['label' => 'Clear All Caches', 'command' => 'optimize:clear'],
                ['label' => 'Optimize Production', 'command' => 'optimize'],
                ['label' => 'Cache Config', 'command' => 'config:cache'],
                ['label' => 'Clear Views', 'command' => 'view:clear'],
            ],
        ],
        [
            'title' => 'Database & Cron',
            'icon' => 'database',
            'items' => [
                ['label' => 'Migration Status', 'command' => 'migrate:status'],
                ['label' => 'Run Pending Migrations', 'command' => 'migrate'],
                ['label' => 'Run Workflow Schedules', 'command' => 'workflow:run-scheduled'],
                ['label' => 'Run Laravel Cron', 'command' => 'schedule:run'],
            ],
        ],
        [
            'title' => 'Git Cloud Backup',
            'icon' => 'cloud-upload',
            'items' => [
                ['label' => 'Init Git Repo', 'command' => 'git-backup:init'],
                ['label' => 'Backup Now (Sync)', 'command' => 'git-backup:run'],
                ['label' => 'Backup in Background', 'command' => 'git-backup:run --queue'],
                ['label' => 'Restore from Git', 'command' => 'git-backup:restore'],
            ],
        ],
        [
            'title' => 'Queue Workers',
            'icon' => 'clock',
            'items' => [
                ['label' => 'Process Queue (Once)', 'command' => 'queue:work --stop-when-empty'],
                ['label' => 'List Failed Jobs', 'command' => 'queue:failed'],
                ['label' => 'Retry Failed Jobs', 'command' => 'queue:retry all'],
            ],
        ],
        [
            'title' => 'Files, Logs & Info',
            'icon' => 'file-text',
            'items' => [
                ['label' => 'Tail Laravel Log', 'command' => 'tail -n 60 storage/logs/laravel.log'],
                ['label' => 'List Directory', 'command' => 'ls -la'],
                ['label' => 'Project Tree', 'command' => 'tree -L 2'],
                ['label' => 'Storage Link', 'command' => 'storage:link'],
                ['label' => 'System About', 'command' => 'about'],
                ['label' => 'Disk Usage', 'command' => 'df -h'],
            ],
        ],
    ],
];
