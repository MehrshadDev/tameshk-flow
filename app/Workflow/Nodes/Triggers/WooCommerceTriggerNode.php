<?php

namespace App\Workflow\Nodes\Triggers;

use App\Models\Credential;
use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\WebhookTriggerContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Publishing\TriggerEndpoints;
use App\Workflow\Publishing\WorkflowPublisher;
use App\Workflow\Support\WooCommerceClient;
use Illuminate\Support\Facades\Cache;
use Throwable;

class WooCommerceTriggerNode implements NodeContract, WebhookTriggerContract
{
    private const LISTEN_SECONDS = 60;
    private const POLL_SECONDS = 3;

    public static function cacheKey(int $workflowId): string
    {
        return "wc_webhook_test_payload_$workflowId";
    }

    public static function listeningKey(int $workflowId): string
    {
        return "wc_webhook_test_listening_$workflowId";
    }

    public static function definition(): NodeDefinition
    {
        $catalog = self::catalog();

        return new NodeDefinition(
            type: 'trigger.woocommerce',
            name: 'WooCommerce Trigger',
            category: NodeCategory::Trigger,
            description: 'آغاز خودکار ورکفلو هنگام ایجاد، ویرایش یا حذف سفارش، محصول، مشتری یا کد تخفیف در ووکامرس.',
            icon: 'brand:woocommerce',
            group: 'woocommerce',
            inputs: [],
            outputs: ['main'],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'woocommerce',
                    'label'           => 'اعتبارنامه فروشگاه ووکامرس',
                    'required'        => true,
                ],
                'event' => [
                    'type'     => 'select',
                    'label'    => 'رویداد (Event)',
                    'required' => true,
                    'default'  => 'order.created',
                    'options'  => collect($catalog)
                        ->map(fn (array $item, string $key) => ['value' => $key, 'label' => $item[1]])
                        ->values()
                        ->all(),
                ],
            ],
            presets: collect($catalog)
                ->map(fn (array $item, string $key) => [
                    'key'         => str_replace('.', '_', $key),
                    'name'        => $item[0],
                    'description' => $item[2],
                    'config'      => ['event' => $key],
                ])
                ->values()
                ->all(),
            color: 'violet',
        );
    }

    public static function catalog(): array
    {
        return [
            'coupon.created'   => ['On coupon created', 'ایجاد کد تخفیف (coupon.created)', 'اجرا به محض ساخت یک کوپن تخفیف جدید در ووکامرس.'],
            'coupon.deleted'   => ['On coupon deleted', 'حذف کد تخفیف (coupon.deleted)', 'اجرا هنگام حذف شدن یک کوپن تخفیف از فروشگاه.'],
            'coupon.updated'   => ['On coupon updated', 'ویرایش کد تخفیف (coupon.updated)', 'اجرا هنگام تغییر یا ویرایش یک کوپن تخفیف در ووکامرس.'],
            'customer.created' => ['On customer created', 'ثبت‌نام مشتری جدید (customer.created)', 'اجرا به محض ایجاد حساب مشتری جدید در فروشگاه.'],
            'customer.deleted' => ['On customer deleted', 'حذف مشتری (customer.deleted)', 'اجرا هنگام حذف شدن حساب یک مشتری در ووکامرس.'],
            'customer.updated' => ['On customer updated', 'ویرایش اطلاعات مشتری (customer.updated)', 'اجرا هنگام بروزرسانی پروفایل یا آدرس مشتری.'],
            'order.created'    => ['On order created', 'ثبت سفارش جدید (order.created)', 'آغاز خودکار ورکفلو به محض ثبت سفارش جدید در فروشگاه ووکامرس.'],
            'order.deleted'    => ['On order deleted', 'حذف سفارش (order.deleted)', 'اجرا هنگام حذف شدن یک سفارش از ووکامرس.'],
            'order.updated'    => ['On order updated', 'تغییر یا ویرایش سفارش (order.updated)', 'اجرا هنگام تغییر وضعیت سفارش (مثلاً پرداخت یا تکمیل شدن) یا ویرایش آن.'],
            'product.created'  => ['On product created', 'ایجاد محصول جدید (product.created)', 'اجرا به محض انتشار یا ایجاد محصول جدید در فروشگاه.'],
            'product.deleted'  => ['On product deleted', 'حذف محصول (product.deleted)', 'اجرا هنگام حذف شدن یک محصول از فروشگاه.'],
            'product.updated'  => ['On product updated', 'ویرایش محصول / تغییر موجودی (product.updated)', 'اجرا هنگام تغییر قیمت، موجودی انبار یا مشخصات یک محصول.'],
        ];
    }

    public function execute(NodeContext $context): NodeResult
    {
        $input = is_array($context->input) ? $context->input : [];
        $topic = (string) ($context->config['event'] ?? 'order.created');

        if (isset($input['id']) || isset($input['_wc_webhook'])) {
            return $this->formatResult($input, $topic);
        }

        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('برای تست این مرحله، انتخاب اعتبارنامه فروشگاه ووکامرس الزامی است.');
        }

        $client = new WooCommerceClient($credential);
        $workflow = $context->run->workflow;
        $workflowId = $workflow->id;

        $payloadKey = self::cacheKey($workflowId);
        $listenKey = self::listeningKey($workflowId);
        $cancelKey = "cancel_workflow_run_{$context->run->id}";

        Cache::forget($payloadKey);
        Cache::put($listenKey, $topic, now()->addSeconds(self::LISTEN_SECONDS + 10));

        $webhookUrl = app(TriggerEndpoints::class)->woocommerce($workflow);
        $registeredTempWebhook = false;

        if (! $workflow->isPublished()) {
            try {
                $this->registerWebhook($credential, $webhookUrl, WorkflowPublisher::secretFor($workflow), $context->config);
                $registeredTempWebhook = true;
            } catch (Throwable) {
            }
        }

        [$resourceEndpoint, $actionType] = $this->parseTopic($topic);
        $baseline = $this->takeSnapshot($client, $resourceEndpoint);

        $context->log("در انتظار رخداد «{$topic}» در ووکامرس (حداکثر ۶۰ ثانیه)...");

        $deadline = microtime(true) + self::LISTEN_SECONDS;

        try {
            while (microtime(true) < $deadline) {
                if (Cache::get($cancelKey)) {
                    throw NodeExecutionException::because('انتظار برای دریافت رویداد ووکامرس متوقف شد.');
                }

                $captured = Cache::pull($payloadKey);
                if (is_array($captured)) {
                    return $this->formatResult($captured, $topic);
                }

                sleep(self::POLL_SECONDS);

                $capturedAfterSleep = Cache::pull($payloadKey);
                if (is_array($capturedAfterSleep)) {
                    return $this->formatResult($capturedAfterSleep, $topic);
                }

                if ($baseline !== null) {
                    $detected = $this->detectChangeViaPolling($client, $resourceEndpoint, $actionType, $baseline);
                    if ($detected !== null) {
                        return $this->formatResult($detected, $topic);
                    }
                }
            }

            throw NodeExecutionException::because(
                "در مدت ۶۰ ثانیه رویداد «{$topic}» در فروشگاه رخ نداد. دوباره روی دکمه تست کلیک کرده و رویداد مورد نظر را در ووکامرس ایجاد کنید."
            );
        } finally {
            Cache::forget($listenKey);

            if ($registeredTempWebhook && ! $workflow->fresh()?->isPublished()) {
                try {
                    $this->unregisterWebhook($credential, $webhookUrl);
                } catch (Throwable) {
                }
            }
        }
    }

    public function registerWebhook(Credential $credential, string $url, string $secret, array $config = []): void
    {
        $topic = (string) ($config['event'] ?? 'order.created');

        if (! array_key_exists($topic, self::catalog())) {
            $topic = 'order.created';
        }

        $client = new WooCommerceClient($credential);
        $client->syncWebhook("Tameshk Flow ($topic)", $topic, $url, $secret);
    }

    public function unregisterWebhook(Credential $credential, string $url = ''): void
    {
        if ($url === '') {
            return;
        }

        $client = new WooCommerceClient($credential);
        $client->removeWebhooksByUrl($url);
    }

    private function parseTopic(string $topic): array
    {
        [$resource, $event] = array_pad(explode('.', $topic, 2), 2, 'created');

        $endpoint = match ($resource) {
            'order'    => 'orders',
            'product'  => 'products',
            'customer' => 'customers',
            'coupon'   => 'coupons',
            default    => 'orders',
        };

        return [$endpoint, $event];
    }

    private function takeSnapshot(WooCommerceClient $client, string $endpoint): ?array
    {
        try {
            $items = $client->get($endpoint, ['per_page' => 20]);
            if (! is_array($items) || ! array_is_list($items)) {
                return null;
            }

            $byId = [];
            $maxId = 0;

            foreach ($items as $item) {
                if (! is_array($item) || ! isset($item['id'])) {
                    continue;
                }
                $id = (int) $item['id'];
                $maxId = max($maxId, $id);
                $byId[$id] = [
                    'hash' => md5(json_encode([
                        $item['status'] ?? null,
                        $item['date_modified_gmt'] ?? ($item['date_modified'] ?? null),
                        $item['total'] ?? ($item['price'] ?? ($item['email'] ?? ($item['amount'] ?? null))),
                        $item['stock_quantity'] ?? null,
                    ])),
                    'item' => $item,
                ];
            }

            return [
                'max_id' => $maxId,
                'by_id'  => $byId,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    private function detectChangeViaPolling(
        WooCommerceClient $client,
        string $endpoint,
        string $actionType,
        array $baseline,
    ): ?array {
        try {
            $currentItems = $client->get($endpoint, ['per_page' => 20]);
            if (! is_array($currentItems) || ! array_is_list($currentItems)) {
                return null;
            }

            $currentIds = [];

            foreach ($currentItems as $item) {
                if (! is_array($item) || ! isset($item['id'])) {
                    continue;
                }

                $id = (int) $item['id'];
                $currentIds[$id] = true;

                if ($actionType === 'created' && $id > $baseline['max_id']) {
                    return $item;
                }

                if ($actionType === 'updated' && isset($baseline['by_id'][$id])) {
                    $newHash = md5(json_encode([
                        $item['status'] ?? null,
                        $item['date_modified_gmt'] ?? ($item['date_modified'] ?? null),
                        $item['total'] ?? ($item['price'] ?? ($item['email'] ?? ($item['amount'] ?? null))),
                        $item['stock_quantity'] ?? null,
                    ]));

                    if ($newHash !== $baseline['by_id'][$id]['hash']) {
                        return $item;
                    }
                }
            }

            if ($actionType === 'deleted') {
                foreach ($baseline['by_id'] as $oldId => $oldEntry) {
                    if (! isset($currentIds[$oldId])) {
                        return [
                            'id'      => $oldId,
                            'deleted' => true,
                            ...$oldEntry['item'],
                        ];
                    }
                }
            }
        } catch (Throwable) {
        }

        return null;
    }

    private function formatResult(array $payload, string $defaultTopic): NodeResult
    {
        $topic = (string) ($payload['_wc_topic'] ?? $defaultTopic);
        unset($payload['_wc_webhook'], $payload['_wc_topic']);

        [$resource, $event] = array_pad(explode('.', $topic, 2), 2, 'created');

        $summaryText = (string) (
            $payload['name']
            ?? $payload['code']
            ?? $payload['email']
            ?? (isset($payload['id'], $payload['total']) ? "Order #{$payload['id']} - {$payload['total']}" : ($payload['id'] ?? ''))
        );

        return NodeResult::of([
            'event'     => $topic,
            'resource'  => $resource,
            'action'    => $event,
            ...$payload,
            'chatInput' => $summaryText,
            'raw'       => $payload,
        ]);
    }
}
