<?php

namespace App\Http\Controllers;

use App\Jobs\RunWorkflowJob;
use App\Models\Workflow;
use App\Subscriptions\SubscriptionManager;
use App\Workflow\Nodes\Triggers\WooCommerceTriggerNode;
use App\Workflow\Publishing\WorkflowPublisher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WooCommerceWebhookController extends Controller
{
    public function __invoke(Request $request, string $token, SubscriptionManager $subscriptions): JsonResponse
    {
        $workflow = Workflow::query()->with(['user', 'nodes'])->where('webhook_token', $token)->first();

        abort_if(! $workflow, 404);

        $topic = trim((string) $request->header('X-WC-Webhook-Topic', ''));

        if ($topic === '' || $request->has('webhook_id')) {
            return response()->json(['ok' => true, 'status' => 'ping_acknowledged']);
        }

        $rawBody = $request->getContent();
        $signature = trim((string) $request->header('X-WC-Webhook-Signature', ''));
        $expectedSignature = base64_encode(
            hash_hmac('sha256', $rawBody, WorkflowPublisher::secretFor($workflow), true)
        );

        abort_unless($signature !== '' && hash_equals($expectedSignature, $signature), 403);

        $trigger = $workflow->nodes->firstWhere('type', 'trigger.woocommerce');

        if (! $trigger) {
            return response()->json(['ok' => true]);
        }

        $expectedEvent = (string) ($trigger->config['event'] ?? 'order.created');

        if ($topic !== $expectedEvent && $topic !== 'action.woocommerce_' . str_replace('.', '_', $expectedEvent)) {
            return response()->json(['ok' => true]);
        }

        $payload = $request->json()->all();

        if (! is_array($payload) || $payload === []) {
            $payload = $request->all();
        }

        $payload['_wc_webhook'] = true;
        $payload['_wc_topic'] = $topic;

        if (Cache::get(WooCommerceTriggerNode::listeningKey($workflow->id))) {
            Cache::put(WooCommerceTriggerNode::cacheKey($workflow->id), $payload, now()->addMinutes(2));

            return response()->json(['ok' => true, 'mode' => 'test_captured']);
        }

        if (! $workflow->isPublished() || ! $workflow->is_active || ! $workflow->user) {
            return response()->json(['ok' => true]);
        }

        if (! $subscriptions->hasRunQuota($workflow->user)) {
            return response()->json(['ok' => true]);
        }

        $deliveryId = trim((string) $request->header('X-WC-Webhook-Delivery-ID', ''));
        $dedupeKey = $deliveryId !== ''
            ? "wc_delivery_{$workflow->id}_$deliveryId"
            : "wc_delivery_{$workflow->id}_" . md5($rawBody);

        if (! Cache::add($dedupeKey, true, now()->addMinutes(10))) {
            return response()->json(['ok' => true]);
        }

        RunWorkflowJob::dispatchSmart($workflow->id, $payload);

        return response()->json(['ok' => true]);
    }
}
