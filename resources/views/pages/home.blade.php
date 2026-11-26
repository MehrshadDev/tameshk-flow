@php
    $siteName = config('app.site_name') ?: config('app.name', 'تمشک فلو');
    $pageTitle = $siteName . ' | پلتفرم اتوماسیون گردش‌کار و ساخت ایجنت هوش مصنوعی';
    $pageDescription = 'تمشک فلو، پلتفرم بصری اتوماسیون گردش‌کار و ساخت ایجنت‌های هوش مصنوعی؛ اتصال سریع تلگرام، جیمیل، گوگل شیت، وب‌سرویس‌ها و مدل‌های زبانی بدون نیاز به کدنویسی.';
    $homeUrl = route('home');
    $logoUrl = asset('images/logo/favicon.png');
    $previewImage = is_file(public_path('images/landing/tameshk-canvas-dark.webp'))
        ? asset('images/landing/tameshk-canvas-dark.webp')
        : $logoUrl;

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
    <x-pages.home.pricing/>
</x-layouts.app>
