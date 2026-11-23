<?php

namespace App\Console\Commands;

use App\Credentials\CredentialResolver;
use App\Jobs\RunWorkflowJob;
use App\Models\Node;
use App\Models\NodeRun;
use App\Models\WorkflowRun;
use App\Subscriptions\SubscriptionManager;
use App\Workflow\Contracts\PollingTriggerContract;
use App\Workflow\Registry\NodeRegistry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Throwable;

class RunScheduledWorkflowsCommand extends Command
{
    private const MAX_POLL_SECONDS = 20;
    private const ORPHAN_RUN_MINUTES = 10;

    protected $signature = 'workflow:run-scheduled';

    protected $description = 'Poll and queue all published workflows with scheduled or polling triggers.';

    public function handle(
        NodeRegistry $registry,
        CredentialResolver $credentials,
        SubscriptionManager $subscriptions,
    ): int {
        $startedAt = microtime(true);

        if ($subscriptions->isSaasEnabled()) {
            $subscriptions->expireOverdueSubscriptions();
        }

        $this->recoverOrphanedRuns();

        $pollingTypes = $registry->pollingTypes();

        if ($pollingTypes === []) {
            return self::SUCCESS;
        }

        $triggers = Node::query()
            ->whereIn('type', $pollingTypes)
            ->whereHas('workflow', fn ($query) => $query
                ->whereNotNull('published_at')
                ->where('is_active', true))
            ->with('workflow.user')
            ->get()
            ->sortBy(function (Node $trigger): array {
                $isExternal = $this->isExternalTrigger($trigger) ? 1 : 0;
                $lastPolledAt = (int) Cache::get($this->lastPolledKey($trigger->id), 0);

                return [$isExternal, $lastPolledAt, $trigger->id];
            })
            ->values();

        $dispatched = 0;

        foreach ($triggers as $trigger) {
            $workflow = $trigger->workflow;

            if (! $workflow || ! $workflow->user) {
                continue;
            }

            if (! $subscriptions->hasRunQuota($workflow->user)) {
                continue;
            }

            if (! $registry->has($trigger->type)) {
                continue;
            }

            $executor = $registry->make($trigger->type);

            if (! $executor instanceof PollingTriggerContract) {
                continue;
            }

            $config = is_array($trigger->config) ? $trigger->config : [];
            $credentialId = (int) ($config['credential_id'] ?? 0);
            $isExternal = $this->isExternalTrigger($trigger);

            if ($isExternal && (microtime(true) - $startedAt) >= self::MAX_POLL_SECONDS) {
                break;
            }

            if ($isExternal && Cache::has($this->backoffKey($trigger->id))) {
                continue;
            }

            $credential = $credentialId > 0
                ? $credentials->find($credentialId, $workflow->user_id)
                : null;

            try {
                $payloads = $executor->poll($workflow, $trigger, $credential);
                $this->recordPollSuccess($trigger->id);
            } catch (Throwable $e) {
                $this->recordPollFailure($trigger->id);
                report($e);
                continue;
            }

            foreach ($payloads as $payload) {
                if (! is_array($payload)) {
                    continue;
                }

                if (! $subscriptions->hasRunQuota($workflow->user)) {
                    break;
                }

                RunWorkflowJob::dispatch($workflow->id, $payload);
                $dispatched++;
            }
        }

        $this->info("Queued $dispatched polling workflow runs.");

        return self::SUCCESS;
    }

    private function isExternalTrigger(Node $trigger): bool
    {
        $config = is_array($trigger->config) ? $trigger->config : [];

        return (int) ($config['credential_id'] ?? 0) > 0 || $trigger->type === 'trigger.rss';
    }

    private function recoverOrphanedRuns(): void
    {
        $cutoff = now()->subMinutes(self::ORPHAN_RUN_MINUTES);
        $errorMessage = 'اجرای ورکفلو به دلیل پایان مهلت زمانی سرور متوقف شد.';

        $orphanedIds = WorkflowRun::query()
            ->where('status', 'running')
            ->whereNull('output')
            ->where('started_at', '<', $cutoff)
            ->pluck('id');

        if ($orphanedIds->isEmpty()) {
            return;
        }

        WorkflowRun::query()
            ->whereIn('id', $orphanedIds)
            ->update([
                'status'      => 'failed',
                'error'       => $errorMessage,
                'finished_at' => now(),
            ]);

        NodeRun::query()
            ->whereIn('workflow_run_id', $orphanedIds)
            ->where('status', 'running')
            ->update([
                'status'      => 'failed',
                'error'       => $errorMessage,
                'finished_at' => now(),
            ]);
    }

    private function recordPollSuccess(int $triggerId): void
    {
        Cache::put($this->lastPolledKey($triggerId), time(), now()->addDay());
        Cache::forget($this->failureCountKey($triggerId));
        Cache::forget($this->backoffKey($triggerId));
    }

    private function recordPollFailure(int $triggerId): void
    {
        Cache::put($this->lastPolledKey($triggerId), time(), now()->addDay());

        $fails = min(6, ((int) Cache::get($this->failureCountKey($triggerId), 0)) + 1);
        $backoffMinutes = min(30, 2 ** ($fails - 1));

        Cache::put($this->failureCountKey($triggerId), $fails, now()->addHours(2));
        Cache::put($this->backoffKey($triggerId), true, now()->addMinutes($backoffMinutes));
    }

    private function lastPolledKey(int $triggerId): string
    {
        return "trigger_last_poll_$triggerId";
    }

    private function failureCountKey(int $triggerId): string
    {
        return "trigger_poll_fails_$triggerId";
    }

    private function backoffKey(int $triggerId): string
    {
        return "trigger_poll_backoff_$triggerId";
    }
}
