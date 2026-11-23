<?php

namespace App\Templates\Catalog;

use App\Templates\Contracts\WorkflowTemplateContract;
use App\Templates\DTOs\WorkflowTemplate;
use App\Templates\Enums\TemplateCategory;

final class TelegramAiSupportBotTemplate implements WorkflowTemplateContract
{
    public static function make(): WorkflowTemplate
    {
        return new WorkflowTemplate(
            slug: 'telegram-24-7-ai-support-bot',
            title: 'پاسخ‌گوی شبانه‌روزی تلگرام',
            subtitle: 'به سؤال‌های تکراری مشتری خودش جواب می‌دهد و هرچه نتوانست را همراه با متن گفتگو در جدول برای شما نگه می‌دارد.',
            seoTitle: 'ساخت ربات پاسخ‌گوی شبانه‌روزی تلگرام با هوش مصنوعی و ثبت در جدول',
            seoDescription: 'با این ورکفلوی آماده تمشک فلو، یک پشتیبان هوشمند ۲۴ ساعته برای ربات تلگرام بسازید که به سوالات پرتکرار مشتریان پاسخ می‌دهد و تمام گفتگوها را در جدول ذخیره می‌کند.',
            category: TemplateCategory::CustomerSupport,
            tags: ['پشتیبانی مشتری', 'هوش مصنوعی', 'تلگرام', 'OpenRouter', 'پاسخ‌گوی خودکار'],
            difficulty: 'ساده',
            setupMinutes: 3,
            problem: 'بیشتر پیام‌هایی که به پشتیبانی تلگرام می‌رسد چند سؤال تکراری است: ساعت کار، هزینه و زمان ارسال، نحوه ثبت سفارش یا آدرس و راه‌های ارتباطی. جواب‌دادن به این پیام‌ها سخت نیست، اما وقت زیادی از تیم می‌گیرد و شب‌ها و روزهای تعطیل هم متوقف نمی‌شود. مشتری‌ای که ساعت یازده شب سؤال می‌پرسد و تا فردا صبح جوابی نمی‌گیرد، معمولاً تا فردا صبر نمی‌کند.',
            outcomes: [
                'هر پیام تازه‌ای که به ربات تلگرام ارسال شود را در لحظه و به‌صورت ۲۴ ساعته دریافت می‌کند.',
                'با هوش مصنوعی (OpenRouter) و بر اساس اطلاعات کسب‌وکار شما، یک پاسخ فارسی کوتاه و دقیق می‌نویسد و همان‌جا در تلگرام برای مشتری می‌فرستد.',
                'متن سؤال مشتری و پاسخ داده‌شده را در جدول داخلی ثبت می‌کند تا بعداً ببینید مشتری‌ها بیشتر چه می‌پرسند یا موارد نیازمند پیگیری را بررسی کنید.',
            ],
            audience: 'مناسب برای فروشگاه‌ها، تیم‌های خدماتی و کسب‌وکارهایی که روزانه پیام‌های تکراری در تلگرام دریافت می‌کنند و وقت یک نفر صرف جواب‌دادن مداوم به سؤالات مشابه می‌شود.',
            setupSteps: [
                [
                    'title' => 'اتصال ربات تلگرام',
                    'description' => 'در نودهای شروع‌کننده و ارسال پیام تلگرام، اعتبارنامه ربات تلگرام خود (توکن دریافتی از BotFather) را انتخاب کنید.',
                ],
                [
                    'title' => 'انتخاب کلید OpenRouter و شخصی‌سازی اطلاعات کسب‌وکار',
                    'description' => 'در نود OpenRouter Chat Model اعتبارنامه خود را انتخاب کنید و در نود AI Agent بخش «دستورالعمل‌های سیستمی» را با ساعت کاری، هزینه ارسال و قوانین کسب‌وکار خودتان جایگزین نمایید.',
                ],
                [
                    'title' => 'انتشار و فعال‌سازی ربات',
                    'description' => 'جدول داده «گفتگوهای تلگرام» در اولین اجرا خودکار ساخته می‌شود. کافی است روی دکمه «انتشار» کلیک کنید تا وب‌هوک تلگرام ثبت شود و ربات ۲۴ ساعته پاسخ‌گو باشد.',
                ],
            ],
            faqs: [
                [
                    'q' => 'اگر مشتری سؤالی بپرسد که جوابش در دستورالعمل ربات نباشد چه اتفاقی می‌افتد؟',
                    'a' => 'در دستورالعمل سیستمی ایجنت مشخص شده که هرگز اطلاعات حدسی ندهد؛ در این مواقع محترمانه به مشتری اعلام می‌کند که پیامش ثبت شده و همکاران پشتیبانی به‌زودی پاسخ می‌دهند. همزمان سؤال او در جدول ذخیره می‌شود تا بررسی کنید.',
                ],
                [
                    'q' => 'آیا می‌توانم به‌جای OpenRouter از مدل رایگان Gemini یا ChatGPT استفاده کنم؟',
                    'a' => 'بله؛ در ویرایشگر می‌توانید نود OpenRouter Chat Model را حذف کنید و با کلیک روی ورودی Chat Model زیر ایجنت، نود Google Gemini یا OpenAI را جایگزین نمایید.',
                ],
                [
                    'q' => 'آیا ربات پیام‌های قبلی همان مشتری را به خاطر می‌سپارد؟',
                    'a' => 'بله؛ نود حافظه (Simple Memory) در این ورکفلو بر اساس شناسه چت هر کاربر تنظیم شده است تا ایجنت ۵ پیام آخر هر مشتری را به صورت مجزا در خاطر داشته باشد.',
                ],
                [
                    'q' => 'آیا برای روشن ماندن ربات نیاز به سرور مجازی یا باز بودن مرورگر دارم؟',
                    'a' => 'خیر؛ پس از کلیک روی دکمه «انتشار»، وب‌هوک ربات تلگرام روی زیرساخت ابری تمشک فلو فعال می‌شود و بدون نیاز به سرور یا باز بودن سایت، شبانه‌روزی کار می‌کند.',
                ],
            ],
            requiredApps: [
                [
                    'key' => 'telegram',
                    'name' => 'تلگرام',
                    'icon' => 'brand:telegram',
                    'role' => 'دریافت پیام مشتری و ارسال پاسخ',
                ],
                [
                    'key' => 'openrouter',
                    'name' => 'OpenRouter',
                    'icon' => 'brand:openrouter',
                    'role' => 'تولید پاسخ هوشمند فارسی',
                ],
                [
                    'key' => 'data_table',
                    'name' => 'جدول داده داخلی',
                    'icon' => 'table',
                    'role' => 'ثبت تاریخچه سؤال و جواب‌ها',
                ],
            ],
            relatedDocSlugs: [
                'ai-agent-and-memory',
                'credentials-setup',
                'service-actions',
            ],
            relatedTemplateSlugs: [
                'woocommerce-order-telegram-table',
            ],
            workflowName: 'پاسخ‌گوی شبانه‌روزی تلگرام',
            workflowDescription: 'پاسخ‌گویی خودکار ۲۴ ساعته به سوالات پرتکرار مشتریان در تلگرام با هوش مصنوعی و ثبت گفتگوها در جدول.',
            nodes: [
                [
                    'key' => 'tg_trigger',
                    'type' => 'trigger.telegram',
                    'name' => 'دریافت پیام تلگرام',
                    'config' => [
                        'updates' => ['message'],
                    ],
                ],
                [
                    'key' => 'support_agent',
                    'type' => 'ai.agent',
                    'name' => 'پشتیبان هوشمند',
                    'config' => [
                        'prompt' => '{{ input.text }}',
                        'system_prompt' => implode("\n", [
                            'تو دستیار پشتیبانی شبانه‌روزی کسب‌وکار ما در تلگرام هستی. همیشه به زبان فارسی، کوتاه، صمیمی و محترمانه پاسخ بده.',
                            '',
                            'اطلاعات پایه برای پاسخ به سوالات پرتکرار:',
                            '- ساعت پاسخ‌گویی تلفنی و حضوری: شنبه تا چهارشنبه از ساعت ۹ صبح تا ۶ عصر (پنجشنبه‌ها تا ساعت ۱۴).',
                            '- هزینه و نحوه ارسال: ارسال سفارش‌ها در تهران با پیک (۱ روز کاری) و به شهرستان‌ها با پست پیشتاز (۲ تا ۴ روز کاری) انجام می‌شود.',
                            '- پیگیری سفارش: برای بررسی وضعیت سفارش، از مشتری بخواه شماره سفارش یا شماره موبایل ثبت‌شده هنگام خرید را ارسال کند.',
                            '',
                            'قانون مهم:',
                            'اگر پاسخ سوال مشتری در اطلاعات بالا نبود یا نیاز به بررسی انسانی داشت، اصلاً پاسخ حدسی نده و بگو: «پیام شما ثبت شد و همکاران من در اولین ساعت کاری بررسی می‌کنند و به شما اطلاع می‌دهند.»',
                        ]),
                    ],
                ],
                [
                    'key' => 'openrouter_model',
                    'type' => 'ai.model.openrouter',
                    'name' => 'مدل OpenRouter',
                    'config' => [
                        'model' => 'openai/gpt-6.1-sol',
                        'temperature' => 0.3,
                    ],
                ],
                [
                    'key' => 'chat_memory',
                    'type' => 'ai.memory.simple',
                    'name' => 'حافظه گفتگو',
                    'config' => [
                        'memory_size' => 5,
                        'session_key' => '{{ input.chat_id }}',
                    ],
                ],
                [
                    'key' => 'tg_reply',
                    'type' => 'action.telegram',
                    'name' => 'ارسال پاسخ در تلگرام',
                    'config' => [
                        'operation' => 'send_message',
                        'chat_id' => '{{ input.chat_id }}',
                        'text' => '{{ input.response }}',
                        'reply_to_message_id' => '{{ input.message_id }}',
                    ],
                ],
                [
                    'key' => 'conversations_table',
                    'type' => 'transform.data_table',
                    'name' => 'ثبت در جدول گفتگوها',
                    'config' => [
                        'operation' => 'insert_row',
                        'table' => 'گفتگوهای تلگرام',
                        'data_mode' => 'manual',
                        'row_fields' => [
                            [
                                'name' => 'نام مشتری',
                                'type' => 'string',
                                'value' => '{{ input.first_name }}',
                            ],
                            [
                                'name' => 'نام کاربری',
                                'type' => 'string',
                                'value' => '{{ input.username }}',
                            ],
                            [
                                'name' => 'شناسه چت',
                                'type' => 'string',
                                'value' => '{{ input.chat_id }}',
                            ],
                            [
                                'name' => 'سوال مشتری',
                                'type' => 'string',
                                'value' => '{{ input.text }}',
                            ],
                            [
                                'name' => 'پاسخ ربات',
                                'type' => 'string',
                                'value' => '{{ input.response }}',
                            ],
                        ],
                    ],
                ],
            ],
            edges: [
                [
                    'source' => 'tg_trigger',
                    'target' => 'support_agent',
                    'sourceHandle' => 'main',
                    'targetHandle' => 'main',
                ],
                [
                    'source' => 'openrouter_model',
                    'target' => 'support_agent',
                    'sourceHandle' => 'chat_model',
                    'targetHandle' => 'chat_model',
                ],
                [
                    'source' => 'chat_memory',
                    'target' => 'support_agent',
                    'sourceHandle' => 'memory',
                    'targetHandle' => 'memory',
                ],
                [
                    'source' => 'support_agent',
                    'target' => 'tg_reply',
                    'sourceHandle' => 'main',
                    'targetHandle' => 'main',
                ],
                [
                    'source' => 'support_agent',
                    'target' => 'conversations_table',
                    'sourceHandle' => 'main',
                    'targetHandle' => 'main',
                ],
            ],
            featured: true,
        );
    }
}
