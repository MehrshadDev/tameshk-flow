@php
    $siteName = config('app.site_name') ?: config('app.name', 'تمشک فلو');
    $pageTitle = 'خرید اشتراک n8n ایرانی (بدون نیاز به سرور + تست رایگان) | ' . $siteName;
    $pageDescription = 'به دنبال خرید اشتراک n8n یا سرور n8n هستید؟ با تمشک فلو بدون نیاز به سرور مجازی، نصب داکر و پرداخت دلاری، ورک‌فلو، ربات تلگرام و ایجنت هوش مصنوعی بسازید.';
    $canonicalUrl = route('landing.buy-n8n');
    $homeUrl = route('home');
    $logoUrl = asset('images/logo/favicon.png');
    $ogImage = is_file(public_path('images/landing/buy-n8n-og.jpg'))
        ? asset('images/landing/buy-n8n-og.jpg')
        : (is_file(public_path('images/landing/tameshk-canvas-dark.webp'))
            ? asset('images/landing/tameshk-canvas-dark.webp')
            : $logoUrl);

    $faqs = [
        [
            'q' => 'تمشک فلو اکانت رسمی n8n.io می‌فروشه یا جایگزین اونه؟',
            'a' => 'ما اکانت تحریمی n8n نمی‌فروشیم؛ تمشک فلو یک پلتفرم مستقل و ابریه که با الهام از همون فضای بصری n8n طراحی شده تا بدون دردسر خرید سرور مجازی، نصب داکر و پرداخت ارزی، ورکفلوها و ایجنت‌های هوش مصنوعیت رو خیلی راحت بسازی.',
        ],
        [
            'q' => 'فرق اصلی تمشک فلو با خرید سرور مجازی (VPS) برای n8n چیه؟',
            'a' => 'روی سرور شخصی باید هر ماه هزینه VPS خارجی بدی، داکر و SSL نصب کنی، با فیلترینگ وب‌هوک تلگرام کلنجار بری و همیشه نگران بکاپ و قطعی سرور باشی. اما توی تمشک فلو کل زیرساخت در فضای ابری آماده‌ست و ظرف ۳۰ ثانیه مستقیم وارد محیط طراحی می‌شی.',
        ],
        [
            'q' => 'ربات تلگرام و مدل‌های هوش مصنوعی بدون تحریم کار می‌کنن؟',
            'a' => 'آره کاملاً! اتصال به ربات تلگرام، تلگرام بیزینس، جیمیل، گوگل شیت و مدل‌های هوش مصنوعی توی تمشک فلو به‌صورت مستقیم انجام می‌شه و هیچ نیازی به تنظیم پروکسی یا تحریم‌شکن نداری.',
        ],
        [
            'q' => 'می‌تونم سایت یا سیستم اختصاصی خودم رو به تمشک فلو وصل کنم؟',
            'a' => 'بله، دستت کاملاً بازه! علاوه بر نودهای آماده برای سرویس‌های پرکاربرد (مثل تلگرام، بله، ووکامرس و ایمیل)، با نودهای Webhook و HTTP Request می‌تونی تمشک فلو رو به هر سایت، پنل پیامکی یا API دلخواهی وصل کنی.',
        ],
        [
            'q' => 'قبل از خرید اشتراک می‌تونم همه امکانات رو رایگان تست کنم؟',
            'a' => 'حتماً! پلن رایگان تمشک فلو همیشگیه و هر ماه ۵۰۰ اجرای رایگان به همراه دسترسی کامل به همه نودها و ایجنت هوش مصنوعی داری تا با خیال راحت همه‌چیز رو روی کارهای واقعی امتحان کنی.',
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
            {!! json_encode($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}
        </script>
    </x-slot:schema>

    <x-pages.buy-n8n.hero />
    <x-pages.buy-n8n.features />
    <x-pages.buy-n8n.comparison />
    <x-templates.featured-strip
        heading="ورکفلوهای آماده مشابه n8n در تمشک فلو"
        subheading="قالب‌های آماده برای ووکامرس، ربات تلگرام و فرم تماس را با یک کلیک اجرا کنید."
    />
    <x-pages.home.pricing />
    <x-pages.buy-n8n.faq :faqs="$faqs" />

</x-layouts.app>
