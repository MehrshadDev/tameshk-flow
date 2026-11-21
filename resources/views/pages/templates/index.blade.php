@php
    $siteName = config('app.site_name') ?: config('app.name', 'تمشک فلو');
    $pageTitle = 'ورکفلوهای آماده و قالب‌های اتوماسیون | ' . $siteName;
    $pageDescription = 'مجموعه ورکفلوهای آماده تمشک فلو برای ووکامرس، تلگرام، گوگل شیت، جیمیل و هوش مصنوعی؛ هر قالب را با یک کلیک به حساب خود اضافه کنید و بدون کدنویسی راه‌اندازی نمایید.';
    $homeUrl = route('home');
    $canonicalUrl = route('templates.index');
    $logoUrl = asset('images/logo/favicon.png');

    $itemListElements = [];
    foreach ($templates as $idx => $tpl) {
        $itemListElements[] = [
            '@type' => 'ListItem',
            'position' => $idx + 1,
            'name' => $tpl->title,
            'description' => $tpl->subtitle,
            'url' => route('templates.show', $tpl->slug),
        ];
    }

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
                '@type' => 'CollectionPage',
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
                'mainEntity' => [
                    '@type' => 'ItemList',
                    'numberOfItems' => count($itemListElements),
                    'itemListElement' => $itemListElements,
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
                        'name' => 'ورکفلوهای آماده',
                        'item' => $canonicalUrl,
                    ],
                ],
            ],
        ],
    ];
@endphp

