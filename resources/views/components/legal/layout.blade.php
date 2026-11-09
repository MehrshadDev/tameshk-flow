@props([
    'title',
    'subtitle',
    'icon' => 'file-text',
    'updatedAt' => 'آبان ۱۴۰۵',
    'activePage' => 'terms',
    'toc' => [],
])

<x-layouts.app
    :title="$title . ' | ' . config('app.name')"
    :description="$subtitle"
    :header_visibility="false"
    :footer_visibility="true">

    <div x-data="{ mobileTocOpen: false }" class="min-h-screen bg-surface-base">
        <header class="sticky top-0 z-40 border-b border-line-border bg-surface-raised/90 backdrop-blur-xl">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between gap-3 px-4 sm:px-6">
                <div class="flex items-center gap-3">
                    <x-layouts.logo :href="route('home')" logoClasses="h-9 w-auto"/>

                    <span class="hidden h-5 w-px bg-line-border sm:block"></span>

                    <div class="hidden items-center gap-1 rounded-xl border border-line-border bg-surface-card p-1 sm:flex">
                        <a href="{{ route('terms') }}"
                            @class([
                                'flex items-center gap-1.5 rounded-lg px-3 py-1 text-xs transition',
                                'bg-primary text-white font-bold shadow-2xs' => $activePage === 'terms',
                                'font-medium text-content-muted hover:text-content-main' => $activePage !== 'terms',
                            ])>
                            <x-ui.icon name="file-text" class="size-3.5"/>
                            <span>قوانین و مقررات</span>
                        </a>

                        <a href="{{ route('privacy') }}"
                            @class([
                                'flex items-center gap-1.5 rounded-lg px-3 py-1 text-xs transition',
                                'bg-primary text-white font-bold shadow-2xs' => $activePage === 'privacy',
                                'font-medium text-content-muted hover:text-content-main' => $activePage !== 'privacy',
                            ])>
                            <x-ui.icon name="lock" class="size-3.5"/>
                            <span>حریم خصوصی</span>
                        </a>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-2.5">
                    <a href="{{ route('docs.index') }}"
                       class="hidden items-center gap-1.5 rounded-xl border border-line-border bg-surface-card px-3 py-2 text-xs font-semibold text-content-muted transition hover:border-primary/40 hover:text-content-main md:inline-flex">
                        <x-ui.icon name="file-text" class="size-4 text-primary"/>
                        <span>مستندات</span>
                    </a>

                    <button type="button"
                            x-data="{ dark: document.documentElement.classList.contains('dark') }"
                            @click="dark = !dark; document.documentElement.classList.toggle('dark', dark); localStorage.setItem('theme', dark ? 'dark' : 'light')"
                            aria-label="تغییر تم"
                            class="flex size-9 items-center justify-center rounded-xl border border-line-border bg-surface-card text-content-muted transition hover:border-content-muted/40 hover:text-content-main cursor-pointer">
                        <x-ui.icon name="moon" x-show="!dark" class="size-4.5"/>
                        <x-ui.icon name="sun" x-show="dark" x-cloak class="size-4.5"/>
                    </button>

                    @auth
                        <x-ui.button :href="route('app.workflows.index')" size="md">
                            <span>ورود به پنل</span>
                            <x-ui.icon name="chevron-left" class="size-4"/>
                        </x-ui.button>
                    @else
                        <x-ui.button :href="route('login')" variant="secondary" size="md">ورود</x-ui.button>
                    @endauth
                </div>
            </div>
        </header>

        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:py-12">
            <div class="mb-6 flex items-center gap-1 rounded-2xl border border-line-border bg-surface-card p-1.5 sm:hidden">
                <a href="{{ route('terms') }}"
                    @class([
                        'flex flex-1 items-center justify-center gap-1.5 rounded-xl py-2 text-xs transition',
                        'bg-primary text-white font-bold shadow-2xs' => $activePage === 'terms',
                        'font-medium text-content-muted hover:text-content-main' => $activePage !== 'terms',
                    ])>
                    <x-ui.icon name="file-text" class="size-4"/>
                    <span>قوانین و مقررات</span>
                </a>

                <a href="{{ route('privacy') }}"
                    @class([
                        'flex flex-1 items-center justify-center gap-1.5 rounded-xl py-2 text-xs transition',
                        'bg-primary text-white font-bold shadow-2xs' => $activePage === 'privacy',
                        'font-medium text-content-muted hover:text-content-main' => $activePage !== 'privacy',
                    ])>
                    <x-ui.icon name="lock" class="size-4"/>
                    <span>حریم خصوصی</span>
                </a>
            </div>

            @if (! empty($toc))
                <div class="mb-6 lg:hidden">
                    <button type="button"
                            @click="mobileTocOpen = !mobileTocOpen"
                            class="flex w-full items-center justify-between rounded-2xl border border-line-border bg-surface-card px-4 py-3 text-xs font-extrabold text-content-main shadow-2xs cursor-pointer">
                        <span class="flex items-center gap-2">
                            <x-ui.icon name="list" class="size-4 text-primary"/>
                            <span>فهرست سرفصل‌های این صفحه</span>
                        </span>
                        <x-ui.icon name="chevron-down"
                                   x-bind:class="mobileTocOpen && 'rotate-180 text-primary'"
                                   class="size-4 text-content-muted transition-transform duration-200"/>
                    </button>

                    <div x-show="mobileTocOpen"
                         x-cloak
                         x-transition
                         class="mt-2 rounded-2xl border border-line-border bg-surface-card p-3 shadow-lg">
                        <ul class="space-y-1 text-xs">
                            @foreach ($toc as $item)
                                <li>
                                    <a href="#{{ $item['id'] }}"
                                       @click="mobileTocOpen = false"
                                       class="flex items-center gap-2 rounded-xl px-3 py-2 font-medium text-content-muted transition hover:bg-surface-base hover:text-primary">
                                        <span class="size-1.5 shrink-0 rounded-full bg-primary/60"></span>
                                        <span>{{ $item['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="flex flex-col gap-8 lg:flex-row lg:items-start">
                <aside class="hidden w-72 shrink-0 lg:block">
                    <div class="sticky top-24 space-y-4">
                        @if (! empty($toc))
                            <div class="rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
                                <div class="mb-3 flex items-center gap-2 border-b border-line-border/70 pb-3 text-xs font-extrabold text-content-main">
                                    <x-ui.icon name="list" class="size-4 text-primary"/>
                                    <span>سرفصل‌های این سند</span>
                                </div>

                                <ul class="space-y-1 border-r border-line-border/80 pr-3 text-xs">
                                    @foreach ($toc as $item)
                                        <li>
                                            <a href="#{{ $item['id'] }}"
                                               class="block rounded-lg py-1.5 font-medium text-content-muted transition hover:text-primary">
                                                {{ $item['label'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs space-y-3">
                            <div class="flex items-center gap-2.5">
                                <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <x-ui.icon name="headset" class="size-5"/>
                                </span>
                                <div>
                                    <p class="text-xs font-extrabold text-content-main">نیاز به راهنمایی دارید؟</p>
                                    <p class="text-[11px] text-content-muted">پاسخگویی سریع به سوالات شما</p>
                                </div>
                            </div>

                            <p class="text-xs leading-6 text-content-muted">
                                در صورت وجود هرگونه ابهام درباره قوانین یا نحوه حفاظت از داده‌ها، از طریق بخش تیکت‌های پشتیبانی با ما در ارتباط باشید.
                            </p>

                            <a href="{{ auth()->check() ? route('app.tickets.index') : route('login') }}"
                               class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-line-border bg-surface-base py-2 text-xs font-bold text-content-main transition hover:border-primary/40 hover:text-primary">
                                <x-ui.icon name="ticket" class="size-4 text-primary"/>
                                <span>ارسال تیکت پشتیبانی</span>
                            </a>
                        </div>
                    </div>
                </aside>

                <article class="min-w-0 flex-1">
                    <div class="rounded-3xl border border-line-border bg-surface-card p-6 shadow-2xs sm:p-8 lg:p-10">
                        <div class="border-b border-line-border/70 pb-6">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <span class="inline-flex items-center gap-1.5 rounded-xl bg-primary/10 px-3 py-1 text-xs font-extrabold text-primary">
                                    <x-ui.icon :name="$icon" class="size-4"/>
                                    <span>مستندات حقوقی تمشک فلو</span>
                                </span>

                                <span class="inline-flex items-center gap-1.5 rounded-xl border border-line-border/80 bg-surface-base px-3 py-1 text-xs font-medium text-content-muted">
                                    <x-ui.icon name="clock" class="size-3.5 text-primary"/>
                                    <span>آخرین بروزرسانی: {{ $updatedAt }}</span>
                                </span>
                            </div>

                            <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-content-main sm:text-3xl">
                                {{ $title }}
                            </h1>

                            <p class="mt-2.5 text-sm leading-7 text-content-muted">
                                {{ $subtitle }}
                            </p>
                        </div>

                        <div class="docs-prose mt-8 space-y-8 text-sm leading-8 text-content-main">
                            {{ $slot }}
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </div>
</x-layouts.app>
