@php
    $siteName = config('app.site_name') ?: config('app.name', 'تمشک فلو');
    $pageTitle = 'خرید اشتراک n8n ایرانی (بدون نیاز به سرور + تست رایگان) | ' . $siteName;
    $pageDescription = 'به دنبال خرید اشتراک n8n یا سرور n8n هستید؟ با تمشک فلو بدون نیاز به سرور مجازی، نصب داکر و پرداخت دلاری، ورک‌فلو، ربات تلگرام و ایجنت هوش مصنوعی بسازید.';
    $canonicalUrl = route('landing.buy-n8n');
    $homeUrl = route('home');
    $logoUrl = asset('images/logo/favicon.png');
    $ogImage = is_file(public_path('images/landing/buy-n8n-og.jpg'))
        ? asset('images/landing/buy-n8n-og.jpg')
        : $logoUrl;

    $faqs = [
        [
            'q' => 'آیا تمشک فلو اکانت رسمی n8n.io می‌فروشد یا جایگزین آن است؟',
            'a' => 'ما فروشنده اکانت تحریمی n8n.io نیستیم؛ تمشک فلو یک پلتفرم مستقل، ابری و بومی است که دقیقاً با الهام از تجربه بوم بصری n8n برای کاربران ایرانی توسعه یافته تا بدون نیاز به سرور مجازی، داکر و پرداخت دلاری، ورک‌فلوها و ایجنت‌های هوشمند خود را بسازند.',
        ],
        [
            'q' => 'تفاوت اصلی تمشک فلو با خرید سرور مجازی (VPS) برای n8n چیست؟',
            'a' => 'در روش سرور شخصی، شما باید هر ماه هزینه VPS خارجی بپردازید، داکر و SSL نصب کنید، مشکل فیلترینگ وب‌هوک تلگرام را حل کنید و نگران بکاپ و قطعی سرور باشید. در تمشک فلو تمام این زیرساخت‌ها در فضای ابری آماده است و در ۳۰ ثانیه وارد بوم طراحی می‌شوید.',
        ],
        [
            'q' => 'آیا ربات تلگرام و مدل‌های هوش مصنوعی بدون تحریم کار می‌کنند؟',
            'a' => 'بله؛ اتصال به ربات تلگرام، تلگرام بیزینس، جیمیل، گوگل شیت و مدل‌های هوش مصنوعی (Gemini, ChatGPT, Claude, DeepSeek, Groq, OpenRouter) در تمشک فلو بدون نیاز به تنظیم پروکسی یا تحریم‌شکن به صورت مستقیم انجام می‌شود.',
        ],
        [
            'q' => 'آیا می‌توانم سیستم‌های خودم یا سایت‌های دیگر را به تمشک فلو وصل کنم؟',
            'a' => 'بله؛ علاوه بر نود اختصاصی ووکامرس (WooCommerce)، شما به نودهای Webhook (با آدرس تست و پروداکشن) و HTTP Request دسترسی دارید و می‌توانید به هر API، سایت یا سامانه پیامکی ایرانی و خارجی متصل شوید.',
        ],
        [
            'q' => 'آیا قبل از خرید اشتراک می‌توانم همه امکانات را رایگان تست کنم؟',
            'a' => 'بله؛ پلن رایگان تمشک فلو همیشگی است، نیازی به ثبت کارت بانکی ندارد و ماهانه ۵۰۰ اجرای رایگان به همراه دسترسی به تمامی نودها و ایجنت هوش مصنوعی در اختیار شما می‌گذارد.',
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
                        'name' => 'خرید اشتراک n8n ایرانی',
                        'item' => $canonicalUrl,
                    ],
                ],
            ],
            [
                '@type' => 'SoftwareApplication',
                '@id' => $canonicalUrl . '#software',
                'name' => 'تمشک فلو — جایگزین ابری و ایرانی n8n',
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

    <x-pages.buy-n8n.hero />
    <x-pages.buy-n8n.features />
    <x-pages.buy-n8n.comparison />
    <x-pages.home.pricing />
    <x-pages.buy-n8n.faq :faqs="$faqs" />

</x-layouts.app>
