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
use App\Models\User;
use App\Support\UserSessionManager;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerSharedHostingPcntlPolyfill();

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

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                app(UserSessionManager::class)->enforceLimit($event->user, session()->getId());
            }
        });
    }

    private function registerSharedHostingPcntlPolyfill(): void
    {
        if (
            ! function_exists('pcntl_alarm')
            || ! function_exists('pcntl_signal')
            || ! function_exists('pcntl_async_signals')
        ) {
            if (! function_exists('Illuminate\Queue\pcntl_alarm')) {
                eval('
                    namespace Illuminate\Queue;
                    function pcntl_alarm($seconds) { return 0; }
                    function pcntl_signal($signo, $handler, $restart_syscalls = true) { return true; }
                    function pcntl_async_signals($on = null) { return true; }
                ');
            }
        }
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
