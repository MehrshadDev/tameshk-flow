<?php

namespace App\Http\Controllers;

use App\Jobs\RunWorkflowJob;
use App\Models\Workflow;
use App\Subscriptions\SubscriptionManager;
use App\Workflow\Nodes\Triggers\BaleTriggerNode;
use App\Workflow\Publishing\WorkflowPublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BaleWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $token,
        string $secret,
        SubscriptionManager $subscriptions,
        BaleTriggerNode $triggerNode,
    ): JsonResponse {
        $workflow = Workflow::query()->with(['user', 'nodes'])->where('webhook_token', $token)->first();

        abort_if(! $workflow, 404);

        abort_unless(hash_equals(WorkflowPublisher::secretFor($workflow), $secret), 403);

        $trigger = $workflow->nodes->firstWhere('type', 'trigger.bale');

        if (! $trigger) {
            return response()->json(['ok' => true]);
        }

        $update = $request->all();
        $updateId = $update['update_id'] ?? null;

        if (! is_numeric($updateId)) {
            return response()->json(['ok' => true]);
        }

        $listening = Cache::get(BaleTriggerNode::listeningKey($workflow->id));

        if (is_array($listening)) {
            $testConfig = ['updates' => $listening['updates'] ?? []];

            if ($triggerNode->supportsUpdate($update, $testConfig)) {
                if (Cache::pull(BaleTriggerNode::listeningKey($workflow->id)) !== null) {
                    Cache::put(BaleTriggerNode::cacheKey($workflow->id), $update, now()->addMinutes(2));
                }
            }

            return response()->json(['ok' => true, 'mode' => 'test_captured']);
        }

        if (! $workflow->isPublished() || ! $workflow->is_active || ! $workflow->user) {
            return response()->json(['ok' => true]);
        }

        $config = is_array($trigger->config) ? $trigger->config : [];

        if (! $triggerNode->supportsUpdate($update, $config)) {
            return response()->json(['ok' => true]);
        }

        if (! $subscriptions->hasRunQuota($workflow->user)) {
            return response()->json(['ok' => true]);
        }

        if (! Cache::add("bale_update_{$workflow->id}_{$updateId}", true, now()->addMinutes(10))) {
            return response()->json(['ok' => true]);
        }

        RunWorkflowJob::dispatchSmart($workflow->id, $update);

        return response()->json(['ok' => true]);
    }
}
