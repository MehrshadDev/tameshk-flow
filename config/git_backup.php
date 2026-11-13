<?php

use App\Models\Credential;
use App\Models\DiscountCode;
use App\Models\Edge;
use App\Models\Node;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Models\Workflow;

return [
    'enabled' => (bool) env('BACKUP_GIT_ENABLED', false),

    'encryption_key' => (string) env('BACKUP_ENCRYPTION_KEY', env('APP_KEY', '')),

    'local_repository_path' => storage_path('app/git_backups_repo'),

    'branch' => (string) env('BACKUP_GIT_BRANCH', 'main'),

    'bot_name' => (string) env('BACKUP_GIT_BOT_NAME', 'Tameshk Backup Bot'),

    'bot_email' => (string) env('BACKUP_GIT_BOT_EMAIL', 'backup@tameshkflow.ir'),

    'immediate_debounce_seconds' => (int) env('BACKUP_GIT_IMMEDIATE_DEBOUNCE', 15),

    'remotes' => array_values(array_filter(
        array_map('trim', explode(',', (string) env('GIT_REMOTES', '')))
    )),

    'global_excluded_columns' => [
        'remember_token',
    ],

    'tables' => [
        'users' => [
            'model' => User::class,
            'mode' => 'immediate',
            'primary_key' => 'id',
            'excluded_columns' => [
                'remember_token',
            ],
            'ignored_trigger_columns' => [
                'name',
                'avatar',
                'runs_used',
                'runs_reset_at',
                'remember_token',
                'updated_at',
            ],
        ],

        'plans' => [
            'model' => Plan::class,
            'mode' => 'immediate',
            'primary_key' => 'id',
            'excluded_columns' => [],
            'ignored_trigger_columns' => [
                'description',
                'features',
                'updated_at',
            ],
        ],

        'discount_codes' => [
            'model' => DiscountCode::class,
            'mode' => 'immediate',
            'primary_key' => 'id',
            'excluded_columns' => [],
            'ignored_trigger_columns' => [
                'updated_at',
            ],
        ],

        'subscriptions' => [
            'model' => Subscription::class,
            'mode' => 'immediate',
            'primary_key' => 'id',
            'excluded_columns' => [],
            'ignored_trigger_columns' => [
                'updated_at',
            ],
        ],

        'payments' => [
            'model' => Payment::class,
            'mode' => 'immediate',
            'primary_key' => 'id',
            'only_trigger_when' => [
                'status' => 'paid',
            ],
            'excluded_columns' => [
                'raw_response',
                'ip_address',
            ],
            'ignored_trigger_columns' => [
                'raw_response',
                'ip_address',
                'updated_at',
            ],
        ],

        'credentials' => [
            'model' => Credential::class,
            'mode' => 'periodic',
            'primary_key' => 'id',
            'excluded_columns' => [
                'last_used_at',
            ],
            'ignored_trigger_columns' => [
                'last_used_at',
                'updated_at',
            ],
        ],

        'workflows' => [
            'model' => Workflow::class,
            'mode' => 'periodic',
            'primary_key' => 'id',
            'excluded_columns' => [],
            'ignored_trigger_columns' => [
                'settings',
                'updated_at',
            ],
        ],

        'nodes' => [
            'model' => Node::class,
            'mode' => 'periodic',
            'primary_key' => 'id',
            'excluded_columns' => [],
            'ignored_trigger_columns' => [
                'position_x',
                'position_y',
                'updated_at',
            ],
        ],

        'edges' => [
            'model' => Edge::class,
            'mode' => 'periodic',
            'primary_key' => 'id',
            'excluded_columns' => [],
            'ignored_trigger_columns' => [
                'updated_at',
            ],
        ],

        'tickets' => [
            'model' => Ticket::class,
            'mode' => 'periodic',
            'primary_key' => 'id',
            'excluded_columns' => [],
            'ignored_trigger_columns' => [
                'last_reply_at',
                'updated_at',
            ],
        ],

        'ticket_messages' => [
            'model' => TicketMessage::class,
            'mode' => 'periodic',
            'primary_key' => 'id',
            'excluded_columns' => [],
            'ignored_trigger_columns' => [
                'updated_at',
            ],
        ],
    ],
];
