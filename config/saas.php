<?php

return [
    'enabled' => (bool) env('SAAS_MODE', false),

    'default_plan_slug' => 'free',

    'grace_period_days' => 3,

    'renewal_window_days' => 10,

    'zibal' => [
        'sandbox' => (bool) env('ZIBAL_SANDBOX', true),
        'merchant_id' => (string) env('ZIBAL_MERCHANT_ID', 'zibal'),
        'currency' => (string) env('ZIBAL_CURRENCY', 'IRT'),
    ],

    'enamad' => [
        'id' => (string) env('ENAMAD_ID', ''),
        'code' => (string) env('ENAMAD_CODE', ''),
        'local_logo' => (string) env('ENAMAD_LOCAL_LOGO', 'images/enamad.webp'),
    ],

    'plans' => [
        [
            'slug' => 'free',
            'name' => 'رایگان',
            'description' => 'برای امتحان تمشک و ساخت اتوماسیون‌های شخصی',
            'monthly_price' => 0,
            'yearly_price' => 0,
            'max_monthly_runs' => 500,
            'max_active_workflows' => 2,
            'features' => [
                '۵۰۰ اجرا در ماه',
                'تا ۲ ورک‌فلو فعال هم‌زمان',
                'ساخت تا ۱۰ ورک‌فلو',
            ],
            'sort_order' => 1,
        ],
        [
            'slug' => 'pro',
            'name' => 'پرو',
            'description' => 'برای فریلنسرها، سازندگان ربات و کسب‌وکارهای در حال رشد',
            'monthly_price' => 390000,
            'yearly_price' => 3900000,
            'max_monthly_runs' => 10000,
            'max_active_workflows' => 10,
            'features' => [
                '۱۰٬۰۰۰ اجرا در ماه',
                'تا ۱۰ ورک‌فلو فعال هم‌زمان',
                'ساخت تا ۵۰ ورک‌فلو',
            ],
            'sort_order' => 2,
        ],
        [
            'slug' => 'business',
            'name' => 'بیزینس',
            'description' => 'برای تیم‌ها و پروژه‌هایی که نمی‌خواهند نگران سقف اجرا باشند',
            'monthly_price' => 990000,
            'yearly_price' => 9900000,
            'max_monthly_runs' => null,
            'max_active_workflows' => null,
            'features' => [
                'اجرای نامحدود در ماه',
                'ورک‌فلو فعال نامحدود',
                'ساخت ورک‌فلو بدون محدودیت تعداد',
                'پشتیبانی اختصاصی',
            ],
            'sort_order' => 3,
        ],
    ],
];
