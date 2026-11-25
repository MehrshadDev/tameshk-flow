<?php

namespace App\Workflow\Nodes\Actions;

use App\Workflow\Contracts\NodeContract;
use App\Workflow\DTOs\NodeContext;
use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\DTOs\NodeResult;
use App\Workflow\Enums\NodeCategory;
use App\Workflow\Exceptions\NodeExecutionException;
use App\Workflow\Support\BaleApi;
use App\Workflow\Support\BaleFormatter;
use App\Workflow\Support\BotMessageHelper;
use Illuminate\Support\Facades\Http;
use Throwable;

class BaleNode implements NodeContract
{
    public static function definition(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'action.bale',
            name: 'Bale',
            category: NodeCategory::Action,
            description: 'ارسال و ویرایش پیام، دکمه‌های شیشه‌ای، تصویر، ویدیو، صوت، سند، دانلود ویس/فایل، فوروارد و مدیریت چت در پیام‌رسان بله.',
            icon: 'brand:bale',
            group: 'bale',
            inputs: ['main'],
            outputs: ['main'],
            configSchema: [
                'credential_id' => [
                    'type'            => 'credential',
                    'credential_type' => 'bale',
                    'label'           => 'اعتبارنامه بازوی بله',
                    'required'        => true,
                ],
                'operation' => [
                    'type'     => 'select',
                    'label'    => 'عملیات',
                    'options'  => [
                        ['value' => 'send_message', 'label' => 'ارسال پیام متنی (Send text message)'],
                        ['value' => 'send_photo', 'label' => 'ارسال تصویر (Send photo)'],
                        ['value' => 'send_document', 'label' => 'ارسال فایل یا سند (Send document)'],
                        ['value' => 'send_video', 'label' => 'ارسال ویدیو (Send video)'],
                        ['value' => 'send_audio', 'label' => 'ارسال صوت (Send audio)'],
                        ['value' => 'get_file', 'label' => 'دریافت و دانلود فایل یا ویس (Get / Download file)'],
                        ['value' => 'edit_message', 'label' => 'ویرایش پیام متنی (Edit text message)'],
                        ['value' => 'delete_message', 'label' => 'حذف پیام (Delete message)'],
                        ['value' => 'forward_message', 'label' => 'فوروارد پیام (Forward message)'],
                        ['value' => 'copy_message', 'label' => 'کپی پیام (Copy message)'],
                        ['value' => 'answer_callback', 'label' => 'پاسخ به کلیک دکمه (Answer callback query)'],
                        ['value' => 'send_action', 'label' => 'ارسال وضعیت چت (Send chat action)'],
                        ['value' => 'pin_message', 'label' => 'پین کردن پیام (Pin message)'],
                    ],
                    'default'  => 'send_message',
                    'required' => true,
                ],
                'chat_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه چت یا کانال مقصد (Chat ID)',
                    'required'    => true,
                    'placeholder' => 'مثلاً: 123456789 یا channel@ یا {{ input.chat_id }}',
                    'depends_on'  => [
                        'operation' => [
                            'send_message',
                            'send_photo',
                            'send_document',
                            'send_video',
                            'send_audio',
                            'edit_message',
                            'delete_message',
                            'forward_message',
                            'copy_message',
                            'send_action',
                            'pin_message',
                        ],
                    ],
                ],
                'from_chat_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه چت مبدأ (From Chat ID)',
                    'required'    => true,
                    'placeholder' => 'مثلاً: {{ input.chat_id }}',
                    'depends_on'  => ['operation' => ['forward_message', 'copy_message']],
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
                    'placeholder' => 'https://example.com/image.jpg یا file_id بله',
                    'depends_on'  => ['operation' => 'send_photo'],
                ],
                'document' => [
                    'type'        => 'text',
                    'label'       => 'آدرس یا شناسه فایل (Document URL / File ID)',
                    'required'    => true,
                    'placeholder' => 'https://example.com/file.pdf یا file_id بله',
                    'depends_on'  => ['operation' => 'send_document'],
                ],
                'video' => [
                    'type'        => 'text',
                    'label'       => 'آدرس یا شناسه ویدیو (Video URL / File ID)',
                    'required'    => true,
                    'placeholder' => 'https://example.com/video.mp4 یا file_id بله',
                    'depends_on'  => ['operation' => 'send_video'],
                ],
                'audio' => [
                    'type'        => 'text',
                    'label'       => 'آدرس یا شناسه فایل صوتی (Audio URL / File ID)',
                    'required'    => true,
                    'placeholder' => 'https://example.com/audio.mp3 یا file_id بله',
                    'depends_on'  => ['operation' => 'send_audio'],
                ],
                'file_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه فایل یا ویس (File ID)',
                    'required'    => true,
                    'default'     => '{{ input.file_id }}',
                    'placeholder' => '{{ input.file_id }}',
                    'help'        => 'شناسه فایل دریافتی از بله جهت دانلود و تبدیل به Data URL / Base64 (مناسب برای ارسال ویس یا تصویر به هوش مصنوعی).',
                    'depends_on'  => ['operation' => 'get_file'],
                ],
                'caption' => [
                    'type'        => 'textarea',
                    'label'       => 'توضیحات (Caption)',
                    'placeholder' => 'متن توضیحات همراه با رسانه',
                    'depends_on'  => ['operation' => ['send_photo', 'send_document', 'send_video', 'send_audio']],
                ],
                'message_id' => [
                    'type'        => 'text',
                    'label'       => 'شناسه پیام (Message ID)',
                    'required'    => true,
                    'placeholder' => 'مثلاً: {{ input.message_id }}',
                    'depends_on'  => ['operation' => ['edit_message', 'delete_message', 'forward_message', 'copy_message', 'pin_message']],
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
                        ['value' => 'record_video', 'label' => 'در حال ضبط ویدیو (Record Video)'],
                        ['value' => 'upload_video', 'label' => 'در حال ارسال ویدیو (Upload Video)'],
                        ['value' => 'record_voice', 'label' => 'در حال ضبط صدا (Record Voice)'],
                        ['value' => 'upload_voice', 'label' => 'در حال ارسال صدا (Upload Voice)'],
                        ['value' => 'choose_sticker', 'label' => 'در حال انتخاب استیکر (Choose Sticker)'],
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
                    'depends_on'  => ['operation' => ['send_message', 'send_photo', 'send_document', 'send_video', 'send_audio', 'edit_message']],
                ],
                'format_mode' => [
                    'type'       => 'select',
                    'label'      => 'قالب‌بندی متن (Text Formatting)',
                    'options'    => [
                        ['value' => 'auto', 'label' => 'تبدیل خودکار Markdown به قالب بله'],
                        ['value' => 'none', 'label' => 'ارسال بدون تغییر (متن خام بله)'],
                    ],
                    'default'    => 'auto',
                    'advanced'   => true,
                    'depends_on' => ['operation' => ['send_message', 'send_photo', 'send_document', 'send_video', 'send_audio', 'edit_message']],
                ],
                'reply_to_message_id' => [
                    'type'        => 'text',
                    'label'       => 'پاسخ به پیام (Reply to Message ID)',
                    'placeholder' => 'شناسه پیامی که می‌خواهید به آن ریپلای شود',
                    'advanced'    => true,
                    'depends_on'  => ['operation' => ['send_message', 'send_photo', 'send_document', 'send_video', 'send_audio']],
                ],
                'use_business_api' => [
                    'type'       => 'select',
                    'label'      => 'استفاده از API کسب‌وکاری بله (Business API)',
                    'options'    => [
                        ['value' => '0', 'label' => 'خیر (API عادی بازو)'],
                        ['value' => '1', 'label' => 'بله (برای بازوهای دارای اشتراک کسب‌وکاری)'],
                    ],
                    'default'    => '0',
                    'advanced'   => true,
                    'depends_on' => ['operation' => ['send_message', 'send_photo', 'send_document', 'send_video', 'send_audio', 'forward_message']],
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
                        ['value' => '1', 'label' => 'بله (پنجره پاپ‌آپ با دکمه تأیید)'],
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
                    'description' => 'ارسال پیام متنی به کاربر، گروه یا کانال در بله.',
                    'config'      => ['operation' => 'send_message'],
                ],
                [
                    'key'         => 'get_file',
                    'name'        => 'Get & download a file / voice',
                    'description' => 'دریافت و دانلود فایل یا ویس از بله با File ID (مناسب برای تبدیل ویس به متن).',
                    'config'      => ['operation' => 'get_file'],
                ],
                [
                    'key'         => 'send_photo',
                    'name'        => 'Send a photo',
                    'description' => 'ارسال تصویر به همراه کپشن در بله.',
                    'config'      => ['operation' => 'send_photo'],
                ],
                [
                    'key'         => 'send_document',
                    'name'        => 'Send a document',
                    'description' => 'ارسال فایل، پی‌دی‌اف یا سند در بله.',
                    'config'      => ['operation' => 'send_document'],
                ],
                [
                    'key'         => 'send_video',
                    'name'        => 'Send a video',
                    'description' => 'ارسال ویدیو به همراه کپشن در بله.',
                    'config'      => ['operation' => 'send_video'],
                ],
                [
                    'key'         => 'send_audio',
                    'name'        => 'Send an audio',
                    'description' => 'ارسال فایل صوتی در بله.',
                    'config'      => ['operation' => 'send_audio'],
                ],
                [
                    'key'         => 'edit_message',
                    'name'        => 'Edit a message',
                    'description' => 'ویرایش متن یا دکمه‌های پیام قبلی بازو در چت.',
                    'config'      => ['operation' => 'edit_message'],
                ],
                [
                    'key'         => 'delete_message',
                    'name'        => 'Delete a message',
                    'description' => 'حذف پیام از چت بله.',
                    'config'      => ['operation' => 'delete_message'],
                ],
                [
                    'key'         => 'forward_message',
                    'name'        => 'Forward a message',
                    'description' => 'فوروارد پیام از یک چت به چت دیگر در بله.',
                    'config'      => ['operation' => 'forward_message'],
                ],
                [
                    'key'         => 'copy_message',
                    'name'        => 'Copy a message',
                    'description' => 'ارسال کپی پیام بدون نمایش نام فرستنده اصلی در بله.',
                    'config'      => ['operation' => 'copy_message'],
                ],
                [
                    'key'         => 'answer_callback',
                    'name'        => 'Answer callback query',
                    'description' => 'پاسخ به کلیک کاربر روی دکمه‌های شیشه‌ای بازو.',
                    'config'      => ['operation' => 'answer_callback'],
                ],
                [
                    'key'         => 'send_action',
                    'name'        => 'Send chat action',
                    'description' => 'ارسال وضعیت در حال نوشتن یا آپلود فایل در چت.',
                    'config'      => ['operation' => 'send_action'],
                ],
                [
                    'key'         => 'pin_message',
                    'name'        => 'Pin a message',
                    'description' => 'پین کردن پیام در گروه یا کانال بله.',
                    'config'      => ['operation' => 'pin_message'],
                ],
            ],
            color: 'emerald',
        );
    }

    public function execute(NodeContext $context): NodeResult
    {
        $credential = $context->credential();

        if (! $credential) {
            throw NodeExecutionException::because('اعتبارنامه بازوی بله انتخاب نشده است.');
        }

        $botToken = trim((string) $credential->get('bot_token'));

        if ($botToken === '') {
            throw NodeExecutionException::because('توکن بازوی بله در اعتبارنامه خالی است.');
        }

        $operation = (string) ($context->config['operation'] ?? 'send_message');

        return match ($operation) {
            'send_message'    => $this->handleSendMessage($context, $botToken),
            'send_photo'      => $this->handleSendMedia($context, $botToken, 'sendPhoto', 'photo', 'آدرس یا شناسه تصویر (Photo) الزامی است.'),
            'send_document'   => $this->handleSendMedia($context, $botToken, 'sendDocument', 'document', 'آدرس یا شناسه فایل (Document) الزامی است.'),
            'send_video'      => $this->handleSendMedia($context, $botToken, 'sendVideo', 'video', 'آدرس یا شناسه ویدیو (Video) الزامی است.'),
            'send_audio'      => $this->handleSendMedia($context, $botToken, 'sendAudio', 'audio', 'آدرس یا شناسه فایل صوتی (Audio) الزامی است.'),
            'get_file'        => $this->handleGetFile($context, $botToken),
            'edit_message'    => $this->handleEditMessage($context, $botToken),
            'delete_message'  => $this->handleDeleteMessage($context, $botToken),
            'forward_message' => $this->handleRelayMessage($context, $botToken, 'forwardMessage'),
            'copy_message'    => $this->handleRelayMessage($context, $botToken, 'copyMessage'),
            'answer_callback' => $this->handleAnswerCallback($context, $botToken),
            'send_action'     => $this->handleSendAction($context, $botToken),
            'pin_message'     => $this->handlePinMessage($context, $botToken),
            default           => throw NodeExecutionException::because("عملیات بله نامعتبر است: $operation"),
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

    private function requiredInt(NodeContext $context, string $key, string $message): int
    {
        $raw = $this->required($context, $key, $message);

        if (! preg_match('/^-?\d+$/', $raw)) {
            throw NodeExecutionException::because("مقدار فیلد $key باید عددی باشد.");
        }

        return (int) $raw;
    }

    private function done(array $extra = []): NodeResult
    {
        return NodeResult::of(['success' => true, ...$extra]);
    }

    private function buildReplyMarkup(NodeContext $context): ?array
    {
        return BotMessageHelper::buildInlineKeyboard($this->value($context, 'inline_keyboard'));
    }

    private function resolveChatAndMessageId(NodeContext $context): array
    {
        $message = 'شناسه چت و شناسه پیام الزامی هستند.';

        return [
            $this->required($context, 'chat_id', $message),
            $this->requiredInt($context, 'message_id', $message),
        ];
    }

    private function handleSendMessage(NodeContext $context, string $botToken): NodeResult
    {
        $chatId = $this->required($context, 'chat_id', 'شناسه چت مقصد (Chat ID) الزامی است.');
        $rawText = $this->required($context, 'text', 'متن پیام الزامی است.');
        $formatMode = $this->value($context, 'format_mode') ?: 'auto';
        $replyTo = $this->value($context, 'reply_to_message_id');
        $replyMarkup = $this->buildReplyMarkup($context);
        $business = $this->flag($context, 'use_business_api');

        $prepared = BaleFormatter::prepare($rawText, $formatMode);
        $chunks = BaleFormatter::split($prepared);
        $totalChunks = count($chunks);
        $lastResult = [];
        $messageIds = [];

        foreach ($chunks as $index => $chunk) {
            $payload = [
                'chat_id' => $chatId,
                'text'    => $chunk,
            ];

            if ($index === 0 && $replyTo !== '' && preg_match('/^-?\d+$/', $replyTo)) {
                $payload['reply_to_message_id'] = (int) $replyTo;
            }

            if ($index === $totalChunks - 1 && $replyMarkup !== null) {
                $payload['reply_markup'] = $replyMarkup;
            }

            $lastResult = $this->request($botToken, 'sendMessage', $payload, $business);

            if (($mid = data_get($lastResult, 'message_id')) !== null) {
                $messageIds[] = $mid;
            }
        }

        return $this->done([
            'message_id'  => data_get($lastResult, 'message_id'),
            'message_ids' => $messageIds,
            'chat'        => data_get($lastResult, 'chat'),
            'date'        => data_get($lastResult, 'date'),
            'text'        => data_get($lastResult, 'text'),
            'raw'         => $lastResult,
        ]);
    }

    private function handleSendMedia(NodeContext $context, string $botToken, string $method, string $field, string $missing): NodeResult
    {
        $rawCaption = (string) ($context->config['caption'] ?? '');
        $formatMode = $this->value($context, 'format_mode') ?: 'auto';
        $safeCaption = BaleFormatter::truncate(
            BaleFormatter::prepare($rawCaption, $formatMode),
            BaleFormatter::MAX_CAPTION_LENGTH
        );
        $business = $this->flag($context, 'use_business_api');

        $payload = [
            'chat_id' => $this->required($context, 'chat_id', 'شناسه چت مقصد الزامی است.'),
            $field    => $this->required($context, $field, $missing),
        ];

        if ($safeCaption !== '') {
            $payload['caption'] = $safeCaption;
        }

        $replyTo = $this->value($context, 'reply_to_message_id');
        if ($replyTo !== '' && preg_match('/^-?\d+$/', $replyTo)) {
            $payload['reply_to_message_id'] = (int) $replyTo;
        }

        $replyMarkup = $this->buildReplyMarkup($context);
        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        $result = $this->request($botToken, $method, $payload, $business);

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
            throw NodeExecutionException::because('مسیر دانلود فایل از بله دریافت نشد.');
        }

        $downloadUrl = BaleApi::fileUrl($botToken, $filePath);

        try {
            $response = Http::connectTimeout(15)->timeout(60)->get($downloadUrl);
        } catch (Throwable $e) {
            throw NodeExecutionException::because("خطا در دانلود فایل از بله: {$e->getMessage()}", secrets: [$botToken]);
        }

        if ($response->failed()) {
            throw NodeExecutionException::because("دانلود فایل از بله با کد {$response->status()} ناموفق بود.");
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
        $rawText = $this->required($context, 'text', 'متن جدید پیام الزامی است.');
        $formatMode = $this->value($context, 'format_mode') ?: 'auto';
        $safeText = BaleFormatter::truncate(
            BaleFormatter::prepare($rawText, $formatMode),
            BaleFormatter::MAX_EDIT_LENGTH
        );

        $payload = [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
            'text'       => $safeText,
        ];

        $replyMarkup = $this->buildReplyMarkup($context);
        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        $result = $this->request($botToken, 'editMessageText', $payload);

        return $this->done([
            'message_id' => data_get($result, 'message_id', $messageId),
            'text'       => data_get($result, 'text', $safeText),
            'raw'        => $result,
        ]);
    }

    private function handleDeleteMessage(NodeContext $context, string $botToken): NodeResult
    {
        [$chatId, $messageId] = $this->resolveChatAndMessageId($context);

        $this->request($botToken, 'deleteMessage', [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
        ]);

        return $this->done(['chat_id' => $chatId, 'message_id' => $messageId]);
    }

    private function handleRelayMessage(NodeContext $context, string $botToken, string $method): NodeResult
    {
        [$chatId, $messageId] = $this->resolveChatAndMessageId($context);
        $fromChatId = $this->required($context, 'from_chat_id', 'شناسه چت مبدأ (From Chat ID) الزامی است.');
        $business = $method === 'forwardMessage' && $this->flag($context, 'use_business_api');

        $result = $this->request($botToken, $method, [
            'chat_id'      => $chatId,
            'from_chat_id' => $fromChatId,
            'message_id'   => $messageId,
        ], $business);

        return $this->done([
            'message_id' => data_get($result, 'message_id'),
            'chat'       => data_get($result, 'chat'),
            'raw'        => $result,
        ]);
    }

    private function handleAnswerCallback(NodeContext $context, string $botToken): NodeResult
    {
        $callbackId = $this->required($context, 'callback_query_id', 'شناسه رویداد کلیک (Callback Query ID) الزامی است.');

        $payload = [
            'callback_query_id' => $callbackId,
            'show_alert'        => $this->flag($context, 'show_alert'),
        ];

        $callbackText = (string) ($context->config['callback_text'] ?? '');
        if ($callbackText !== '') {
            $payload['text'] = $callbackText;
        }

        $this->request($botToken, 'answerCallbackQuery', $payload);

        return $this->done(['callback_query_id' => $callbackId]);
    }

    private function handleSendAction(NodeContext $context, string $botToken): NodeResult
    {
        $chatId = $this->required($context, 'chat_id', 'شناسه چت الزامی است.');
        $action = (string) ($context->config['chat_action'] ?? 'typing');

        $this->request($botToken, 'sendChatAction', [
            'chat_id' => $chatId,
            'action'  => $action,
        ]);

        return $this->done(['chat_id' => $chatId, 'action' => $action]);
    }

    private function handlePinMessage(NodeContext $context, string $botToken): NodeResult
    {
        [$chatId, $messageId] = $this->resolveChatAndMessageId($context);

        $this->request($botToken, 'pinChatMessage', [
            'chat_id'    => $chatId,
            'message_id' => $messageId,
        ]);

        return $this->done(['chat_id' => $chatId, 'message_id' => $messageId]);
    }

    private function request(string $botToken, string $method, array $payload, bool $business = false): mixed
    {
        try {
            $response = Http::connectTimeout(15)->timeout(30)->post(BaleApi::url($botToken, $method, $business), $payload);
        } catch (Throwable $e) {
            throw NodeExecutionException::because("خطا در ارتباط با سرور بله: {$e->getMessage()}", secrets: [$botToken]);
        }

        if ($response->failed() || $response->json('ok') === false) {
            $errorDescription = data_get($response->json(), 'description', $response->body());
            throw NodeExecutionException::because("خطای بله: $errorDescription");
        }

        return $response->json('result');
    }
}
