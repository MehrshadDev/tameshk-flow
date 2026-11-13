<?php

namespace App\Workflow\Nodes\Actions;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\TelegramApi;
use Illuminate\Support\Facades\Http;
use Throwable;

class TelegramNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'action.telegram',
            name: 'Telegram',
            category: NodeCategory::Action,
            description: 'ارسال و ویرایش پیام، دکمه‌های شیشه‌ای، تصویر، سند، دانلود ویس/فایل و مدیریت چت تلگرام.',
            icon: 'brand:telegram',
            group: 'telegram',
            inputs: ['main'],
            outputs: ['main'],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'telegram',
                    'label'           => 'اعتبارنامه ربات تلگرام',
                    'required'        => true,
                ],
                'operation' => [
                    'type'     => 'select',
                    'label'    => 'عملیات',
                    'options'  => [
                        ['value' => 'send_message', 'label' => 'ارسال پیام متنی (Send text message)'],
                        ['value' => 'send_photo', 'label' => 'ارسال تصویر (Send photo)'],
                        ['value' => 'send_document', 'label' => 'ارسال فایل یا سند (Send document)'],
                        ['value' => 'get_file', 'label' => 'دریافت و دانلود فایل یا ویس (Get / Download file)'],
                        ['value' => 'edit_message', 'label' => 'ویرایش پیام متنی (Edit text message)'],
                        ['value' => 'delete_message', 'label' => 'حذف پیام (Delete message)'],
                        ['value' => 'answer_callback', 'label' => 'پاسخ به کلیک دکمه (Answer callback query)'],
                        ['value' => 'send_action', 'label' => 'ارسال وضعیت چت (Send chat action)'],
                        ['value' => 'pin_message', 'label' => 'پین کردن پیام (Pin message)'],
                    ],
                    'default'  => 'send_message',
                    'required' => true,
                ],

                'chat_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه چت یا کانال (Chat ID)',
                    'required'    => true,
                    'placeholder' => 'مثلاً: 123456789 یا channel@ یا {{ input.chat_id }}',
                    'depends_on'  => [
                        'operation' => ['send_message', 'send_photo', 'send_document', 'edit_message', 'delete_message', 'send_action', 'pin_message'],
                    ],
                ],
                'text' => [
                    'type'        => 'textarea',
                    'label'       => 'متن پیام',
                    'required'    => true,
                    'placeholder' => 'متن پیام را وارد کنید',
                    'depends_on'  => ['operation' => ['send_message', 'edit_message']],
                ],
                'photo' => [
                    'type'        => 'text',
                    'label'       => 'آدرس یا شناسه تصویر (Photo URL / File ID)',
                    'required'    => true,
                    'placeholder' => 'https://example.com/image.jpg یا file_id تلگرام',
                    'depends_on'  => ['operation' => 'send_photo'],
                ],
                'document' => [
                    'type'        => 'text',
                    'label'       => 'آدرس یا شناسه فایل (Document URL / File ID)',
                    'required'    => true,
                    'placeholder' => 'https://example.com/file.pdf یا file_id تلگرام',
                    'depends_on'  => ['operation' => 'send_document'],
                ],
                'file_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه فایل یا ویس (File ID)',
                    'required'    => true,
                    'default'     => '{{ input.file_id }}',
                    'placeholder' => '{{ input.file_id }}',
                    'help'        => 'شناسه فایل دریافتی از تلگرام جهت دانلود و تبدیل به Data URL / Base64 (مناسب برای ارسال ویس به هوش مصنوعی).',
                    'depends_on'  => ['operation' => 'get_file'],
                ],
                'caption' => [
                    'type'        => 'textarea',
                    'label'       => 'توضیحات (Caption)',
                    'placeholder' => 'متن توضیحات همراه با تصویر یا فایل',
                    'depends_on'  => ['operation' => ['send_photo', 'send_document']],
                ],
                'message_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه پیام (Message ID)',
                    'required'    => true,
                    'placeholder' => 'مثلاً: {{ input.message_id }}',
                    'depends_on'  => ['operation' => ['edit_message', 'delete_message', 'pin_message']],
                ],
                'callback_query_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه رویداد کلیک (Callback Query ID)',
                    'required'    => true,
                    'placeholder' => '{{ input.callback_query_id }}',
                    'depends_on'  => ['operation' => 'answer_callback'],
                ],
                'chat_action' => [
                    'type'       => 'select',
                    'label'      => 'نوع وضعیت',
                    'options'    => [
                        ['value' => 'typing', 'label' => 'در حال نوشتن (Typing)'],
                        ['value' => 'upload_photo', 'label' => 'در حال ارسال عکس (Upload Photo)'],
                        ['value' => 'upload_document', 'label' => 'در حال ارسال فایل (Upload Document)'],
                        ['value' => 'record_video', 'label' => 'در حال ضبط ویدیو (Record Video)'],
                        ['value' => 'upload_video', 'label' => 'در حال ارسال ویدیو (Upload Video)'],
                        ['value' => 'record_voice', 'label' => 'در حال ضبط صدا (Record Voice)'],
                    ],
                    'default'    => 'typing',
                    'required'   => true,
                    'depends_on' => ['operation' => 'send_action'],
                ],

                'inline_keyboard' => [
                    'type'        => 'textarea',
                    'label'       => 'دکمه‌های شیشه‌ای (Inline Buttons)',
                    'placeholder' => "مثال: ✅ درسته | confirm , ❌ نه | cancel\n(برای قرار گرفتن دکمه‌ها کنار هم از کاما و برای ردیف بعدی از خط جدید استفاده کنید)",
                    'advanced'    => true,
                    'depends_on'  => ['operation' => ['send_message', 'send_photo', 'send_document', 'edit_message']],
                ],
                'business_connection_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه اتصال بیزینس (Business Connection ID)',
                    'placeholder' => '{{ input.business_connection_id }}',
                    'help'        => 'برای ارسال پیام از طرف اکانت شخصی تلگرام بیزینس (در صورت شروع ورکفلو با پیام بیزینس، به صورت خودکار تشخیص داده می‌شود).',
                    'advanced'    => true,
                    'depends_on'  => ['operation' => ['send_message', 'send_photo', 'send_document', 'edit_message', 'send_action', 'pin_message']],
                ],
                'parse_mode' => [
                    'type'       => 'select',
                    'label'      => 'نوع قالب‌بندی (Parse Mode)',
                    'options'    => [
                        ['value' => 'None', 'label' => 'متن ساده (None)'],
                        ['value' => 'HTML', 'label' => 'HTML'],
                        ['value' => 'Markdown', 'label' => 'Markdown'],
                    ],
                    'default'    => 'None',
                    'advanced'   => true,
                    'depends_on' => ['operation' => ['send_message', 'send_photo', 'send_document', 'edit_message']],
                ],
                'disable_preview' => [
                    'type'       => 'select',
                    'label'      => 'پیش‌نمایش لینک‌ها (Link Preview)',
                    'options'    => [
                        ['value' => '0', 'label' => 'فعال بودن پیش‌نمایش'],
                        ['value' => '1', 'label' => 'غیرفعال بودن پیش‌نمایش'],
                    ],
                    'default'    => '0',
                    'advanced'   => true,
                    'depends_on' => ['operation' => ['send_message', 'edit_message']],
                ],
                'reply_to_message_id' => [
                    'type'        => 'text',
                    'label'       => 'پاسخ به پیام (Reply to Message ID)',
                    'placeholder' => 'شناسه پیامی که می‌خواهید به آن ریپلای شود',
                    'advanced'    => true,
                    'depends_on'  => ['operation' => ['send_message', 'send_photo', 'send_document']],
                ],
                'disable_notification' => [
                    'type'       => 'select',
                    'label'      => 'ارسال بی‌صدا (Silent Notification)',
                    'options'    => [
                        ['value' => '0', 'label' => 'خیر (با اعلان و صدا)'],
                        ['value' => '1', 'label' => 'بله (بدون صدا)'],
                    ],
                    'default'    => '0',
                    'advanced'   => true,
                    'depends_on' => ['operation' => ['send_message', 'send_photo', 'send_document', 'pin_message']],
                ],
                'callback_text' => [
                    'type'        => 'text',
                    'label'       => 'متن پاسخ دکمه (Alert Text)',
                    'placeholder' => 'پیامی که به کاربر نمایش داده شود',
                    'advanced'    => true,
                    'depends_on'  => ['operation' => 'answer_callback'],
                ],
                'show_alert' => [
                    'type'       => 'select',
                    'label'      => 'نمایش به صورت پاپ‌آپ (Show Alert)',
                    'options'    => [
                        ['value' => '0', 'label' => 'خیر (اعلان کوچک بالای صفحه)'],
                        ['value' => '1', 'label' => 'بله (پنجره پاپ‌آپ با دکمه OK)'],
                    ],
                    'default'    => '0',
                    'advanced'   => true,
                    'depends_on' => ['operation' => 'answer_callback'],
                ],
            ],
            presets: [
                [
                    'key'         => 'send_message',
                    'name'        => 'Send a text message',
                    'description' => 'ارسال پیام متنی (به همراه دکمه‌های شیشه‌ای اختیاری) به کاربر، گروه یا کانال.',
                    'config'      => ['operation' => 'send_message'],
                ],
                [
                    'key'         => 'get_file',
                    'name'        => 'Get & download a file / voice',
                    'description' => 'دریافت و دانلود فایل یا ویس از تلگرام با File ID (مناسب برای تبدیل ویس به متن).',
                    'config'      => ['operation' => 'get_file'],
                ],
                [
                    'key'         => 'send_photo',
                    'name'        => 'Send a photo',
                    'description' => 'ارسال تصویر به همراه کپشن به تلگرام.',
                    'config'      => ['operation' => 'send_photo'],
                ],
                [
                    'key'         => 'send_document',
                    'name'        => 'Send a document',
                    'description' => 'ارسال فایل، پی‌دی‌اف یا سند به تلگرام.',
                    'config'      => ['operation' => 'send_document'],
                ],
                [
                    'key'         => 'edit_message',
                    'name'        => 'Edit a message',
                    'description' => 'ویرایش متن یا دکمه‌های پیام قبلی ربات در چت.',
                    'config'      => ['operation' => 'edit_message'],
                ],
                [
                    'key'         => 'delete_message',
                    'name'        => 'Delete a message',
                    'description' => 'حذف پیام از چت تلگرام.',
                    'config'      => ['operation' => 'delete_message'],
                ],
                [
                    'key'         => 'answer_callback',
                    'name'        => 'Answer callback query',
                    'description' => 'پاسخ به کلیک کاربر روی دکمه‌های شیشه‌ای ربات.',
                    'config'      => ['operation' => 'answer_callback'],
                ],
                [
                    'key'         => 'send_action',
                    'name'        => 'Send chat action',
                    'description' => 'ارسال وضعیت تایپ کردن یا آپلود فایل در چت.',
                    'config'      => ['operation' => 'send_action'],
                ],
                [
                    'key'         => 'pin_message',
                    'name'        => 'Pin a message',
                    'description' => 'پین کردن پیام در گروه یا کانال تلگرام.',
                    'config'      => ['operation' => 'pin_message'],
                ],
            ],
            color: 'sky',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('اعتبارنامه ربات تلگرام انتخاب نشده است.');
        }

        $botToken = trim((string) $credential->get('bot_token'));

        if ($botToken === '') {
            throw NodeExecutionException::because('توکن ربات تلگرام در اعتبارنامه خالی است.');
        }

        $operation = (string) ($context->config['operation'] ?? 'send_message');

        return match ($operation) {
            'send_message'    => $this->handleSendMessage($context, $botToken),
            'send_photo'      => $this->handleSendMedia($context, $botToken, 'sendPhoto', 'photo', 'آدرس یا شناسه تصویر (Photo) الزامی است.'),
            'send_document'   => $this->handleSendMedia($context, $botToken, 'sendDocument', 'document', 'آدرس یا شناسه فایل (Document) الزامی است.'),
            'get_file'        => $this->handleGetFile($context, $botToken),
            'edit_message'    => $this->handleEditMessage($context, $botToken),
            'delete_message'  => $this->handleDeleteMessage($context, $botToken),
            'answer_callback' => $this->handleAnswerCallback($context, $botToken),
            'send_action'     => $this->handleSendAction($context, $botToken),
            'pin_message'     => $this->handlePinMessage($context, $botToken),
            default           => throw NodeExecutionException::because("عملیات تلگرام نامعتبر است: $operation"),
        };
    }

    private function value(NodeContext $context, string $key): string
    {
        return trim((string) ($context->config[$key] ?? ''));
    }

    private function flag(NodeContext $context, string $key): bool
    {
        return ((string) ($context->config[$key] ?? '0')) === '1';
    }

    private function required(NodeContext $context, string $key, string $message): string
    {
        $value = $this->value($context, $key);

        if ($value === '') {
            throw NodeExecutionException::because($message);
        }

        return $value;
    }

    private function done(array $extra = []): NodeResult
    {
        return NodeResult::of(['success' => true, ...$extra]);
    }

    private function withParseMode(array $payload, NodeContext $context): array
    {
        $parseMode = $this->value($context, 'parse_mode');

        if ($parseMode !== '' && $parseMode !== 'None') {
            $payload['parse_mode'] = $parseMode;
        }

        return $payload;
    }

    private function withBusinessConnection(array $payload, NodeContext $context): array
    {
        $explicit = $this->value($context, 'business_connection_id');

        if ($explicit !== '') {
            $payload['business_connection_id'] = $explicit;

            return $payload;
        }

        $triggerData = $context->run->trigger_data ?? [];
        $triggerBusinessId = trim((string) (
            data_get($context->input, 'business_connection_id')
            ?? data_get($triggerData, 'business_message.business_connection_id')
            ?? data_get($triggerData, 'edited_business_message.business_connection_id')
            ?? data_get($triggerData, 'business_connection_id')
            ?? ''
        ));

        if ($triggerBusinessId === '') {
            return $payload;
        }

        $triggerChatId = (string) (
            data_get($context->input, 'chat_id')
            ?? data_get($triggerData, 'business_message.chat.id')
            ?? data_get($triggerData, 'edited_business_message.chat.id')
            ?? ''
        );

        if ($triggerChatId === '' || (string) ($payload['chat_id'] ?? '') === $triggerChatId) {
            $payload['business_connection_id'] = $triggerBusinessId;
        }

        return $payload;
    }

    private function buildReplyMarkup(NodeContext $context): ?array
    {
        $raw = $this->value($context, 'inline_keyboard');

        if ($raw === '') {
            return null;
        }

        if (str_starts_with($raw, '[') || str_starts_with($raw, '{')) {
            $decoded = json_decode($raw, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                if (isset($decoded['inline_keyboard']) && is_array($decoded['inline_keyboard'])) {
                    return ['inline_keyboard' => $decoded['inline_keyboard']];
                }

                if (isset($decoded[0]['text'])) {
                    return ['inline_keyboard' => [$decoded]];
                }

                return ['inline_keyboard' => $decoded];
            }
        }

        $lines = preg_split('/\r\n|\r|\n/', $raw) ?: [];
        $keyboard = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $parts = preg_split('/[,،]+/u', $line) ?: [];
            $row = [];

            foreach ($parts as $part) {
                $part = trim($part);

                if ($part === '') {
                    continue;
                }

                if (str_contains($part, '|')) {
                    [$btnLabel, $btnData] = array_map('trim', explode('|', $part, 2));
                } else {
                    $btnLabel = $part;
                    $btnData = $part;
                }

                if ($btnLabel === '') {
                    continue;
                }

                if ($btnData === '') {
                    $btnData = $btnLabel;
                }

                if (preg_match('#^https?://#i', $btnData)) {
                    $row[] = [
                        'text' => $btnLabel,
                        'url'  => $btnData,
                    ];
                } else {
                    $row[] = [
                        'text'          => $btnLabel,
                        'callback_data' => mb_strcut($btnData, 0, 64, 'UTF-8'),
                    ];
                }
            }

            if ($row !== []) {
                $keyboard[] = $row;
            }
        }

        return $keyboard !== [] ? ['inline_keyboard' => $keyboard] : null;
    }

    private function applyMessageOptions(array $payload, NodeContext $context): array
    {
        $payload = $this->withParseMode($payload, $context);
        $payload = $this->withBusinessConnection($payload, $context);
        $replyTo = $this->value($context, 'reply_to_message_id');

        if ($replyTo !== '') {
            $payload['reply_to_message_id'] = $replyTo;
        }

        $replyMarkup = $this->buildReplyMarkup($context);

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        return $payload;
    }

    private function resolveChatAndMessageId(NodeContext $context): array
    {
        $message = 'شناسه چت و شناسه پیام الزامی هستند.';

        return [
            $this->required($context, 'chat_id', $message),
            $this->required($context, 'message_id', $message),
        ];
    }

    private function handleSendMessage(NodeContext $context, string $botToken): NodeResult
    {
        $payload = $this->applyMessageOptions([
            'chat_id'                  => $this->required($context, 'chat_id', 'شناسه چت مقصد (Chat ID) الزامی است.'),
            'text'                     => $this->required($context, 'text', 'متن پیام الزامی است.'),
            'disable_web_page_preview' => $this->flag($context, 'disable_preview'),
            'disable_notification'     => $this->flag($context, 'disable_notification'),
        ], $context);

        $result = $this->request($botToken, 'sendMessage', $payload);

        return $this->done([
            'message_id' => data_get($result, 'message_id'),
            'chat'       => data_get($result, 'chat'),
            'date'       => data_get($result, 'date'),
            'text'       => data_get($result, 'text'),
            'raw'        => $result,
        ]);
    }

    private function handleSendMedia(NodeContext $context, string $botToken, string $method, string $field, string $missing): NodeResult
    {
        $payload = $this->applyMessageOptions([
            'chat_id'              => $this->required($context, 'chat_id', 'شناسه چت مقصد الزامی است.'),
            $field                 => $this->required($context, $field, $missing),
            'caption'              => (string) ($context->config['caption'] ?? ''),
            'disable_notification' => $this->flag($context, 'disable_notification'),
        ], $context);

        $result = $this->request($botToken, $method, $payload);

        return $this->done([
            'message_id' => data_get($result, 'message_id'),
            'chat'       => data_get($result, 'chat'),
            'caption'    => data_get($result, 'caption'),
            $field       => data_get($result, $field),
            'raw'        => $result,
        ]);
    }

    private function handleGetFile(NodeContext $context, string $botToken): NodeResult
    {
        $fileId = $this->required($context, 'file_id', 'شناسه فایل یا ویس (File ID) الزامی است.');

        $fileInfo = $this->request($botToken, 'getFile', ['file_id' => $fileId]);
        $filePath = trim((string) data_get($fileInfo, 'file_path', ''));

        if ($filePath === '') {
            throw NodeExecutionException::because('مسیر دانلود فایل از تلگرام دریافت نشد.');
        }

        $downloadUrl = TelegramApi::fileUrl($botToken, $filePath);

        try {
            $response = Http::timeout(60)->get($downloadUrl);
        } catch (Throwable $e) {
            throw NodeExecutionException::because("خطا در دانلود فایل از تلگرام: {$e->getMessage()}");
        }

        if ($response->failed()) {
            throw NodeExecutionException::because("دانلود فایل از تلگرام با کد {$response->status()} ناموفق بود.");
        }

        $binary = $response->body();
        $mimeType = $this->detectMimeType($filePath, (string) $response->header('Content-Type'));
        $base64 = base64_encode($binary);

        return $this->done([
            'file_id'        => data_get($fileInfo, 'file_id', $fileId),
            'file_unique_id' => data_get($fileInfo, 'file_unique_id'),
            'file_path'      => $filePath,
            'file_size'      => data_get($fileInfo, 'file_size', strlen($binary)),
            'mime_type'      => $mimeType,
            'data_url'       => "data:$mimeType;base64,$base64",
            'base64'         => $base64,
        ]);
    }

    private function detectMimeType(string $filePath, string $headerContentType): string
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        $byExt = match ($ext) {
            'oga', 'ogg', 'opus' => 'audio/ogg',
            'mp3'                => 'audio/mp3',
            'm4a'                => 'audio/m4a',
            'wav'                => 'audio/wav',
            'aac'                => 'audio/aac',
            'jpg', 'jpeg'        => 'image/jpeg',
            'png'                => 'image/png',
            'webp'               => 'image/webp',
            'gif'                => 'image/gif',
            'mp4'                => 'video/mp4',
            'mov'                => 'video/quicktime',
            'webm'               => 'video/webm',
            'pdf'                => 'application/pdf',
            default              => null,
        };

        if ($byExt !== null) {
            return $byExt;
        }

        $cleanHeader = trim(explode(';', $headerContentType)[0] ?? '');

        if ($cleanHeader !== '' && $cleanHeader !== 'application/octet-stream') {
            return $cleanHeader;
        }

        return 'audio/ogg';
    }

    private function handleEditMessage(NodeContext $context, string $botToken): NodeResult
    {
        [$chatId, $messageId] = $this->resolveChatAndMessageId($context);

        $payload = $this->withBusinessConnection($this->withParseMode([
            'chat_id'                  => $chatId,
            'message_id'               => $messageId,
            'text'                     => $this->required($context, 'text', 'متن جدید پیام الزامی است.'),
            'disable_web_page_preview' => $this->flag($context, 'disable_preview'),
        ], $context), $context);

        $replyMarkup = $this->buildReplyMarkup($context);

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        $result = $this->request($botToken, 'editMessageText', $payload);

        return $this->done([
            'message_id' => data_get($result, 'message_id'),
            'text'       => data_get($result, 'text'),
            'raw'        => $result,
        ]);
    }

    private function handleDeleteMessage(NodeContext $context, string $botToken): NodeResult
    {
        [$chatId, $messageId] = $this->resolveChatAndMessageId($context);

        $this->request($botToken, 'deleteMessage', ['chat_id' => $chatId, 'message_id' => $messageId]);

        return $this->done(['chat_id' => $chatId, 'message_id' => $messageId]);
    }

    private function handleAnswerCallback(NodeContext $context, string $botToken): NodeResult
    {
        $callbackId = $this->required($context, 'callback_query_id', 'شناسه رویداد کلیک (Callback Query ID) الزامی است.');

        $this->request($botToken, 'answerCallbackQuery', [
            'callback_query_id' => $callbackId,
            'text'              => (string) ($context->config['callback_text'] ?? ''),
            'show_alert'        => $this->flag($context, 'show_alert'),
        ]);

        return $this->done(['callback_query_id' => $callbackId]);
    }

    private function handleSendAction(NodeContext $context, string $botToken): NodeResult
    {
        $chatId = $this->required($context, 'chat_id', 'شناسه چت الزامی است.');
        $action = (string) ($context->config['chat_action'] ?? 'typing');

        $payload = $this->withBusinessConnection(['chat_id' => $chatId, 'action' => $action], $context);

        $this->request($botToken, 'sendChatAction', $payload);

        return $this->done(['chat_id' => $chatId, 'action' => $action]);
    }

    private function handlePinMessage(NodeContext $context, string $botToken): NodeResult
    {
        [$chatId, $messageId] = $this->resolveChatAndMessageId($context);

        $payload = $this->withBusinessConnection([
            'chat_id'              => $chatId,
            'message_id'           => $messageId,
            'disable_notification' => $this->flag($context, 'disable_notification'),
        ], $context);

        $this->request($botToken, 'pinChatMessage', $payload);

        return $this->done(['chat_id' => $chatId, 'message_id' => $messageId]);
    }

    private function request(string $botToken, string $method, array $payload): array
    {
        try {
            $response = Http::timeout(30)->post(TelegramApi::url($botToken, $method), $payload);
        } catch (Throwable $e) {
            throw NodeExecutionException::because("خطا در ارتباط با سرور تلگرام: {$e->getMessage()}");
        }

        if ($response->failed()) {
            $errorDescription = data_get($response->json(), 'description', $response->body());
            throw NodeExecutionException::because("خطای تلگرام: $errorDescription");
        }

        return (array) data_get($response->json(), 'result', []);
    }
}
