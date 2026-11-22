<?php

namespace App\Templates\Catalog;

use App\Templates\Contracts\WorkflowTemplateContract;
use App\Templates\DTOs\WorkflowTemplate;
use App\Templates\Enums\TemplateCategory;

final class WooCommerceOrderTelegramTableTemplate implements WorkflowTemplateContract
{
    public static function make(): WorkflowTemplate
    {
        return new WorkflowTemplate(
            slug: 'woocommerce-order-telegram-table',
            title: 'هیچ سفارشی را از دست ندهید',
            subtitle: 'اطلاع‌رسانی آنی هر سفارش جدید ووکامرس در گروه تلگرام تیم و ثبت خودکار آن در جدول سفارش‌ها.',
            seoTitle: 'ارسال خودکار سفارش‌های ووکامرس به تلگرام و ثبت در جدول',
            seoDescription: 'با این ورکفلوی آماده تمشک فلو، هر سفارش جدید ووکامرس را در لحظه به گروه تلگرام تیم بفرستید و همزمان در جدول سفارش‌ها ذخیره کنید — بدون کدنویسی و بدون نیاز به سرور.',
            category: TemplateCategory::SalesOrders,
            tags: ['فروش و سفارش', 'ووکامرس', 'تلگرام', 'مدیریت فروشگاه'],
            difficulty: 'ساده',
            setupMinutes: 3,
            problem: 'وقتی فروشگاه تازه راه افتاده، سر زدن دستی به پیشخوان وردپرس کار را راه می‌اندازد؛ اما به محض بالا رفتن تعداد سفارش‌های روزانه، چک کردن مداوم پنل دیگر جواب نمی‌دهد. یا پنل بسته است و سفارش ساعت‌ها بدون پیگیری می‌ماند، یا تیم بسته‌بندی و ارسال به‌موقع خبردار نمی‌شوند. نتیجه مشخص است: تاخیر در آماده‌سازی، کاهش اعتماد خریدار و از دست رفتن مشتری برگشتی.',
            outcomes: [
                'به محض ثبت سفارش تازه در ووکامرس، ورکفلو به‌صورت خودکار و بدون نیاز به باز نگه داشتن پیشخوان وردپرس فعال می‌شود.',
                'پیامی خوانا شامل شماره سفارش، نام خریدار، شماره تماس و مبلغ نهایی در لحظه به گروه یا کانال تلگرام تیم ارسال می‌شود.',
                'یک ردیف ساختاریافته از اطلاعات همان سفارش در جدول داده داخلی تمشک فلو ثبت می‌شود تا گزارش‌گیری و مرور فروش ماهانه همیشه در دسترس باشد.',
            ],
            audience: 'مناسب برای فروشگاه‌های ووکامرسی که روزانه چند سفارش دارند و بیش از یک نفر درگیر پیگیری، بسته‌بندی یا ارسال سفارش‌ها هستند. اگر به‌تازگی فروشگاه را راه‌اندازی کرده‌اید و فعلاً هفته‌ای یکی‌دو سفارش دارید، شاید هنوز به این خودکارسازی نیاز فوری نداشته باشید.',
            setupSteps: [
                [
                    'title' => 'اتصال فروشگاه ووکامرس',
                    'description' => 'در نود WooCommerce Trigger، اعتبارنامه فروشگاه خود (آدرس سایت و کلیدهای REST API ووکامرس) را انتخاب کنید تا رویداد ثبت سفارش جدید به‌صورت خودکار شنود شود.',
                ],
                [
                    'title' => 'تنظیم ربات و گروه تلگرام تیم',
                    'description' => 'در نود Telegram، ربات خود را متصل کنید و شناسه گروه یا کانال تیم (Chat ID) را وارد نمایید. متن پیام از قبل با متغیرهای سفارش آماده شده و قابل ویرایش است.',
                ],
                [
                    'title' => 'تست و انتشار ورکفلو',
                    'description' => 'جدول داده داخلی «سفارش ها» هنگام اولین اجرا به‌صورت خودکار ساخته می‌شود. کافی است روی دکمه «انتشار» کلیک کنید تا از این لحظه همه سفارش‌ها ۲۴ ساعته ثبت و اطلاع‌رسانی شوند.',
                ],
            ],
            faqs: [
                [
                    'q' => 'آیا برای کار کردن این ورکفلو لازم است افزونه خاصی روی وردپرس نصب کنم؟',
                    'a' => 'خیر؛ این ورکفلو مستقیماً از REST API و وب‌هوک رسمی خود ووکامرس استفاده می‌کند. کافی است از بخش پیکربندی ووکامرس یک کلید API با دسترسی خواندن/نوشتن بسازید و در تمشک فلو ثبت کنید.',
                ],
                [
                    'q' => 'آیا بدون نیاز به هاست خارجی یا پروکسی، پیام‌های تلگرام ارسال می‌شوند؟',
                    'a' => 'بله؛ زیرساخت تمشک فلو کاملاً ابری است و ارتباط با وب‌هوک ووکامرس و ربات تلگرام به‌صورت مستقیم و ۲۴ ساعته بدون نیاز به سرور مجازی یا تنظیم پروکسی انجام می‌شود.',
                ],
                [
                    'q' => 'اگر بخواهم سفارش‌ها به‌جای جدول داخلی در گوگل شیت ذخیره شوند چطور؟',
                    'a' => 'خیلی راحت! پس از باز شدن ورکفلو در ویرایشگر، می‌توانید نود Data table را حذف کرده و نود Google Sheets (عملیات Append Row) را جایگزین آن کنید یا حتی هر دو را کنار هم داشته باشید.',
                ],
                [
                    'q' => 'آیا می‌توانم فقط سفارش‌های پرداخت‌شده یا بالای یک مبلغ خاص را به تلگرام بفرستم؟',
                    'a' => 'بله؛ کافی است یک نود If Condition بین شروع‌کننده ووکامرس و نود تلگرام قرار دهید و شرط دلخواه (مثلاً وضعیت سفارش یا حداقل مبلغ) را روی آن تنظیم کنید.',
                ],
            ],
            requiredApps: [
                [
                    'key' => 'woocommerce',
                    'name' => 'ووکامرس',
                    'icon' => 'brand:woocommerce',
                    'role' => 'دریافت آنی سفارش جدید',
                ],
                [
                    'key' => 'telegram',
                    'name' => 'تلگرام',
                    'icon' => 'brand:telegram',
                    'role' => 'ارسال اعلان به گروه تیم',
                ],
                [
                    'key' => 'data_table',
                    'name' => 'جدول داده داخلی',
                    'icon' => 'table',
                    'role' => 'ثبت سطر سفارش و آمار',
                ],
            ],
            relatedDocSlugs: [
                'first-workflow',
                'credentials-setup',
                'service-actions',
            ],
            relatedTemplateSlugs: [],
            workflowName: 'اطلاع سفارش ووکامرس در تلگرام و جدول',
            workflowDescription: 'دریافت آنی سفارش‌های جدید ووکامرس، ارسال پیام به گروه تلگرام تیم و ثبت در جدول داده داخلی.',
            nodes: [
                [
                    'key' => 'wc_trigger',
                    'type' => 'trigger.woocommerce',
                    'name' => 'سفارش جدید ووکامرس',
                    'config' => [
                        'event' => 'order.created',
                    ],
                ],
                [
                    'key' => 'tg_notify',
                    'type' => 'action.telegram',
                    'name' => 'اعلان در تلگرام تیم',
                    'config' => [
                        'operation' => 'send_message',
                        'chat_id' => '',
                        'disable_preview' => '1',
                        'text' => implode("\n", [
                            '🛒 سفارش جدید ثبت شد (شماره {{ input.number }})',
                            '',
                            '👤 مشتری: {{ input.billing.first_name }} {{ input.billing.last_name }}',
                            '📞 موبایل: {{ input.billing.phone }}',
                            '💳 مبلغ کل: {{ input.total }} {{ input.currency }}',
                            '📌 وضعیت: {{ input.status }}',
                            '🧾 روش پرداخت: {{ input.payment_method_title }}',
                        ]),
                    ],
                ],
                [
                    'key' => 'orders_table',
                    'type' => 'transform.data_table',
                    'name' => 'ثبت در جدول سفارش ها',
                    'config' => [
                        'operation' => 'insert_row',
                        'table' => 'سفارش ها',
                        'data_mode' => 'manual',
                        'row_fields' => [
                            [
                                'name' => 'شماره سفارش',
                                'type' => 'string',
                                'value' => '{{ input.number }}',
                            ],
                            [
                                'name' => 'نام مشتری',
                                'type' => 'string',
                                'value' => '{{ input.billing.first_name }} {{ input.billing.last_name }}',
                            ],
                            [
                                'name' => 'موبایل',
                                'type' => 'string',
                                'value' => '{{ input.billing.phone }}',
                            ],
                            [
                                'name' => 'مبلغ',
                                'type' => 'number',
                                'value' => '{{ input.total }}',
                            ],
                            [
                                'name' => 'وضعیت',
                                'type' => 'string',
                                'value' => '{{ input.status }}',
                            ],
                            [
                                'name' => 'تاریخ ثبت',
                                'type' => 'string',
                                'value' => '{{ input.date_created }}',
                            ],
                        ],
                    ],
                ],
            ],
            edges: [
                [
                    'source' => 'wc_trigger',
                    'target' => 'tg_notify',
                    'sourceHandle' => 'main',
                    'targetHandle' => 'main',
                ],
                [
                    'source' => 'wc_trigger',
                    'target' => 'orders_table',
                    'sourceHandle' => 'main',
                    'targetHandle' => 'main',
                ],
            ],
            featured: true,
        );
    }
}
