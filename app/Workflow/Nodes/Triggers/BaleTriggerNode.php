<?php

namespace App\Workflow\Nodes\Triggers;

use App\Models\Credential;
use App\Models\Node;
use App\Models\Workflow;
use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\WebhookTriggerContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Publishing\TriggerEndpoints;
use App\Workflow\Publishing\WorkflowPublisher;
use App\Workflow\Support\BaleApi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class BaleTriggerNode implements NodeContract, WebhookTriggerContract
{
    private const LISTEN_SECONDS = 60;
    private const POLL_USLEEP = 300_000;

    public static function cacheKey(int $workflowId): string
    {
        return "bale_webhook_test_payload_$workflowId";
    }

    public static function listeningKey(int $workflowId): string
    {
        return "bale_webhook_test_listening_$workflowId";
    }

    public static function definition(): NodeDefinition
    {
        $catalog = self::catalog();

        return new NodeDefinition(
            type: 'trigger.bale',
            name: 'Bale Trigger',
            category: NodeCategory::Trigger,
            description: 'آغاز خودکار ورکفلو با دریافت رویدادهای بازوی بله؛ نوع رویداد از فیلد «اجرا هنگام» انتخاب می‌شود.',
            icon: 'brand:bale',
            group: 'bale',
            inputs: [],
            outputs: ['main'],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'bale',
                    'label'           => 'اعتبارنامه بازوی بله',
                    'required'        => true,
                ],
                'updates' => [
                    'type'     => 'multiselect',
                    'label'    => 'اجرا هنگام (Trigger On)',
                    'required' => true,
                    'default'  => ['message', 'callback_query'],
                    'help'     => 'یک یا چند نوع رویداد بازوی بله را برای شروع خودکار ورکفلو انتخاب کنید.',
                    'options'  => collect($catalog)
                        ->map(fn (array $item, string $key) => ['value' => $key, 'label' => $item[1]])
                        ->values()
                        ->all(),
                ],
            ],
            presets: collect($catalog)
                ->map(fn (array $item, string $key) => [
                    'key'         => $key,
                    'name'        => $item[0],
                    'description' => $item[2],
                    'config'      => ['updates' => [$key]],
                ])
                ->values()
                ->all(),
            color: 'emerald',
        );
    }

    private static function catalog(): array
    {
        return [
            'message'            => ['On message', 'پیام جدید (Message)', 'آغاز خودکار ورکفلو به محض ارسال پیام جدید در پی‌وی، گروه یا کانال.'],
            'edited_message'     => ['On edited message', 'ویرایش پیام (Edited Message)', 'اجرا هنگام ویرایش پیام توسط کاربر.'],
            'callback_query'     => ['On callback query', 'کلیک روی دکمه (Callback Query)', 'اجرا هنگام کلیک کاربر روی دکمه‌های شیشه‌ای بازو.'],
            'pre_checkout_query' => ['On pre checkout query', 'پیش از پرداخت (Pre-Checkout Query)', 'اجرا هنگام تأیید نهایی پرداخت کیف‌پولی پیش از کسر وجه.'],
        ];
    }

    public function allowedUpdates(array $config): array
    {
        $selected = array_values(array_intersect(
            array_keys(self::catalog()),
            (array) ($config['updates'] ?? []),
        ));

        return $selected !== [] ? $selected : ['message'];
    }

    public function supportsUpdate(array $update, array $config): bool
    {
        $type = $this->resolveUpdateType($update);

        return in_array($type, $this->allowedUpdates($config), true);
    }

    public function execute(NodeContext $context): NodeResult
    {
        $input = is_array($context->input) ? $context->input : [];

        if (isset($input['update_id'])) {
            return $this->formatResult($input);
        }

        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('برای تست این مرحله، انتخاب اعتبارنامه بازوی بله الزامی است.');
        }

        $botToken = trim((string) $credential->get('bot_token'));

        if ($botToken === '') {
            throw NodeExecutionException::because('توکن بازوی بله در اعتبارنامه خالی است.');
        }

        $workflow = $context->run->workflow;

        if ($workflow->isPublished() && $workflow->is_active) {
            throw NodeExecutionException::because('این ورکفلو منتشر شده و webhook بله فعال است. برای تست دستی، ابتدا انتشار را لغو کنید.');
        }

        $workflowId = $workflow->id;

        $this->assertCredentialFreeForTest($workflow, $credential->id);

        $allowed = $this->allowedUpdates($context->config);
        $payloadKey = self::cacheKey($workflowId);
        $listenKey = self::listeningKey($workflowId);
        $cancelKey = "cancel_workflow_run_{$context->run->id}";

        $webhookUrl = app(TriggerEndpoints::class)->bale($workflow);
        $secret = WorkflowPublisher::secretFor($workflow);
        $registeredTempWebhook = false;

        Cache::forget($payloadKey);
        Cache::put($listenKey, ['updates' => $allowed], now()->addSeconds(self::LISTEN_SECONDS + 10));

        try {
            $this->registerWebhook($credential, $webhookUrl, $secret, $context->config);
            $registeredTempWebhook = true;

            $context->log('در حال انتظار برای دریافت رویداد تستی از وب‌هوک بله (حداکثر ۶۰ ثانیه)...');

            $deadline = microtime(true) + self::LISTEN_SECONDS;

            while (microtime(true) < $deadline) {
                if (Cache::get($cancelKey)) {
                    throw NodeExecutionException::because('انتظار برای دریافت رویداد متوقف شد.');
                }

                $captured = Cache::pull($payloadKey);

                if (is_array($captured)) {
                    return $this->formatResult($captured);
                }

                usleep(self::POLL_USLEEP);
            }

            throw NodeExecutionException::because('در مدت ۶۰ ثانیه رویدادی دریافت نشد (Timeout). مجدداً روی دکمه تست کلیک کرده و بلافاصله رویداد موردنظر را در بازوی بله ایجاد کنید.');
        } finally {
            Cache::forget($listenKey);

            if ($registeredTempWebhook) {
                $fresh = $workflow->fresh();

                if (! $fresh || ! $fresh->isPublished() || ! $fresh->is_active) {
                    try {
                        $this->unregisterWebhook($credential, $webhookUrl);
                    } catch (Throwable) {
                    }
                }
            }
        }
    }

    public function registerWebhook(Credential $credential, string $url, string $secret, array $config = []): void
    {
        $botToken = trim((string) $credential->get('bot_token'));

        if ($botToken === '') {
            throw NodeExecutionException::because('توکن بازوی بله در اعتبارنامه خالی است.');
        }

        try {
            $response = Http::connectTimeout(15)->timeout(30)->post(BaleApi::url($botToken, 'setWebhook'), [
                'url' => $url,
            ]);
        } catch (Throwable) {
            throw NodeExecutionException::because('عدم امکان ارتباط با سرور بله برای ثبت وب‌هوک. لطفاً چند لحظه بعد دوباره تلاش کنید.');
        }

        if ($response->failed() || ! $response->json('ok')) {
            $description = (string) data_get($response->json(), 'description', $response->body());

            throw NodeExecutionException::because($this->describeWebhookError($description), secrets: [$botToken]);
        }
    }

    public function unregisterWebhook(Credential $credential, string $url = ''): void
    {
        $botToken = trim((string) $credential->get('bot_token'));

        if ($botToken === '') {
            return;
        }

        Http::connectTimeout(10)->timeout(15)->post(BaleApi::url($botToken, 'deleteWebhook'));
    }

    private function assertCredentialFreeForTest(Workflow $workflow, int $credentialId): void
    {
        $taken = Node::query()
            ->where('type', 'trigger.bale')
            ->where(function ($query) use ($credentialId) {
                $query->where('config->credential_id', $credentialId)
                    ->orWhere('config->credential_id', (string) $credentialId);
            })
            ->whereHas('workflow', fn ($query) => $query
                ->whereKeyNot($workflow->id)
                ->whereNotNull('published_at')
                ->where('is_active', true))
            ->exists();

        if ($taken) {
            throw NodeExecutionException::because(
                'این بازوی بله هم‌اکنون در ورکفلوی منتشرشده دیگری فعال است. برای تست دستی، ابتدا آن ورکفلو را متوقف کنید.'
            );
        }
    }

    private function describeWebhookError(string $description): string
    {
        $lower = strtolower($description);

        return match (true) {
            str_contains($lower, 'https') => 'بله فقط آدرس HTTPS معتبر (روی پورت ۴۴۳ یا ۸۸) را برای webhook می‌پذیرد. آدرس عمومی سایت (APP_URL یا WORKFLOW_WEBHOOK_BASE_URL) را بررسی کنید.',
            str_contains($lower, 'resolve') || str_contains($lower, 'host') => 'سرور بله نتوانست دامنه webhook را پیدا کند. روی localhost کار نمی‌کند؛ از یک آدرس عمومی معتبر استفاده کنید.',
            str_contains($lower, 'unauthorized') || str_contains($lower, 'token') => 'توکن بازوی بله نامعتبر است. اعتبارنامه را بررسی کنید.',
            default => "ثبت webhook در بله ناموفق بود: $description",
        };
    }

    private function resolveUpdateType(array $update): string
    {
        foreach (array_keys(self::catalog()) as $known) {
            if (isset($update[$known])) {
                return $known;
            }
        }

        return (string) (collect(array_keys($update))->first(fn ($key) => $key !== 'update_id') ?? 'message');
    }

    private function formatResult(array $update): NodeResult
    {
        $type = $this->resolveUpdateType($update);
        $payload = (array) data_get($update, $type, []);

        [$chatId, $text, $messageId, $threadId] = match ($type) {
            'callback_query' => [
                data_get($payload, 'message.chat.id'),
                data_get($payload, 'data', ''),
                data_get($payload, 'message.message_id'),
                data_get($payload, 'message.message_thread_id'),
            ],
            'pre_checkout_query' => [
                data_get($payload, 'from.id'),
                data_get($payload, 'invoice_payload', ''),
                null,
                null,
            ],
            default => [
                data_get($payload, 'chat.id'),
                data_get($payload, 'text', data_get($payload, 'caption', '')),
                data_get($payload, 'message_id'),
                data_get($payload, 'message_thread_id'),
            ],
        };

        $text = (string) ($text ?? '');
        $fromId = data_get($payload, 'from.id');

        [$messageType, $fileId, $mimeType, $fileName, $duration] = $this->extractMediaInfo($type, $payload);

        return NodeResult::of([
            'update_id'             => data_get($update, 'update_id'),
            'update_type'           => $type,
            'message_type'          => $messageType,
            'message_id'            => $messageId,
            'message_thread_id'     => $threadId,
            'chat_id'               => $chatId,
            'from_id'               => $fromId,
            'text'                  => $text,
            'chatInput'             => $text,
            'file_id'               => $fileId,
            'mime_type'             => $mimeType,
            'file_name'             => $fileName,
            'duration'              => $duration,
            'username'              => data_get($payload, 'from.username', ''),
            'first_name'            => data_get($payload, 'from.first_name', ''),
            'last_name'             => data_get($payload, 'from.last_name', ''),
            'chat_type'             => data_get($payload, 'chat.type', data_get($payload, 'message.chat.type')),
            'callback_query_id'     => $type === 'callback_query' ? data_get($payload, 'id') : null,
            'callback_data'         => $type === 'callback_query' ? $text : null,
            'callback_message_text' => $type === 'callback_query'
                ? (string) data_get($payload, 'message.text', data_get($payload, 'message.caption', ''))
                : null,
            'pre_checkout_query_id' => $type === 'pre_checkout_query' ? data_get($payload, 'id') : null,
            'total_amount'          => $type === 'pre_checkout_query' ? data_get($payload, 'total_amount') : null,
            'currency'              => $type === 'pre_checkout_query' ? data_get($payload, 'currency') : null,
            'contact'               => data_get($payload, 'contact'),
            'location'              => data_get($payload, 'location'),
            'web_app_data'          => data_get($payload, 'web_app_data.data'),
            'raw'                   => $update,
        ]);
    }

    private function extractMediaInfo(string $updateType, array $payload): array
    {
        if ($updateType === 'callback_query') {
            return ['callback_query', null, null, null, null];
        }

        if ($updateType === 'pre_checkout_query') {
            return ['pre_checkout_query', null, null, null, null];
        }

        if (isset($payload['voice']) && is_array($payload['voice'])) {
            return [
                'voice',
                data_get($payload, 'voice.file_id'),
                data_get($payload, 'voice.mime_type', 'audio/ogg'),
                null,
                data_get($payload, 'voice.duration'),
            ];
        }

        if (isset($payload['audio']) && is_array($payload['audio'])) {
            return [
                'audio',
                data_get($payload, 'audio.file_id'),
                data_get($payload, 'audio.mime_type', 'audio/mpeg'),
                data_get($payload, 'audio.file_name'),
                data_get($payload, 'audio.duration'),
            ];
        }

        if (isset($payload['video']) && is_array($payload['video'])) {
            return [
                'video',
                data_get($payload, 'video.file_id'),
                data_get($payload, 'video.mime_type', 'video/mp4'),
                data_get($payload, 'video.file_name'),
                data_get($payload, 'video.duration'),
            ];
        }

        if (isset($payload['animation']) && is_array($payload['animation'])) {
            return [
                'animation',
                data_get($payload, 'animation.file_id'),
                data_get($payload, 'animation.mime_type', 'video/mp4'),
                data_get($payload, 'animation.file_name'),
                data_get($payload, 'animation.duration'),
            ];
        }

        if (isset($payload['document']) && is_array($payload['document'])) {
            return [
                'document',
                data_get($payload, 'document.file_id'),
                data_get($payload, 'document.mime_type', 'application/octet-stream'),
                data_get($payload, 'document.file_name'),
                null,
            ];
        }

        if (isset($payload['photo']) && is_array($payload['photo']) && $payload['photo'] !== []) {
            $largest = end($payload['photo']);

            return [
                'photo',
                is_array($largest) ? ($largest['file_id'] ?? null) : null,
                'image/jpeg',
                null,
                null,
            ];
        }

        if (isset($payload['sticker']) && is_array($payload['sticker'])) {
            return [
                'sticker',
                data_get($payload, 'sticker.file_id'),
                'image/webp',
                null,
                null,
            ];
        }

        if (isset($payload['contact']) && is_array($payload['contact'])) {
            return ['contact', null, null, null, null];
        }

        if (isset($payload['location']) && is_array($payload['location'])) {
            return ['location', null, null, null, null];
        }

        if (isset($payload['text'])) {
            return ['text', null, null, null, null];
        }

        return [$updateType, null, null, null, null];
    }
}
