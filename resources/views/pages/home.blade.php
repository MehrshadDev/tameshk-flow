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
            'a' => 'نه اصلاً! تمام مراحل طراحی، وصل کردن نودها و تعریف شرط‌ها کاملاً بصری انجام می‌شه. فقط کافیه نودها رو کنار هم بچینی و بدون حتی یک خط کدنویسی، کارهات رو خودکار کنی.',
        ],
        [
            'q' => 'برای استفاده از تمشک فلو باید سرور (VPS) بخرم؟',
            'a' => 'نه، نیازی به خرید سرور، نصب داکر یا درگیر شدن با تنظیمات فنی نداری. همه‌چیز روی زیرساخت ابری ما آماده‌ست و فقط کافیه ورکفلوت رو بسازی و روشنش کنی.',
        ],
        [
            'q' => 'امنیت کلیدهای API و حساب‌هایی که متصل می‌کنم چطور تامین می‌شه؟',
            'a' => 'خیالت از بابت امنیت راحت باشه؛ تمام کلیدهای دسترسی و توکن‌های تو قبل از ذخیره در دیتابیس به‌صورت کامل رمزنگاری (Encrypt) می‌شن و به‌شکل ایزوله فقط در لحظه اجرای ورکفلوهای خودت استفاده می‌شن.',
        ],
        [
            'q' => 'چطور می‌تونم قبل از خرید اشتراک، تمشک فلو رو تست کنم؟',
            'a' => 'پلن رایگان تمشک فلو همیشه فعاله و هیچ محدودیت زمانی نداره. هر ماه سهمیه اجرای رایگان می‌گیری تا تمام امکانات ویرایشگر، نودها و ایجنت‌های هوش مصنوعی رو روی کارهای واقعی امتحان کنی.',
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
