@php
    $siteName = config('app.site_name') ?: config('app.name', 'تمشک فلو');
    $pageTitle = $siteName . ' | پلتفرم ابری اتوماسیون گردش‌کار و ساخت ایجنت هوش مصنوعی';
    $pageDescription = 'تمشک فلو، پلتفرم ابری و بصری اتوماسیون گردش‌کار و ساخت ایجنت‌های هوش مصنوعی بدون کدنویسی؛ پیام‌رسان‌ها، سرویس‌ها، دیتابیس‌ها و وب‌سرویس‌ها را در چند دقیقه به هم متصل کنید.';
    $homeUrl = route('home');
    $logoUrl = asset('images/logo/favicon.png');
    $previewImage = is_file(public_path('images/landing/tameshk-canvas-dark.webp'))
        ? asset('images/landing/tameshk-canvas-dark.webp')
        : $logoUrl;

    $faqs = [
        [
            'q' => 'برای ساخت ورکفلو در تمشک فلو باید برنامه‌نویسی بلد باشم؟',
            'a' => 'خیر. تمام مراحل طراحی، اتصال نودها و تعریف شرط‌ها به‌صورت کاملاً بصری انجام می‌شود و می‌توانید بدون درگیر شدن با کد، فرایندهای خود را خودکار کنید.',
        ],
        [
            'q' => 'برای استفاده از تمشک فلو نیاز به خرید سرور (VPS) دارم؟',
            'a' => 'خیر، نیازی به خرید سرور، نصب داکر یا تنظیمات زیرساخت ندارید. تمشک فلو به‌صورت ابری اجرا می‌شود و بلافاصله پس از ثبت‌نام می‌توانید ورکفلوهای خود را بسازید و فعال کنید.',
        ],
        [
            'q' => 'امنیت کلیدهای API و حساب‌های متصل‌شده چگونه تأمین می‌شود؟',
            'a' => 'تمامی کلیدهای دسترسی و توکن‌های شما پیش از ذخیره در دیتابیس رمزنگاری (Encrypt) می‌شوند و به‌صورت کاملاً ایزوله، صرفاً در لحظه اجرای ورکفلوهای خودتان مورد استفاده قرار می‌گیرند.',
        ],
        [
            'q' => 'چطور می‌توانم پیش از خرید اشتراک، تمشک فلو را امتحان کنم؟',
            'a' => 'پلن رایگان تمشک فلو بدون محدودیت زمانی فعال است و هر ماه سهمیه اجرای رایگان دریافت می‌کنید تا تمام امکانات ویرایشگر، نودها و ایجنت‌های هوش مصنوعی را در عمل بررسی کنید.',
        ],
    ];

    $offers = config('saas.enabled') && ! empty(config('saas.plans'))
        ? collect(config('saas.plans', []))->map(fn (array $plan) => [
            '@type' => 'Offer',
            'name' => 'پلن ' . $plan['name'],
            'price' => (string) (($plan['monthly_price'] ?? 0) * 10),
            'priceCurrency' => 'IRR',
            'availability' => 'https://schema.org/InStock',
            'url' => route('register'),
        ])->values()->all()
        : [
            '@type' => 'Offer',
            'price' => '0',
            'priceCurrency' => 'IRR',
            'availability' => 'https://schema.org/InStock',
            'url' => route('register'),
        ];

    $faqEntities = array_map(fn (array $item) => [
        '@type' => 'Question',
        'name' => $item['q'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $item['a'],
        ],
    ], $faqs);

    $schemaData = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'Organization',
                '@id' => $homeUrl . '#organization',
                'name' => $siteName,
                'alternateName' => 'Tameshk Flow',
                'url' => $homeUrl,
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $logoUrl,
                ],
                'sameAs' => [
                    'https://github.com/MehrshadDev/tameshk-flow',
                ],
            ],
            [
                '@type' => 'WebSite',
                '@id' => $homeUrl . '#website',
                'url' => $homeUrl,
                'name' => $siteName,
                'inLanguage' => 'fa-IR',
                'publisher' => [
                    '@id' => $homeUrl . '#organization',
                ],
            ],
            [
                '@type' => 'SoftwareApplication',
                '@id' => $homeUrl . '#software',
                'name' => $siteName,
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web',
                'inLanguage' => 'fa-IR',
                'url' => $homeUrl,
                'image' => $previewImage,
                'description' => $pageDescription,
                'publisher' => [
                    '@id' => $homeUrl . '#organization',
                ],
                'offers' => $offers,
            ],
            [
                '@type' => 'FAQPage',
                '@id' => $homeUrl . '#faq',
                'mainEntity' => $faqEntities,
            ],
        ],
    ];
@endphp

<x-layouts.app
    :title="$pageTitle"
    :description="$pageDescription"
    :canonical="$homeUrl"
    :footer_visibility="true"
    :header_visibility="true">

    <x-slot:schema>
        <script type="application/ld+json">
            {!! json_encode($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
        </script>
    </x-slot:schema>

    <x-pages.home.hero/>
    <x-pages.home.showcase/>
    <x-pages.home.features/>
    <x-pages.home.solutions/>
    <x-pages.home.pricing/>
    <x-pages.home.faq :faqs="$faqs"/>
</x-layouts.app>
