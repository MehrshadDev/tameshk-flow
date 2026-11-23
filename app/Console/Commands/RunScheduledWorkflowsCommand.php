<?php

namespace App\Console\Commands;

use App\Credentials\CredentialResolver;
use App\Jobs\RunWorkflowJob;
use App\Models\Node;
use App\Subscriptions\SubscriptionManager;
use App\Workflow\Contracts\PollingTriggerContract;
use App\Workflow\Registry\NodeRegistry;
use Illuminate\Console\Command;
use Throwable;

class RunScheduledWorkflowsCommand extends Command
{
    protected $signature = 'workflow:run-scheduled';

    protected $description = 'Poll and dispatch all published workflows with scheduled or polling triggers.';

    public function handle(
        NodeRegistry $registry,
        CredentialResolver $credentials,
        SubscriptionManager $subscriptions,
    ): int {
        if ($subscriptions->isSaasEnabled()) {
            $subscriptions->expireOverdueSubscriptions();
        }

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
            ->get();

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
            $credential = $credentialId > 0
                ? $credentials->find($credentialId, $workflow->user_id)
                : null;

            try {
                $payloads = $executor->poll($workflow, $trigger, $credential);
            } catch (Throwable $e) {
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

                RunWorkflowJob::dispatchSync($workflow->id, $payload);
                $dispatched++;
            }
        }

        $this->info("Dispatched $dispatched polling workflow runs.");

        return self::SUCCESS;
    }
}
