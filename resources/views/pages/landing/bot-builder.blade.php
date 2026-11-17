@php
    $siteName = config('app.site_name') ?: config('app.name', 'تمشک فلو');
    $pageTitle = 'ساخت ربات تلگرام بدون کدنویسی (ابری) | ' . $siteName;
    $pageDescription = 'ساخت ربات تلگرام، اتصال به اکانت تلگرام بیزینس و ساخت ایجنت هوش مصنوعی (ChatGPT و Gemini) بدون کدنویسی و بدون نیاز به سرور. مجهز به حافظه گفتگو، تبدیل ویس به متن و اتصال به ده‌ها سرویس و API.';
    $canonicalUrl = route('landing.bot-builder');
    $homeUrl = route('home');
    $logoUrl = asset('images/logo/favicon.png');
    $ogImage = is_file(public_path('images/landing/bot-builder-og.jpg'))
        ? asset('images/landing/bot-builder-og.jpg')
        : (is_file(public_path('images/landing/tameshk-canvas-dark.webp'))
            ? asset('images/landing/tameshk-canvas-dark.webp')
            : $logoUrl);

    $faqs = [
        [
            'q' => 'آیا برای ساخت ربات تلگرام در تمشک فلو نیاز به دانش برنامه‌نویسی دارم؟',
            'a' => 'خیر؛ تمام مراحل ساخت ربات در محیط بصری با کشیدن و رها کردن بلوک‌ها (Drag & Drop) انجام می‌شود و حتی یک خط کدنویسی پایتون یا PHP نیاز نیست.',
        ],
        [
            'q' => 'آیا برای روشن ماندن ۲۴ ساعته ربات باید سرور مجازی (VPS) یا هاست بخرم؟',
            'a' => 'خیر؛ تمشک فلو کاملاً ابری است. به محض اینکه روی دکمه «انتشار» کلیک کنید، وب‌هوک ربات تلگرام شما به صورت خودکار و بدون نیاز به پروکسی یا سرور شخصی ثبت می‌شود و ۲۴ ساعته کار می‌کند.',
        ],
        [
            'q' => 'چطور ربات تلگرام را به هوش مصنوعی مثل ChatGPT یا Gemini وصل کنم؟',
            'a' => 'کافی است نود شروع‌کننده تلگرام را روی بوم بگذارید و آن را به نود AI Agent متصل کنید. در تمشک فلو می‌توانید مدل‌های Gemini، ChatGPT، Claude، DeepSeek، Groq و OpenRouter را به همراه حافظه گفتگو به ربات خود وصل کنید.',
        ],
        [
            'q' => 'آیا امکان اتصال به اکانت شخصی تلگرام بیزینس و پردازش ویس وجود دارد؟',
            'a' => 'بله؛ نود اختصاصی تلگرام در تمشک فلو علاوه بر ربات‌های معمولی، از پاسخ‌دهی خودکار در اکانت شخصی تلگرام بیزینس و دانلود فایل صوتی برای تبدیل ویس به متن با هوش مصنوعی پشتیبانی می‌کند.',
        ],
        [
            'q' => 'ربات تلگرام را به چه سرویس‌ها و دیتابیس‌هایی می‌توانم متصل کنم؟',
            'a' => 'علاوه بر نودهای آماده برای جیمیل، گوگل شیت، ووکامرس، دیتابیس MySQL، جدول داده داخلی، ویکی‌پدیا و وب‌اسکرپر، از طریق نودهای HTTP Request و Webhook می‌توانید ربات خود را به هر سامانه پیامکی، CRM، درگاه پرداخت یا وب‌سرویس دیگری متصل کنید.',
        ],
        [
            'q' => 'آیا قبل از خرید اشتراک می‌توانم ربات خود را رایگان بسازم و تست کنم؟',
            'a' => 'بله؛ پلن رایگان تمشک فلو همیشگی است و ماهانه ۵۰۰ اجرای رایگان به همراه دسترسی کامل به تمامی نودها، ابزارها و ایجنت هوش مصنوعی در اختیار شما قرار می‌دهد.',
        ],
    ];

    $offers = collect(config('saas.plans', []))->map(fn (array $plan) => [
        '@type' => 'Offer',
        'name' => 'پلن ' . $plan['name'],
        'price' => (string) (($plan['monthly_price'] ?? 0) * 10),
        'priceCurrency' => 'IRR',
        'availability' => 'https://schema.org/InStock',
        'url' => route('register'),
    ])->values()->all();

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
                '@type' => 'WebPage',
                '@id' => $canonicalUrl . '#webpage',
                'url' => $canonicalUrl,
                'name' => $pageTitle,
                'description' => $pageDescription,
                'inLanguage' => 'fa-IR',
                'isPartOf' => [
                    '@id' => $homeUrl . '#website',
                ],
                'breadcrumb' => [
                    '@id' => $canonicalUrl . '#breadcrumb',
                ],
            ],
            [
                '@type' => 'BreadcrumbList',
                '@id' => $canonicalUrl . '#breadcrumb',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => $siteName,
                        'item' => $homeUrl,
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => 'ساخت ربات تلگرام با هوش مصنوعی',
                        'item' => $canonicalUrl,
                    ],
                ],
            ],
            [
                '@type' => 'SoftwareApplication',
                '@id' => $canonicalUrl . '#software',
                'name' => 'ربات‌ساز ابری و هوشمند تلگرام تمشک فلو',
                'applicationCategory' => 'BusinessApplication',
                'operatingSystem' => 'Web',
                'inLanguage' => 'fa-IR',
                'url' => $canonicalUrl,
                'image' => $ogImage,
                'description' => $pageDescription,
                'publisher' => [
                    '@id' => $homeUrl . '#organization',
                ],
                'offers' => $offers,
            ],
            [
                '@type' => 'FAQPage',
                '@id' => $canonicalUrl . '#faq',
                'mainEntity' => $faqEntities,
            ],
        ],
    ];
@endphp

<x-layouts.app
    :title="$pageTitle"
    :description="$pageDescription"
    :canonical="$canonicalUrl"
    :og-image="$ogImage"
    :header_visibility="true"
    :footer_visibility="true">

    <x-slot:schema>
        <script type="application/ld+json">
            {!! json_encode($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
    </x-slot:schema>

    <x-pages.bot-builder.hero />
    <x-pages.bot-builder.features />
    <x-pages.bot-builder.comparison />
    <x-pages.home.pricing />
    <x-pages.bot-builder.faq :faqs="$faqs" />

</x-layouts.app>