<x-layouts.app
    :title="$pageTitle"
    :description="$pageDescription"
    :canonical="$canonicalUrl"
    :header_visibility="false"
    :footer_visibility="true">

    <x-slot:schema>
        <script type="application/ld+json">
            {!! json_encode($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
    </x-slot:schema>

    <div class="min-h-screen bg-surface-base font-vazirmatn-rd">
        <x-templates.header active="index"/>

        <section class="relative isolate overflow-hidden border-b border-line-border/70 py-14 sm:py-20">
            <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
                <div class="absolute inset-0"
                     style="background: radial-gradient(45% 45% at 50% 0%, color-mix(in oklab, var(--color-primary) 13%, transparent), transparent 72%);"></div>
                <div class="templates-hero-grid absolute inset-0 opacity-60"></div>
            </div>

            <div class="mx-auto max-w-4xl px-4 text-center sm:px-6">
                <div class="inline-flex items-center gap-2 rounded-full border border-line-border bg-surface-card/90 px-3.5 py-1.5 text-xs font-bold text-content-muted shadow-2xs">
                    <span class="size-2 rounded-full bg-primary"></span>
                    <span>از صفر نسازید؛ با یک کلیک راه‌اندازی کنید</span>
                </div>

                <h1 class="mt-5 text-3xl font-extrabold tracking-tight text-content-main sm:text-5xl">
                    ورکفلوهای آماده تمشک فلو
                </h1>

                <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-content-muted sm:text-base sm:leading-8">
                    هر ورکفلو از قبل چیده، متصل و تنظیم شده است. کافی است یکی را انتخاب کنید، اتصال سرویس‌های خود را وارد نمایید و در چند دقیقه کارهای تکراری را خودکار کنید.
                </p>
            </div>
        </section>

        <section x-data="{
                     query: '',
                     category: 'all',
                     appFilter: 'all',
                     matches(el) {
                         const q = this.query.trim().toLowerCase();
                         const catOk = this.category === 'all' || el.dataset.category === this.category;
                         const appOk = this.appFilter === 'all' || (el.dataset.apps || '').split(',').includes(this.appFilter);
                         const textOk = q === '' || (el.dataset.search || '').includes(q);
                         return catOk && appOk && textOk;
                     },
                     resetFilters() {
                         this.query = '';
                         this.category = 'all';
                         this.appFilter = 'all';
                     }
                 }"
                 class="mx-auto max-w-6xl px-4 py-10 sm:px-6 sm:py-14">

            <div class="space-y-4 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs sm:p-5">
                <div class="relative">
                    <x-ui.icon name="search" class="pointer-events-none absolute inset-s-3.5 top-1/2 size-4.5 -translate-y-1/2 text-content-muted"/>
                    <input type="text"
                           x-model="query"
                           placeholder="جستجو در ورکفلوهای آماده (مثلاً: ووکامرس، تلگرام، سفارش، جدول)..."
                           class="h-11 w-full rounded-xl border border-line-border bg-surface-base pe-10 ps-10 text-xs font-medium text-content-main transition placeholder:text-content-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 sm:text-sm"/>
                    <button type="button"
                            x-show="query !== ''"
                            x-cloak
                            @click="query = ''"
                            aria-label="پاک کردن جستجو"
                            class="absolute inset-e-2.5 top-1/2 flex size-7 -translate-y-1/2 items-center justify-center rounded-lg text-content-muted transition hover:bg-line-border/60 hover:text-content-main cursor-pointer">
                        <x-ui.icon name="x" class="size-4"/>
                    </button>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <button type="button"
                                @click="category = 'all'"
                                :class="category === 'all'
                                    ? 'border-primary bg-primary text-white font-bold shadow-2xs'
                                    : 'border-line-border bg-surface-base text-content-muted hover:text-content-main'"
                                class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs transition cursor-pointer">
                            <span>همه دسته‌ها</span>
                            <span class="font-mono text-[10px] opacity-80">({{ count($templates) }})</span>
                        </button>

                        @foreach ($categories as $item)
                            <button type="button"
                                    @click="category = '{{ $item['category']->value }}'"
                                    :class="category === '{{ $item['category']->value }}'
                                        ? 'border-primary bg-primary text-white font-bold shadow-2xs'
                                        : 'border-line-border bg-surface-base text-content-muted hover:text-content-main'"
                                    class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs transition cursor-pointer">
                                <x-ui.icon :name="$item['category']->icon()" class="size-3.5"/>
                                <span>{{ $item['category']->label() }}</span>
                                <span class="font-mono text-[10px] opacity-80">({{ $item['count'] }})</span>
                            </button>
                        @endforeach
                    </div>

                    @if (count($apps) > 1)
                        <div class="flex flex-wrap items-center gap-1.5">
                            @foreach ($apps as $app)
                                <button type="button"
                                        @click="appFilter = (appFilter === '{{ $app['key'] }}' ? 'all' : '{{ $app['key'] }}')"
                                        :class="appFilter === '{{ $app['key'] }}'
                                            ? 'border-primary/60 bg-primary/10 text-primary font-bold'
                                            : 'border-line-border bg-surface-base text-content-muted hover:text-content-main'"
                                        class="inline-flex items-center gap-1.5 rounded-xl border px-2.5 py-1.5 text-[11px] font-semibold transition cursor-pointer">
                                    <x-ui.icon :name="$app['icon']" class="size-3.5"/>
                                    <span>{{ $app['name'] }}</span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-8 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($templates as $template)
                    <x-templates.card
                        :template="$template"
                        data-category="{{ $template->category->value }}"
                        data-apps="{{ implode(',', $template->appKeys()) }}"
                        data-search="{{ $template->searchableText() }}"
                        x-show="matches($el)"
                    />
                @endforeach
            </div>

            <div class="mt-14 rounded-3xl border border-line-border bg-surface-card p-6 text-center shadow-2xs sm:p-10">
                <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                    <x-ui.icon name="rocket" class="size-6"/>
                </div>
                <h2 class="mt-4 text-lg font-extrabold text-content-main sm:text-2xl">
                    سناریوی اختصاصی خودتان را در ذهن دارید؟
                </h2>
                <p class="mx-auto mt-2 max-w-xl text-xs leading-7 text-content-muted sm:text-sm">
                    در بوم بصری تمشک فلو می‌توانید هر ترکیبی از ووکامرس، تلگرام، گوگل شیت، جیمیل، MySQL و مدل‌های هوش مصنوعی را از صفر و بدون کدنویسی بسازید.
                </p>
                <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
                    <x-ui.button :href="auth()->check() ? route('app.workflows.index') : route('register')" size="lg" class="shadow-md shadow-primary/20">
                        <x-ui.icon name="bolt" class="size-4.5"/>
                        <span>{{ auth()->check() ? 'ورود به ویرایشگر ورکفلوها' : 'شروع رایگان با ۵۰۰ اجرا' }}</span>
                    </x-ui.button>
                    <x-ui.button :href="route('docs.index')" variant="secondary" size="lg">
                        مرور مستندات
                    </x-ui.button>
                </div>
            </div>
        </section>
    </div>

    @once
        <style>
            .templates-hero-grid {
                background-image:
                    linear-gradient(color-mix(in oklab, var(--color-line-border) 55%, transparent) 1px, transparent 1px),
                    linear-gradient(90deg, color-mix(in oklab, var(--color-line-border) 55%, transparent) 1px, transparent 1px);
                background-size: 32px 32px;
                -webkit-mask-image: radial-gradient(ellipse 70% 60% at 50% 30%, #000 20%, transparent 100%);
                mask-image: radial-gradient(ellipse 70% 60% at 50% 30%, #000 20%, transparent 100%);
            }
        </style>
    @endonce
</x-layouts.app>
