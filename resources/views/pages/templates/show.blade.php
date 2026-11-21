@php
    use App\Templates\TemplateRepository;
    use Carbon\Carbon;

    $siteName = config('app.site_name') ?: config('app.name', 'تمشک فلو');
    $homeUrl = route('home');
    $indexUrl = route('templates.index');
    $canonicalUrl = route('templates.show', $template->slug);
    $fullTitle = $template->seoTitle . ' | ' . $siteName;
    $logoUrl = asset('images/logo/favicon.png');

    $sourceFile = TemplateRepository::sourceFilePath($template->slug);
    $lastModifiedIso = ($sourceFile && is_file($sourceFile))
        ? Carbon::createFromTimestamp(filemtime($sourceFile), config('app.timezone', 'Asia/Tehran'))->toAtomString()
        : now()->toAtomString();

    $howToSteps = array_map(fn (array $step, int $idx) => [
        '@type' => 'HowToStep',
        'position' => $idx + 1,
        'name' => $step['title'],
        'text' => $step['description'],
    ], $template->setupSteps, array_keys($template->setupSteps));

    $faqEntities = array_map(fn (array $item) => [
        '@type' => 'Question',
        'name' => $item['q'],
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text' => $item['a'],
        ],
    ], $template->faqs);

    $graph = [
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
            'name' => $fullTitle,
            'description' => $template->seoDescription,
            'inLanguage' => 'fa-IR',
            'dateModified' => $lastModifiedIso,
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
                    'name' => 'ورکفلوهای آماده',
                    'item' => $indexUrl,
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $template->title,
                    'item' => $canonicalUrl,
                ],
            ],
        ],
    ];

    if ($howToSteps !== []) {
        $graph[] = [
            '@type' => 'HowTo',
            '@id' => $canonicalUrl . '#howto',
            'name' => $template->seoTitle,
            'description' => $template->subtitle,
            'totalTime' => 'PT' . max(1, $template->setupMinutes) . 'M',
            'inLanguage' => 'fa-IR',
            'step' => $howToSteps,
        ];
    }

    if ($faqEntities !== []) {
        $graph[] = [
            '@type' => 'FAQPage',
            '@id' => $canonicalUrl . '#faq',
            'mainEntity' => $faqEntities,
        ];
    }

    $schemaData = [
        '@context' => 'https://schema.org',
        '@graph' => $graph,
    ];

    $templateError = session('template_error');
@endphp

