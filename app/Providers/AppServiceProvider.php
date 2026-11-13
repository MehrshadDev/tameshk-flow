<?php

namespace App\Providers;

use App\Backup\GitBackupCipher;
use App\Backup\GitBackupObserver;
use App\Models\Credential;
use App\Models\DiscountCode;
use App\Models\Ticket;
use App\Models\Workflow;
use App\Policies\CredentialPolicy;
use App\Policies\DiscountCodePolicy;
use App\Policies\TicketPolicy;
use App\Policies\WorkflowPolicy;
use App\Subscriptions\SubscriptionManager;
use App\Tickets\TelegramTicketNotifier;
use App\Tickets\TicketManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SubscriptionManager::class);
        $this->app->singleton(TelegramTicketNotifier::class);
        $this->app->singleton(TicketManager::class);
        $this->app->singleton(GitBackupCipher::class);
    }

    public function boot(): void
    {
        Gate::policy(Credential::class, CredentialPolicy::class);
        Gate::policy(Workflow::class, WorkflowPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(DiscountCode::class, DiscountCodePolicy::class);

        $this->registerGitBackupObservers();
    }

    private function registerGitBackupObservers(): void
    {
        if (! config('git_backup.enabled', false)) {
            return;
        }

        $tables = (array) config('git_backup.tables', []);

        foreach ($tables as $config) {
            $modelClass = is_array($config) ? ($config['model'] ?? null) : null;

            if (is_string($modelClass) && is_subclass_of($modelClass, Model::class)) {
                $modelClass::observe(GitBackupObserver::class);
            }
        }
    }
}
