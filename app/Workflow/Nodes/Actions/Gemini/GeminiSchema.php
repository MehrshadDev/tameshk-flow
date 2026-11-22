<?php

namespace App\Workflow\Nodes\Actions\Gemini;

use App\Workflow\DTOs\NodeDefinition;
use App\Workflow\Enums\NodeCategory;

final class GeminiSchema
{
    public static function make(): NodeDefinition
    {
        return new NodeDefinition(
            type: 'action.gemini',
            name: 'Google Gemini',
            category: NodeCategory::Ai,
            description: 'ارسال پرامپت، تولید و ویرایش تصویر، تحلیل صدا، ویدیو، اسناد و مخزن جستجوی فایل با مدل‌های Gemini.',
            icon: 'brand:gemini',
            group: 'gemini',
            inputs: ['main'],
            outputs: ['main'],
            configSchema: [
                'credential_id' => [
                    'type' => 'credential',
                    'credential_type' => 'gemini',
                    'label' => 'اعتبارنامه Gemini',
                    'required' => true,
                ],
                'resource' => [
                    'type' => 'select',
                    'label' => 'بخش (Resource)',
                    'options' => [
                        ['value' => 'text', 'label' => 'متن و چت (Text)'],
                        ['value' => 'image', 'label' => 'تصویر (Image)'],
                        ['value' => 'audio', 'label' => 'صدا و ویس (Audio)'],
                        ['value' => 'document', 'label' => 'سند و PDF (Document)'],
                        ['value' => 'file_search', 'label' => 'مخزن فایل (File Search)'],
                        ['value' => 'media_file', 'label' => 'آپلود مدیا (Files API)'],
                        ['value' => 'video', 'label' => 'ویدیو (Video)'],
                    ],
                    'default' => 'text',
                    'required' => true,
                ],

                'operation' => [
                    'type' => 'select',
                    'label' => 'عملیات',
                    'options' => [
                        ['value' => 'message_model', 'label' => 'ارسال پیام به مدل (Message a Model)'],
                    ],
                    'default' => 'message_model',
                    'required' => true,
                    'depends_on' => ['resource' => 'text'],
                ],
                'operation_image' => [
                    'type' => 'select',
                    'label' => 'عملیات تصویر',
                    'options' => [
                        ['value' => 'analyze_image', 'label' => 'تحلیل تصویر (Analyze Image)'],
                        ['value' => 'generate_image', 'label' => 'تولید تصویر (Generate Image)'],
                        ['value' => 'edit_image', 'label' => 'ویرایش تصویر (Edit Image)'],
                    ],
                    'default' => 'analyze_image',
                    'required' => true,
                    'depends_on' => ['resource' => 'image'],
                ],
                'operation_audio' => [
                    'type' => 'select',
                    'label' => 'عملیات صدا',
                    'options' => [
                        ['value' => 'analyze_audio', 'label' => 'تحلیل فایل صوتی (Analyze Audio)'],
                        ['value' => 'transcribe_audio', 'label' => 'تبدیل گفتار به متن (Transcribe)'],
                    ],
                    'default' => 'analyze_audio',
                    'required' => true,
                    'depends_on' => ['resource' => 'audio'],
                ],
                'operation_document' => [
                    'type' => 'select',
                    'label' => 'عملیات سند',
                    'options' => [
                        ['value' => 'analyze_document', 'label' => 'تحلیل سند و PDF (Analyze Document)'],
                    ],
                    'default' => 'analyze_document',
                    'required' => true,
                    'depends_on' => ['resource' => 'document'],
                ],
                'operation_file_search' => [
                    'type' => 'select',
                    'label' => 'عملیات مخزن فایل',
                    'options' => [
                        ['value' => 'create_store', 'label' => 'ایجاد مخزن جدید (Create Store)'],
                        ['value' => 'list_stores', 'label' => 'لیست مخازن (List Stores)'],
                        ['value' => 'upload_file_to_store', 'label' => 'آپلود فایل در مخزن (Upload File)'],
                        ['value' => 'query_store', 'label' => 'پرسش از مخزن (Query Store)'],
                        ['value' => 'delete_store', 'label' => 'حذف مخزن (Delete Store)'],
                    ],
                    'default' => 'list_stores',
                    'required' => true,
                    'depends_on' => ['resource' => 'file_search'],
                ],
                'operation_media_file' => [
                    'type' => 'select',
                    'label' => 'عملیات مدیا',
                    'options' => [
                        ['value' => 'upload_file', 'label' => 'آپلود فایل (Upload Media File)'],
                    ],
                    'default' => 'upload_file',
                    'required' => true,
                    'depends_on' => ['resource' => 'media_file'],
                ],
                'operation_video' => [
                    'type' => 'select',
                    'label' => 'عملیات ویدیو',
                    'options' => [
                        ['value' => 'analyze_video', 'label' => 'تحلیل ویدیو (Analyze Video)'],
                        ['value' => 'generate_video', 'label' => 'تولید سناریو ویدیو (Generate Video)'],
                        ['value' => 'download_video', 'label' => 'دانلود ویدیو (Download Video)'],
                    ],
                    'default' => 'analyze_video',
                    'required' => true,
                    'depends_on' => ['resource' => 'video'],
                ],

                'model' => [
                    'type' => 'model_select',
                    'label' => 'مدل هوش مصنوعی',
                    'required' => true,
                    'depends_on' => ['resource' => ['text', 'document']],
                ],
                'model_multimodal' => [
                    'type' => 'model_select',
                    'label' => 'مدل هوش مصنوعی',
                    'required' => true,
                    'depends_on' => ['resource' => 'audio'],
                ],
                'model_image' => [
                    'type' => 'model_select',
                    'label' => 'مدل هوش مصنوعی',
                    'required' => true,
                    'depends_on' => [
                        'resource' => 'image',
                        'operation_image' => 'analyze_image',
                    ],
                ],
                'image_generation_model' => [
                    'type' => 'select',
                    'label' => 'مدل تصویرساز',
                    'options' => [
                        ['value' => 'auto', 'label' => 'هوشمند (پیشنهادی)'],
                        ['value' => 'gemini-3.1-flash-image-preview', 'label' => 'Gemini 3.1 Flash Image'],
                        ['value' => 'gemini-2.5-flash-image', 'label' => 'Gemini 2.5 Flash Image'],
                        ['value' => 'imagen-4.0-generate-001', 'label' => 'Imagen 4.0'],
                        ['value' => 'imagen-3.0-generate-002', 'label' => 'Imagen 3.0'],
                    ],
                    'default' => 'auto',
                    'required' => true,
                    'depends_on' => [
                        'resource' => 'image',
                        'operation_image' => ['generate_image', 'edit_image'],
                    ],
                ],
                'model_video' => [
                    'type' => 'model_select',
                    'label' => 'مدل هوش مصنوعی',
                    'required' => true,
                    'depends_on' => [
                        'resource' => 'video',
                        'operation_video' => ['analyze_video', 'generate_video'],
                    ],
                ],
                'model_file_search' => [
                    'type' => 'model_select',
                    'label' => 'مدل هوش مصنوعی',
                    'required' => true,
                    'depends_on' => [
                        'resource' => 'file_search',
                        'operation_file_search' => 'query_store',
                    ],
                ],

                'image_input' => [
                    'type' => 'text',
                    'label' => 'فایل یا آدرس تصویر',
                    'required' => false,
                    'default' => '{{ input.data_url }}',
                    'placeholder' => '{{ input.data_url }} یا https://example.com/image.jpg',
                    'depends_on' => [
                        'resource' => 'image',
                        'operation_image' => ['analyze_image', 'edit_image'],
                    ],
                ],
                'audio_input' => [
                    'type' => 'text',
                    'label' => 'فایل یا آدرس صوتی',
                    'required' => false,
                    'default' => '{{ input.data_url }}',
                    'placeholder' => '{{ input.data_url }} یا https://example.com/voice.ogg',
                    'depends_on' => ['resource' => 'audio'],
                ],
                'document_input' => [
                    'type' => 'text',
                    'label' => 'فایل یا آدرس سند',
                    'required' => false,
                    'default' => '{{ input.data_url }}',
                    'placeholder' => '{{ input.data_url }} یا https://example.com/file.pdf',
                    'depends_on' => ['resource' => 'document'],
                ],
                'video_input' => [
                    'type' => 'text',
                    'label' => 'فایل یا آدرس ویدیو',
                    'required' => true,
                    'placeholder' => 'https://example.com/video.mp4 یا {{ input.data_url }}',
                    'depends_on' => [
                        'resource' => 'video',
                        'operation_video' => ['analyze_video', 'download_video'],
                    ],
                ],

                'prompt' => [
                    'type' => 'textarea',
                    'label' => 'پرامپت (Prompt)',
                    'required' => true,
                    'placeholder' => 'متن یا دستور خود را وارد کنید؛ مثال: {{ input.chatInput }}',
                    'depends_on' => ['resource' => ['text', 'image', 'document']],
                ],
                'audio_prompt' => [
                    'type' => 'textarea',
                    'label' => 'دستورالعمل (اختیاری)',
                    'required' => false,
                    'placeholder' => 'خالی = تحلیل یا پیاده‌سازی خودکار فایل صوتی',
                    'depends_on' => ['resource' => 'audio'],
                ],
                'video_prompt' => [
                    'type' => 'textarea',
                    'label' => 'پرامپت ویدیو (Prompt)',
                    'required' => true,
                    'placeholder' => 'سوال یا موضوع سناریوی ویدیویی را بنویسید...',
                    'depends_on' => [
                        'resource' => 'video',
                        'operation_video' => ['analyze_video', 'generate_video'],
                    ],
                ],

                'aspect_ratio' => [
                    'type' => 'select',
                    'label' => 'نسبت ابعاد (Aspect Ratio)',
                    'options' => [
                        ['value' => '1:1', 'label' => '1:1 (مربع)'],
                        ['value' => '16:9', 'label' => '16:9 (افقی)'],
                        ['value' => '9:16', 'label' => '9:16 (عمودی)'],
                        ['value' => '4:3', 'label' => '4:3 (استاندارد)'],
                        ['value' => '3:4', 'label' => '3:4 (پرتره)'],
                    ],
                    'default' => '1:1',
                    'depends_on' => [
                        'resource' => 'image',
                        'operation_image' => 'generate_image',
                    ],
                ],

                'store_display_name' => [
                    'type' => 'text',
                    'label' => 'نام مخزن (Display Name)',
                    'required' => true,
                    'placeholder' => 'پایگاه دانش پشتیبانی',
                    'depends_on' => [
                        'resource' => 'file_search',
                        'operation_file_search' => 'create_store',
                    ],
                ],
                'store_name' => [
                    'type' => 'text',
                    'label' => 'شناسه مخزن (Store ID)',
                    'required' => true,
                    'placeholder' => 'fileSearchStores/my-store-123',
                    'depends_on' => [
                        'resource' => 'file_search',
                        'operation_file_search' => ['upload_file_to_store', 'query_store', 'delete_store'],
                    ],
                ],
                'file_input_mode' => [
                    'type' => 'select',
                    'label' => 'نوع داده ورودی',
                    'options' => [
                        ['value' => 'binary_or_url', 'label' => 'فایل یا لینک (PDF، سند، خروجی تلگرام)'],
                        ['value' => 'text', 'label' => 'متن مستقیم'],
                    ],
                    'default' => 'binary_or_url',
                    'required' => true,
                    'depends_on' => [
                        'resource' => 'file_search',
                        'operation_file_search' => 'upload_file_to_store',
                    ],
                ],
                'store_file_source' => [
                    'type' => 'text',
                    'label' => 'فایل یا آدرس (File / URL)',
                    'required' => false,
                    'default' => '{{ input.data_url }}',
                    'placeholder' => '{{ input.data_url }} یا https://example.com/file.pdf',
                    'depends_on' => [
                        'resource' => 'file_search',
                        'operation_file_search' => 'upload_file_to_store',
                        'file_input_mode' => 'binary_or_url',
                    ],
                ],
                'file_content' => [
                    'type' => 'textarea',
                    'label' => 'متن محتوا',
                    'required' => true,
                    'placeholder' => 'متن مورد نظر برای ذخیره در مخزن را وارد کنید...',
                    'depends_on' => [
                        'resource' => 'file_search',
                        'operation_file_search' => 'upload_file_to_store',
                        'file_input_mode' => 'text',
                    ],
                ],
                'document_title' => [
                    'type' => 'text',
                    'label' => 'عنوان فایل (اختیاری)',
                    'required' => false,
                    'placeholder' => 'راهنمای کاربران',
                    'depends_on' => [
                        'resource' => 'file_search',
                        'operation_file_search' => 'upload_file_to_store',
                    ],
                ],
                'store_query' => [
                    'type' => 'textarea',
                    'label' => 'متن پرسش (Query)',
                    'required' => true,
                    'placeholder' => '{{ input.chatInput }}',
                    'depends_on' => [
                        'resource' => 'file_search',
                        'operation_file_search' => 'query_store',
                    ],
                ],

                'media_source' => [
                    'type' => 'text',
                    'label' => 'فایل یا آدرس مدیا',
                    'required' => false,
                    'default' => '{{ input.data_url }}',
                    'placeholder' => '{{ input.data_url }} یا https://example.com/media.pdf',
                    'depends_on' => ['resource' => 'media_file'],
                ],
                'media_display_name' => [
                    'type' => 'text',
                    'label' => 'نام فایل (اختیاری)',
                    'required' => false,
                    'placeholder' => 'uploaded_file',
                    'depends_on' => ['resource' => 'media_file'],
                ],

                'system_instruction' => [
                    'type' => 'textarea',
                    'label' => 'دستورالعمل سیستمی (System Instruction)',
                    'placeholder' => 'مثال: پاسخ‌ها را کوتاه و به زبان فارسی بده.',
                    'advanced' => true,
                    'depends_on' => ['resource' => ['text', 'document', 'file_search']],
                ],
                'file_search_store_name' => [
                    'type' => 'text',
                    'label' => 'شناسه مخزن فایل (RAG Store ID)',
                    'placeholder' => 'fileSearchStores/my-store-123',
                    'advanced' => true,
                    'depends_on' => ['resource' => 'text'],
                ],
                'temperature' => [
                    'type' => 'number',
                    'label' => 'میزان خلاقیت (Temperature)',
                    'default' => 0.7,
                    'min' => 0,
                    'max' => 2,
                    'advanced' => true,
                    'depends_on' => ['resource' => ['text', 'image', 'audio', 'document', 'file_search']],
                ],
                'number_of_images' => [
                    'type' => 'number',
                    'label' => 'تعداد تصاویر',
                    'default' => 1,
                    'min' => 1,
                    'max' => 4,
                    'integer' => true,
                    'advanced' => true,
                    'depends_on' => [
                        'resource' => 'image',
                        'operation_image' => 'generate_image',
                    ],
                ],
                'output_format' => [
                    'type' => 'select',
                    'label' => 'فرمت خروجی تصویر',
                    'options' => [
                        ['value' => 'image/png', 'label' => 'PNG'],
                        ['value' => 'image/jpeg', 'label' => 'JPEG'],
                    ],
                    'default' => 'image/png',
                    'advanced' => true,
                    'depends_on' => [
                        'resource' => 'image',
                        'operation_image' => ['generate_image', 'edit_image'],
                    ],
                ],
                'audio_mime_type' => [
                    'type' => 'select',
                    'label' => 'فرمت صوت (MIME Type)',
                    'options' => [
                        ['value' => 'audio/ogg', 'label' => 'OGG (audio/ogg)'],
                        ['value' => 'audio/mp3', 'label' => 'MP3 (audio/mp3)'],
                        ['value' => 'audio/wav', 'label' => 'WAV (audio/wav)'],
                        ['value' => 'audio/m4a', 'label' => 'M4A (audio/m4a)'],
                        ['value' => 'audio/aac', 'label' => 'AAC (audio/aac)'],
                    ],
                    'default' => 'audio/ogg',
                    'advanced' => true,
                    'depends_on' => ['resource' => 'audio'],
                ],
                'document_mime_type' => [
                    'type' => 'select',
                    'label' => 'فرمت سند (MIME Type)',
                    'options' => [
                        ['value' => 'application/pdf', 'label' => 'PDF (application/pdf)'],
                        ['value' => 'text/plain', 'label' => 'Text (text/plain)'],
                        ['value' => 'text/csv', 'label' => 'CSV (text/csv)'],
                        ['value' => 'text/markdown', 'label' => 'Markdown (text/markdown)'],
                        ['value' => 'text/html', 'label' => 'HTML (text/html)'],
                    ],
                    'default' => 'application/pdf',
                    'advanced' => true,
                    'depends_on' => ['resource' => 'document'],
                ],
                'store_file_mime_type' => [
                    'type' => 'select',
                    'label' => 'فرمت فایل مخزن (MIME Type)',
                    'options' => [
                        ['value' => 'auto', 'label' => 'تشخیص خودکار'],
                        ['value' => 'application/pdf', 'label' => 'PDF (application/pdf)'],
                        ['value' => 'text/plain', 'label' => 'Text (text/plain)'],
                        ['value' => 'text/markdown', 'label' => 'Markdown (text/markdown)'],
                        ['value' => 'text/csv', 'label' => 'CSV (text/csv)'],
                        ['value' => 'text/html', 'label' => 'HTML (text/html)'],
                        ['value' => 'application/json', 'label' => 'JSON (application/json)'],
                    ],
                    'default' => 'auto',
                    'advanced' => true,
                    'depends_on' => [
                        'resource' => 'file_search',
                        'operation_file_search' => 'upload_file_to_store',
                    ],
                ],
                'media_mime_type' => [
                    'type' => 'text',
                    'label' => 'فرمت فایل (MIME Type)',
                    'placeholder' => 'application/pdf',
                    'advanced' => true,
                    'depends_on' => ['resource' => 'media_file'],
                ],
                'video_mime_type' => [
                    'type' => 'select',
                    'label' => 'فرمت ویدیو (MIME Type)',
                    'options' => [
                        ['value' => 'video/mp4', 'label' => 'MP4 (video/mp4)'],
                        ['value' => 'video/quicktime', 'label' => 'MOV (video/quicktime)'],
                        ['value' => 'video/webm', 'label' => 'WEBM (video/webm)'],
                    ],
                    'default' => 'video/mp4',
                    'advanced' => true,
                    'depends_on' => [
                        'resource' => 'video',
                        'operation_video' => 'analyze_video',
                    ],
                ],
            ],
            presets: [
                [
                    'key' => 'message_model',
                    'name' => 'Message a model',
                    'description' => 'ارسال پرامپت متنی و دریافت پاسخ از مدل‌های Gemini.',
                    'config' => ['resource' => 'text', 'operation' => 'message_model'],
                ],
                [
                    'key' => 'analyze_image',
                    'name' => 'Analyze an image',
                    'description' => 'دریافت تصویر و استخراج جزئیات یا پاسخ به سوالات بر اساس آن.',
                    'config' => ['resource' => 'image', 'operation_image' => 'analyze_image'],
                ],
                [
                    'key' => 'generate_image',
                    'name' => 'Generate an image',
                    'description' => 'تولید تصویر با کیفیت بالا از طریق پرامپت.',
                    'config' => ['resource' => 'image', 'operation_image' => 'generate_image'],
                ],
                [
                    'key' => 'edit_image',
                    'name' => 'Edit an image',
                    'description' => 'ویرایش تصویر مرجع با اعمال دستورالعمل جدید.',
                    'config' => ['resource' => 'image', 'operation_image' => 'edit_image'],
                ],
                [
                    'key' => 'analyze_audio',
                    'name' => 'Analyze audio',
                    'description' => 'شنیدن فایل صوتی و پاسخ به سوالات یا خلاصه‌سازی آن.',
                    'config' => ['resource' => 'audio', 'operation_audio' => 'analyze_audio'],
                ],
                [
                    'key' => 'transcribe_audio',
                    'name' => 'Transcribe a recording',
                    'description' => 'پیاده‌سازی و تبدیل کامل گفتار و فایل‌های صوتی به متن.',
                    'config' => ['resource' => 'audio', 'operation_audio' => 'transcribe_audio'],
                ],
                [
                    'key' => 'analyze_document',
                    'name' => 'Analyze document',
                    'description' => 'مطالعه و تحلیل فایل‌های PDF، متن و اسناد اداری.',
                    'config' => ['resource' => 'document', 'operation_document' => 'analyze_document'],
                ],
                [
                    'key' => 'upload_media_file',
                    'name' => 'Upload a media file',
                    'description' => 'آپلود فایل در Google Files API برای استفاده در نودهای بعدی.',
                    'config' => ['resource' => 'media_file', 'operation_media_file' => 'upload_file'],
                ],
                [
                    'key' => 'create_search_store',
                    'name' => 'Create a File Search store',
                    'description' => 'ساخت مخزن جدید برای ذخیره و جستجوی معنایی فایل‌ها.',
                    'config' => ['resource' => 'file_search', 'operation_file_search' => 'create_store'],
                ],
                [
                    'key' => 'list_search_stores',
                    'name' => 'List all File Search stores',
                    'description' => 'دریافت فهرست تمام مخازن ذخیره‌سازی فایل ایجاد شده.',
                    'config' => ['resource' => 'file_search', 'operation_file_search' => 'list_stores'],
                ],
                [
                    'key' => 'upload_to_search_store',
                    'name' => 'Upload a file to a File Search store',
                    'description' => 'آپلود فایل (PDF، سند، خروجی تلگرام) یا متن در مخزن جستجوی فایل.',
                    'config' => [
                        'resource' => 'file_search',
                        'operation_file_search' => 'upload_file_to_store',
                        'file_input_mode' => 'binary_or_url',
                    ],
                ],
                [
                    'key' => 'query_search_store',
                    'name' => 'Query a File Search store',
                    'description' => 'پرسش و جستجوی هوشمند از روی فایل‌های داخل مخزن.',
                    'config' => ['resource' => 'file_search', 'operation_file_search' => 'query_store'],
                ],
                [
                    'key' => 'delete_search_store',
                    'name' => 'Delete a File Search store',
                    'description' => 'حذف مخزن جستجوی فایل و اسناد مربوط به آن.',
                    'config' => ['resource' => 'file_search', 'operation_file_search' => 'delete_store'],
                ],
                [
                    'key' => 'analyze_video',
                    'name' => 'Analyze video',
                    'description' => 'مشاهده و درک ویدیو و پاسخ به سوالات مربوط به محتوای آن.',
                    'config' => ['resource' => 'video', 'operation_video' => 'analyze_video'],
                ],
                [
                    'key' => 'generate_video',
                    'name' => 'Generate a video',
                    'description' => 'تولید ساختار صحنه‌ها، سناریو و استوری‌بورد ویدیویی بر اساس پرامپت.',
                    'config' => ['resource' => 'video', 'operation_video' => 'generate_video'],
                ],
                [
                    'key' => 'download_video',
                    'name' => 'Download a video',
                    'description' => 'دانلود فایل ویدیویی از آدرس اینترنتی به صورت خروجی مستقیم.',
                    'config' => ['resource' => 'video', 'operation_video' => 'download_video'],
                ],
            ],
            color: 'blue',
        );
    }
}