<x-layouts.app
    :title="$fullTitle"
    :description="$template->seoDescription"
    :canonical="$canonicalUrl"
    og-type="article"
    :header_visibility="false"
    :footer_visibility="true">

    <x-slot:schema>
        <script type="application/ld+json">
            {!! json_encode($schemaData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
        </script>
    </x-slot:schema>

    <div class="min-h-screen bg-surface-base font-vazirmatn-rd">
        <x-templates.header active="show"/>

        <div class="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:py-12">
            <nav aria-label="مسیر صفحه" class="mb-6 flex flex-wrap items-center gap-1.5 text-xs text-content-muted">
                <a href="{{ route('templates.index') }}" class="font-semibold transition hover:text-primary">
                    ورکفلوهای آماده
                </a>
                <x-ui.icon name="chevron-left" class="size-3.5 opacity-60"/>
                <span>{{ $template->category->label() }}</span>
                <x-ui.icon name="chevron-left" class="size-3.5 opacity-60"/>
                <span class="font-bold text-content-main">{{ $template->title }}</span>
            </nav>

            @if ($templateError)
                <div class="mb-6 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-primary/30 bg-primary/10 px-4 py-3.5 text-xs font-semibold text-primary">
                    <div class="flex items-center gap-2.5">
                        <x-ui.icon name="alert-triangle" class="size-4.5 shrink-0"/>
                        <span>{{ $templateError }}</span>
                    </div>

                    @if (auth()->check() && config('saas.enabled'))
                        <a href="{{ route('app.billing.index') }}"
                           class="inline-flex shrink-0 items-center gap-1 rounded-xl bg-primary px-3 py-1.5 text-[11px] font-bold text-white transition hover:bg-primary/90">
                            <span>ارتقای اشتراک</span>
                            <x-ui.icon name="chevron-left" class="size-3.5"/>
                        </a>
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-12 lg:items-start">

                <article class="space-y-8 lg:col-span-8">
                    <header class="space-y-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 rounded-xl bg-primary/10 px-3 py-1 text-xs font-extrabold text-primary">
                                <x-ui.icon :name="$template->category->icon()" class="size-3.5"/>
                                <span>{{ $template->category->label() }}</span>
                            </span>

                            <span class="inline-flex items-center gap-1.5 rounded-xl border border-line-border bg-surface-card px-3 py-1 text-xs font-semibold text-content-muted">
                                <x-ui.icon name="clock" class="size-3.5 text-primary"/>
                                <span>راه‌اندازی در {{ $template->setupMinutes }} دقیقه</span>
                            </span>

                            <span class="inline-flex items-center gap-1.5 rounded-xl border border-line-border bg-surface-card px-3 py-1 text-xs font-semibold text-content-muted">
                                <span>سطح: {{ $template->difficulty }}</span>
                            </span>
                        </div>

                        <h1 class="text-2xl font-extrabold leading-tight tracking-tight text-content-main sm:text-4xl">
                            {{ $template->title }}
                        </h1>

                        <p class="text-sm leading-7 text-content-muted sm:text-base sm:leading-8">
                            {{ $template->subtitle }}
                        </p>
                    </header>

                    <div class="rounded-2xl border border-primary/30 bg-surface-card p-4 shadow-sm lg:hidden">
                        <form method="POST"
                              action="{{ route('templates.use', $template->slug) }}"
                              x-data="{ submitting: false }"
                              @submit="submitting = true">
                            @csrf
                            <button type="submit"
                                    :disabled="submitting"
                                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3 text-sm font-extrabold text-white shadow-md shadow-primary/25 transition hover:bg-primary/90 disabled:opacity-60 cursor-pointer">
                                <x-ui.icon name="bolt" x-show="!submitting" class="size-4.5"/>
                                <x-ui.icon name="loader" x-show="submitting" x-cloak class="size-4.5 animate-spin"/>
                                <span x-text="submitting ? 'در حال ساخت ورکفلو...' : '{{ auth()->check() ? 'ساخت این ورکفلو در پنل من' : 'استفاده رایگان از این ورکفلو' }}'"></span>
                            </button>
                        </form>
                        <p class="mt-2.5 text-center text-[11px] leading-5 text-content-muted">
                            @auth
                                این ورکفلو بلافاصله در حساب شما ساخته و در ویرایشگر باز می‌شود.
                            @else
                                رایگان ثبت‌نام کنید تا این ورکفلو خودکار در بوم شما آماده شود.
                            @endauth
                        </p>
                    </div>

                    <x-templates.flow-preview :template="$template" :definitions="$definitions"/>

                    <section class="rounded-3xl border border-line-border bg-surface-card p-6 shadow-2xs sm:p-8 space-y-8">
                        <div class="space-y-3">
                            <h2 class="flex items-center gap-2.5 text-lg font-extrabold text-content-main">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-amber-500/12 text-amber-500">
                                    <x-ui.icon name="alert-circle" class="size-4.5"/>
                                </span>
                                <span>مسئله چیست؟</span>
                            </h2>
                            <p class="text-sm leading-8 text-content-muted">
                                {{ $template->problem }}
                            </p>
                        </div>

                        <div class="border-t border-line-border/60 pt-7 space-y-4">
                            <h2 class="flex items-center gap-2.5 text-lg font-extrabold text-content-main">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-emerald-500/12 text-emerald-500">
                                    <x-ui.icon name="bolt" class="size-4.5"/>
                                </span>
                                <span>بعد از راه‌اندازی چه اتفاقی می‌افتد؟</span>
                            </h2>

                            <ul class="space-y-3">
                                @foreach ($template->outcomes as $outcome)
                                    <li class="flex items-start gap-3 text-sm leading-7 text-content-main/90">
                                        <span class="mt-1.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-500">
                                            <x-ui.icon name="check" class="size-3.5"/>
                                        </span>
                                        <span>{{ $outcome }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <div class="border-t border-line-border/60 pt-7 space-y-3">
                            <h2 class="flex items-center gap-2.5 text-lg font-extrabold text-content-main">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-sky-500/12 text-sky-500">
                                    <x-ui.icon name="users" class="size-4.5"/>
                                </span>
                                <span>برای چه کسی مناسب است؟</span>
                            </h2>
                            <p class="text-sm leading-8 text-content-muted">
                                {{ $template->audience }}
                            </p>
                        </div>
                    </section>

                    @if ($template->setupSteps !== [])
                        <section class="rounded-3xl border border-line-border bg-surface-card p-6 shadow-2xs sm:p-8">
                            <h2 class="text-lg font-extrabold text-content-main">
                                راه‌اندازی در {{ count($template->setupSteps) }} قدم ساده
                            </h2>
                            <p class="mt-1.5 text-xs leading-6 text-content-muted">
                                ساختار گره‌ها و متغیرها از قبل آماده شده؛ فقط اتصال‌های خودتان را انتخاب کنید.
                            </p>

                            <ol class="mt-6 space-y-4">
                                @foreach ($template->setupSteps as $idx => $step)
                                    <li class="flex items-start gap-3.5 rounded-2xl border border-line-border/70 bg-surface-base/70 p-4">
                                        <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-primary/12 font-mono text-xs font-extrabold text-primary">
                                            {{ $idx + 1 }}
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <h3 class="text-sm font-extrabold text-content-main">
                                                {{ $step['title'] }}
                                            </h3>
                                            <p class="mt-1 text-xs leading-6 text-content-muted sm:text-sm sm:leading-7">
                                                {{ $step['description'] }}
                                            </p>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        </section>
                    @endif

                    @if ($template->faqs !== [])
                        <section class="space-y-4">
                            <h2 class="text-lg font-extrabold text-content-main sm:text-xl">
                                سوالات متداول
                            </h2>

                            <div x-data="{ active: 0 }" class="space-y-2.5">
                                @foreach ($template->faqs as $idx => $faq)
                                    <div class="overflow-hidden rounded-2xl border border-line-border bg-surface-card">
                                        <button type="button"
                                                @click="active = (active === {{ $idx }} ? null : {{ $idx }})"
                                                class="flex w-full items-center justify-between gap-4 px-5 py-4 text-start text-xs font-extrabold text-content-main transition hover:text-primary cursor-pointer sm:text-sm">
                                            <span>{{ $faq['q'] }}</span>
                                            <x-ui.icon name="chevron-down"
                                                       x-bind:class="active === {{ $idx }} && 'rotate-180 text-primary'"
                                                       class="size-4 shrink-0 text-content-muted transition-transform duration-200"/>
                                        </button>

                                        <div x-show="active === {{ $idx }}"
                                             x-cloak
                                             x-transition
                                             class="border-t border-line-border/60 px-5 py-4 text-xs leading-7 text-content-muted sm:text-sm sm:leading-8">
                                            {{ $faq['a'] }}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </article>

                <aside class="space-y-4 lg:sticky lg:top-24 lg:col-span-4">
                    <div class="hidden rounded-3xl border border-primary/35 bg-surface-card p-5 shadow-lg shadow-primary/5 lg:block">
                        <div class="mb-4 flex items-center gap-3">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <x-ui.icon name="rocket" class="size-5"/>
                            </span>
                            <div>
                                <p class="text-xs font-extrabold text-content-main">آماده استفاده روی بوم</p>
                                <p class="mt-0.5 text-[11px] text-content-muted">بدون کدنویسی و بدون نیاز به سرور</p>
                            </div>
                        </div>

                        <form method="POST"
                              action="{{ route('templates.use', $template->slug) }}"
                              x-data="{ submitting: false }"
                              @submit="submitting = true">
                            @csrf
                            <button type="submit"
                                    :disabled="submitting"
                                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary py-3 text-sm font-extrabold text-white shadow-md shadow-primary/25 transition hover:bg-primary/90 active:scale-[0.99] disabled:opacity-60 cursor-pointer">
                                <x-ui.icon name="bolt" x-show="!submitting" class="size-4.5"/>
                                <x-ui.icon name="loader" x-show="submitting" x-cloak class="size-4.5 animate-spin"/>
                                <span x-text="submitting ? 'در حال ساخت ورکفلو...' : '{{ auth()->check() ? 'ساخت این ورکفلو در پنل من' : 'همین ورکفلو را بردار' }}'"></span>
                            </button>
                        </form>

                        <p class="mt-3 text-center text-[11px] leading-6 text-content-muted">
                            @auth
                                ورکفلو بلافاصله در حساب شما ساخته و در ویرایشگر باز می‌شود؛ فقط اعتبارنامه‌ها را وصل کنید.
                            @else
                                ثبت‌نام رایگان با جیمیل؛ بلافاصله بعد از ورود همین ورکفلو آماده روی بوم شما باز می‌شود.
                            @endauth
                        </p>
                    </div>

                    <div class="rounded-3xl border border-line-border bg-surface-card p-5 shadow-2xs">
                        <h3 class="text-xs font-extrabold text-content-main">
                            برنامه‌ها و ابزارهای لازم
                        </h3>

                        <div class="mt-3.5 space-y-2.5">
                            @foreach ($template->requiredApps as $app)
                                <div class="flex items-center gap-3 rounded-2xl border border-line-border/70 bg-surface-base/70 px-3.5 py-2.5">
                                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl border border-line-border bg-surface-card text-content-main shadow-2xs">
                                        <x-ui.icon :name="$app['icon']" class="size-5"/>
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-xs font-extrabold text-content-main">{{ $app['name'] }}</p>
                                        <p class="mt-0.5 truncate text-[11px] text-content-muted">{{ $app['role'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    @if ($relatedDocs !== [])
                        <div class="rounded-3xl border border-line-border bg-surface-card p-5 shadow-2xs">
                            <h3 class="text-xs font-extrabold text-content-main">
                                راهنماهای مرتبط
                            </h3>

                            <div class="mt-3 space-y-2">
                                @foreach ($relatedDocs as $doc)
                                    <a href="{{ route('docs.show', $doc['slug']) }}"
                                       class="group flex items-start justify-between gap-2.5 rounded-xl border border-transparent px-2.5 py-2 transition hover:border-line-border hover:bg-surface-base">
                                        <div class="min-w-0 flex-1">
                                            <p class="text-xs font-bold leading-5 text-content-main transition group-hover:text-primary">
                                                {{ $doc['title'] }}
                                            </p>
                                            <p class="mt-0.5 text-[10px] text-content-muted">
                                                زمان مطالعه: {{ $doc['readingTime'] }}
                                            </p>
                                        </div>
                                        <x-ui.icon name="chevron-left" class="mt-1 size-4 shrink-0 text-content-muted transition group-hover:-translate-x-0.5 group-hover:text-primary"/>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </aside>
            </div>

            @if ($relatedTemplates !== [])
                <section class="mt-14 border-t border-line-border/70 pt-10">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="text-lg font-extrabold text-content-main">ورکفلوهای آماده دیگر</h2>
                        <a href="{{ route('templates.index') }}"
                           class="inline-flex items-center gap-1 text-xs font-bold text-primary hover:underline">
                            <span>مشاهده همه</span>
                            <x-ui.icon name="chevron-left" class="size-3.5"/>
                        </a>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-3">
                        @foreach ($relatedTemplates as $rel)
                            <x-templates.card :template="$rel"/>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-layouts.app>
