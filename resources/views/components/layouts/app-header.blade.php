@php
    $links = array_values(array_filter([
        ['label' => 'قابلیت‌ها', 'href' => '#features', 'icon' => 'sparkles'],
        config('saas.enabled') ? ['label' => 'تعرفه‌ها', 'href' => '#pricing', 'icon' => 'credit-card'] : null,
        ['label' => 'مستندات', 'href' => route('docs.index'), 'icon' => 'file-text'],
    ]));
@endphp

<div
    x-data="{
        open: false,
        visible: true,
        scrolled: false,
        lastScrollY: 0,
        init() {
            this.lastScrollY = window.scrollY || 0;
            this.scrolled = this.lastScrollY > 16;

            const onScroll = () => {
                const currentY = Math.max(0, window.scrollY || 0);
                const delta = currentY - this.lastScrollY;

                this.scrolled = currentY > 16;

                if (currentY < 40 || this.open) {
                    this.visible = true;
                } else if (delta > 6) {
                    this.visible = false;
                } else if (delta < -6) {
                    this.visible = true;
                }

                this.lastScrollY = currentY;
            };

            window.addEventListener('scroll', onScroll, { passive: true });
        }
    }"
    @keydown.escape.window="open = false"
    class="font-vazirmatn-rd"
>
    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @click="open = false"
        class="fixed inset-0 z-40 bg-black/50 backdrop-blur-sm md:hidden"
    ></div>

    <header
        :class="(!visible && !open) && '-translate-y-24 opacity-0 pointer-events-none md:translate-y-0 md:opacity-100 md:pointer-events-auto'"
        class="pointer-events-none fixed inset-x-0 top-0 z-50 px-3 pt-3 transition-[transform,opacity] duration-300 ease-out sm:px-6 sm:pt-4"
    >
        <div class="pointer-events-auto relative mx-auto max-w-5xl">
            <div
                :class="(scrolled || open) && 'shadow-xl shadow-black/15 dark:shadow-black/50'"
                class="relative flex h-14 items-center justify-between rounded-2xl border border-line-border bg-surface-raised/90 px-3 shadow-lg shadow-black/5 backdrop-blur-xl transition-shadow duration-300 sm:h-16 sm:px-4 dark:bg-surface-card/85 dark:shadow-black/40"
            >
                <div class="pointer-events-none absolute inset-x-12 top-0 h-px bg-linear-to-r from-transparent via-primary/55 to-transparent"></div>

                <div class="flex items-center pr-1">
                    <x-layouts.logo logoClasses="h-8 sm:h-9 w-auto" />
                </div>

                <nav class="hidden items-center gap-1 md:absolute md:left-1/2 md:flex md:-translate-x-1/2">
                    @foreach ($links as $link)
                        <a
                            href="{{ $link['href'] }}"
                            class="rounded-xl px-3.5 py-2 text-sm font-medium text-content-muted transition-colors hover:bg-surface-base hover:text-content-main"
                        >
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="hidden items-center gap-2 md:flex">
                    <x-ui.button href="/login" variant="ghost" class="rounded-xl px-4 py-2 text-sm">ورود</x-ui.button>
                    <x-ui.button href="/register" class="rounded-xl px-5 py-2 text-sm shadow-sm shadow-primary/25">ثبت‌نام</x-ui.button>
                </div>

                <button
                    type="button"
                    @click="open = !open"
                    :aria-expanded="open"
                    aria-label="منو"
                    :class="open && 'border-primary/40 bg-primary/10 text-primary'"
                    class="flex size-9 items-center justify-center rounded-xl border border-line-border bg-surface-base text-content-main transition hover:border-primary/40 hover:text-primary active:scale-95 md:hidden cursor-pointer"
                >
                    <x-ui.icon name="menu-2" x-show="!open" class="size-5"/>
                    <x-ui.icon name="x" x-show="open" x-cloak class="size-5"/>
                </button>
            </div>

            <div
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-200 origin-top"
                x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150 origin-top"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                class="absolute inset-x-0 top-full mt-2.5 overflow-hidden rounded-2xl border border-line-border bg-surface-raised/95 p-3 shadow-2xl backdrop-blur-xl dark:bg-surface-card/95 md:hidden"
            >
                <nav class="flex flex-col gap-1">
                    @foreach ($links as $link)
                        <a
                            href="{{ $link['href'] }}"
                            @click="open = false"
                            class="group flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-semibold text-content-main transition-colors hover:bg-surface-base hover:text-primary"
                        >
                            <span class="flex items-center gap-3">
                                <span class="flex size-8 items-center justify-center rounded-lg border border-line-border/80 bg-surface-base text-primary transition-transform group-hover:scale-105">
                                    <x-ui.icon :name="$link['icon']" class="size-4"/>
                                </span>
                                <span>{{ $link['label'] }}</span>
                            </span>
                            <x-ui.icon name="chevron-left" class="size-4 text-content-muted transition-transform group-hover:-translate-x-0.5 group-hover:text-primary"/>
                        </a>
                    @endforeach
                </nav>

                <div class="my-2.5 h-px bg-linear-to-r from-transparent via-line-border to-transparent"></div>

                <div class="grid grid-cols-2 gap-2.5">
                    <x-ui.button href="/login" variant="secondary" class="rounded-xl py-2.5">ورود</x-ui.button>
                    <x-ui.button href="/register" class="rounded-xl py-2.5 shadow-md shadow-primary/25">ثبت‌نام</x-ui.button>
                </div>
            </div>
        </div>
    </header>
</div>
