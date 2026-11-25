<?php

namespace App\Workflow\Nodes\Ai;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\Contracts\ToolContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\HandleType;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\BaleApi;
use App\Workflow\Support\BaleFormatter;
use Illuminate\Support\Facades\Http;
use Throwable;

class BaleToolNode implements NodeContract, ToolContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'ai.tool.bale',
            name: 'Bale Tool',
            category: NodeCategory::Ai,
            description: 'ابزار ارسال پیام، تصویر، ویدیو، فایل و مدیریت چت در پیام‌رسان بله توسط ایجنت هوش مصنوعی.',
            icon: 'brand:bale',
            group: 'tools',
            inputs: [],
            outputs: [HandleType::Tool->value],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'bale',
                    'label'           => 'اعتبارنامه بازوی بله',
                    'required'        => true,
                ],
                'tool_name' => [
                    'type'        => 'text',
                    'label'       => 'نام ابزار (انگلیسی)',
                    'default'     => 'bale_bot',
                    'required'    => true,
                    'placeholder' => 'bale_bot',
                ],
                'tool_description' => [
                    'type'     => 'textarea',
                    'label'    => 'توضیحات عملکرد ابزار برای مدل',
                    'default'  => 'ارسال پیام متنی، تصویر، ویدیو، سند یا پین و حذف پیام در چت، گروه یا کانال پیام‌رسان بله.',
                    'required' => true,
                ],
                'operation' => [
                    'type'     => 'select',
                    'label'    => 'عملیات مجاز ابزار',
                    'options'  => [
                        ['value' => 'auto', 'label' => 'همه عملیات‌ها (تشخیص خودکار توسط ایجنت)'],
                        ['value' => 'send_message', 'label' => 'فقط ارسال پیام متنی (Send Message)'],
                        ['value' => 'send_photo', 'label' => 'فقط ارسال تصویر (Send Photo)'],
                        ['value' => 'send_document', 'label' => 'فقط ارسال فایل و سند (Send Document)'],
                        ['value' => 'send_video', 'label' => 'فقط ارسال ویدیو (Send Video)'],
                    ],
                    'default'  => 'auto',
                    'required' => true,
                ],
                'default_chat_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه چت پیش‌فرض (Chat ID - اختیاری)',
                    'required'    => false,
                    'placeholder' => 'مثلاً {{ input.chat_id }} یا @channel_name',
                    'help'        => 'در صورت پر بودن، اگر ایجنت chat_id ارسال نکند از این شناسه استفاده خواهد شد.',
                ],
                'format_mode' => [
                    'type'     => 'select',
                    'label'    => 'قالب‌بندی متن (Text Formatting)',
                    'options'  => [
                        ['value' => 'auto', 'label' => 'تبدیل خودکار Markdown به قالب بله'],
                        ['value' => 'none', 'label' => 'ارسال بدون تغییر (متن خام بله)'],
                    ],
                    'default'  => 'auto',
                    'advanced' => true,
                ],
                'use_business_api' => [
                    'type'     => 'select',
                    'label'    => 'استفاده از API کسب‌وکاری بله (Business API)',
                    'options'  => [
                        ['value' => '0', 'label' => 'خیر (API عادی بازو)'],
                        ['value' => '1', 'label' => 'بله (برای بازوهای دارای اشتراک کسب‌وکاری)'],
                    ],
                    'default'  => '0',
                    'advanced' => true,
                ],
            ],
            color: 'emerald',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        return NodeResult::of($context->config, HandleType::Tool->value);
    }

    public function toolName(array $config): string
    {
        return trim((string) ($config['tool_name'] ?? 'bale_bot'));
    }

    public function toolDescription(array $config): string
    {
        return trim((string) ($config['tool_description'] ?? 'ارسال پیام و رسانه در پیام‌رسان بله'));
    }

    public function toolParameters(array $config): array
    {
        $operation = (string) ($config['operation'] ?? 'auto');
        $hasDefaultChat = trim((string) ($config['default_chat_id'] ?? '')) !== '';

        $properties = [];
        $required = [];

        if ($operation === 'auto') {
            $properties['action'] = [
                'type'        => 'STRING',
                'description' => 'نوع عملیات بله: "send_message" (ارسال پیام متنی)، "send_photo" (ارسال تصویر)، "send_document" (ارسال فایل)، "send_video" (ارسال ویدیو)، "pin_message" (پین کردن پیام)، "delete_message" (حذف پیام)',
            ];
            $required[] = 'action';
        }

        $properties['chat_id'] = [
            'type'        => 'STRING',
            'description' => $hasDefaultChat
                ? 'شناسه چت یا کانال بله (اختیاری؛ در صورت خالی بودن به چت پیش‌فرض ارسال می‌شود)'
                : 'شناسه عددی چت یا نام کاربری کانال در بله (مثلاً 123456789 یا @channel)',
        ];

        if (! $hasDefaultChat) {
            $required[] = 'chat_id';
        }

        if (in_array($operation, ['auto', 'send_message'], true)) {
            $properties['text'] = [
                'type'        => 'STRING',
                'description' => 'متن پیام برای ارسال در بله',
            ];

            if ($operation === 'send_message') {
                $required[] = 'text';
            }
        }

        if (in_array($operation, ['auto', 'send_photo', 'send_document', 'send_video'], true)) {
            $properties['media_url'] = [
                'type'        => 'STRING',
                'description' => 'آدرس اینترنتی (URL) یا file_id تصویر، فایل یا ویدیو برای ارسال',
            ];
            $properties['caption'] = [
                'type'        => 'STRING',
                'description' => 'توضیحات (Caption) زیر رسانه (اختیاری)',
            ];

            if (in_array($operation, ['send_photo', 'send_document', 'send_video'], true)) {
                $required[] = 'media_url';
            }
        }

        if ($operation === 'auto') {
            $properties['message_id'] = [
                'type'        => 'INTEGER',
                'description' => 'شناسه پیام (Message ID) برای عملیات پین کردن یا حذف پیام',
            ];
        }

        return [
            'type'       => 'OBJECT',
            'properties' => $properties,
            'required'   => $required,
        ];
    }

    public function executeTool(NodeContext $context, array $arguments): mixed
    {
        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('اعتبارنامه بازوی بله برای ابزار Bale انتخاب نشده است.');
        }

        $botToken = trim((string) $credential->get('bot_token'));

        if ($botToken === '') {
            throw NodeExecutionException::because('توکن بازوی بله در اعتبارنامه خالی است.');
        }

        $configuredOp = (string) ($context->config['operation'] ?? 'auto');
        $action = $configuredOp === 'auto'
            ? strtolower(trim((string) ($arguments['action'] ?? 'send_message')))
            : $configuredOp;

        $chatId = trim((string) ($arguments['chat_id'] ?? ''));
        if ($chatId === '') {
            $chatId = trim((string) ($context->config['default_chat_id'] ?? ''));
        }

        if ($chatId === '') {
            throw NodeExecutionException::because('شناسه چت (chat_id) مشخص نشده است.');
        }

        $formatMode = trim((string) ($context->config['format_mode'] ?? 'auto')) ?: 'auto';
        $business = ((string) ($context->config['use_business_api'] ?? '0')) === '1';

        return match ($action) {
            'send_message' => $this->sendTextChunks(
                $botToken,
                $chatId,
                $this->requireArg($arguments, 'text', 'متن پیام (text) الزامی است.'),
                $formatMode,
                $business,
            ),
            'send_photo' => $this->sendMedia(
                $botToken,
                'sendPhoto',
                'photo',
                $chatId,
                $this->requireArg($arguments, 'media_url', 'آدرس تصویر (media_url) الزامی است.'),
                (string) ($arguments['caption'] ?? ''),
                $formatMode,
                $business,
            ),
            'send_document' => $this->sendMedia(
                $botToken,
                'sendDocument',
                'document',
                $chatId,
                $this->requireArg($arguments, 'media_url', 'آدرس فایل (media_url) الزامی است.'),
                (string) ($arguments['caption'] ?? ''),
                $formatMode,
                $business,
            ),
            'send_video' => $this->sendMedia(
                $botToken,
                'sendVideo',
                'video',
                $chatId,
                $this->requireArg($arguments, 'media_url', 'آدرس ویدیو (media_url) الزامی است.'),
                (string) ($arguments['caption'] ?? ''),
                $formatMode,
                $business,
            ),
            'pin_message' => $this->send($botToken, 'pinChatMessage', [
                'chat_id'    => $chatId,
                'message_id' => $this->requireIntArg($arguments, 'message_id', 'شناسه پیام (message_id) الزامی است.'),
            ]),
            'delete_message' => $this->send($botToken, 'deleteMessage', [
                'chat_id'    => $chatId,
                'message_id' => $this->requireIntArg($arguments, 'message_id', 'شناسه پیام (message_id) الزامی است.'),
            ]),
            default => ['error' => "عملیات ناشناخته بله: $action"],
        };
    }

    private function sendTextChunks(
        string $botToken,
        string $chatId,
        string $rawText,
        string $formatMode,
        bool $business,
    ): array {
        $prepared = BaleFormatter::prepare($rawText, $formatMode);
        $chunks = BaleFormatter::split($prepared);
        $lastResponse = [];

        foreach ($chunks as $chunk) {
            $lastResponse = $this->send($botToken, 'sendMessage', [
                'chat_id' => $chatId,
                'text'    => $chunk,
            ], $business);
        }

        return $lastResponse;
    }

    private function sendMedia(
        string $botToken,
        string $method,
        string $field,
        string $chatId,
        string $mediaUrl,
        string $rawCaption,
        string $formatMode,
        bool $business,
    ): array {
        $safeCaption = BaleFormatter::truncate(
            BaleFormatter::prepare($rawCaption, $formatMode),
            BaleFormatter::MAX_CAPTION_LENGTH
        );

        $payload = [
            'chat_id' => $chatId,
            $field    => $mediaUrl,
        ];

        if ($safeCaption !== '') {
            $payload['caption'] = $safeCaption;
        }

        return $this->send($botToken, $method, $payload, $business);
    }

    private function requireArg(array $arguments, string $key, string $message): string
    {
        $val = trim((string) ($arguments[$key] ?? ''));

        if ($val === '') {
            throw NodeExecutionException::because($message);
        }

        return $val;
    }

    private function requireIntArg(array $arguments, string $key, string $message): int
    {
        $raw = $this->requireArg($arguments, $key, $message);

        if (! preg_match('/^-?\d+$/', $raw)) {
            throw NodeExecutionException::because("مقدار پارامتر $key باید عددی باشد.");
        }

        return (int) $raw;
    }

    private function send(string $botToken, string $method, array $payload, bool $business = false): array
    {
        try {
            $response = Http::timeout(30)->post(BaleApi::url($botToken, $method, $business), $payload);
        } catch (Throwable $e) {
            throw NodeExecutionException::because("خطا در ارتباط با بله: {$e->getMessage()}");
        }

        if ($response->failed() || $response->json('ok') === false) {
            $desc = data_get($response->json(), 'description', $response->body());
            throw NodeExecutionException::because("خطای بله: $desc");
        }

        $result = $response->json('result');

        return [
            'success'    => true,
            'method'     => $method,
            'message_id' => is_array($result) ? ($result['message_id'] ?? null) : null,
            'result'     => $result,
        ];
    }
}
