@php
    $items = [
        [
            'key' => 'agent',
            'icon' => 'brand:ai-agent',
            'title' => 'ایجنتی که خودش تصمیم می‌گیرد',
            'description' => 'مدل زبانی، حافظه گفتگو و ابزارها را وصل کنید تا ایجنت در هر پیام خودش ابزار مناسب را انتخاب کند.',
            'alt' => 'نمای واقعی نود ایجنت هوش مصنوعی به همراه مدل، حافظه و ابزار در بوم تمشک فلو',
        ],
        [
            'key' => 'data-mapping',
            'icon' => 'brackets',
            'title' => 'داده را بکشید و رها کنید',
            'description' => 'خروجی هر نود را مستقیماً داخل فیلد نود بعدی بیندازید؛ صحت متغیر همان لحظه روی داده واقعی بررسی می‌شود.',
            'alt' => 'نمای واقعی کشیدن فیلد از داده‌های ورودی و رها کردن داخل فیلد متغیر fx در تمشک فلو',
        ],
        [
            'key' => 'routing',
            'icon' => 'arrows-split-2',
            'title' => 'شرط بگذارید و مسیرها را جدا کنید',
            'description' => 'با نودهای شرطی If و Switch پیام‌ها و سفارش‌ها را بررسی کنید و هر داده را خودکار به مسیر درست بفرستید.',
            'alt' => 'نمای واقعی شاخه‌بندی شرطی با نود If و Switch در بوم تمشک فلو',
        ],
        [
            'key' => 'iran-tools',
            'icon' => 'calendar',
            'title' => 'ساخته‌شده برای کسب‌وکار ایرانی',
            'description' => 'تقویم شمسی، تولید کد تایید (OTP)، تبدیل خودکار ارقام فارسی و نگهداری رمزنگاری‌شده کلیدهای API.',
            'alt' => 'نمای واقعی خروجی تاریخ شمسی و کد تایید OTP در تمشک فلو',
        ],
    ];

    $lightboxItems = array_map(fn ($item) => [
        'light' => asset("images/landing/features/{$item['key']}-light.webp"),
        'dark' => asset("images/landing/features/{$item['key']}-dark.webp"),
        'title' => $item['title'],
        'alt' => $item['alt'],
    ], $items);
@endphp

<section id="features"
         x-data="{
             items: @js($lightboxItems),
             activeIndex: null,
             open(idx) {
                 this.activeIndex = idx;
                 document.body.style.overflow = 'hidden';
             },
             close() {
                 this.activeIndex = null;
                 document.body.style.overflow = '';
             }
         }"
         @keydown.escape.window="close()"
         class="relative w-full overflow-x-clip border-t border-line-border bg-surface-base py-16 font-vazirmatn-rd sm:py-24">

    <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true"
         style="background: radial-gradient(45% 32% at 50% 0%, color-mix(in oklab, var(--color-primary) 9%, transparent), transparent 72%);"></div>

    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-2xl font-extrabold leading-snug tracking-tight text-content-main sm:text-4xl">
                اتوماسیون فارسی،
                <span class="text-primary">بدون پیچیدگی</span>
            </h2>

            <p class="mt-3 text-xs leading-7 text-content-muted sm:text-base sm:leading-8">
                هر چیزی که برای ساخت گردش‌کارهای واقعی و ایجنت‌های هوشمند لازم دارید، از همان لحظه اول در بوم آماده است.
            </p>
        </div>

        <div class="mt-10 grid grid-cols-1 gap-5 sm:mt-14 sm:gap-6 md:grid-cols-2 lg:gap-7">
            @foreach ($items as $idx => $item)
                @php
                    $lightSrc = $lightboxItems[$idx]['light'];
                    $darkSrc = $lightboxItems[$idx]['dark'];
                @endphp

                <article
                    class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-line-border bg-surface-card shadow-2xs transition-all duration-300 hover:-translate-y-1 hover:border-primary/40 hover:shadow-xl hover:shadow-black/6 sm:rounded-3xl dark:hover:shadow-black/45">

                    <div class="pointer-events-none absolute inset-x-10 top-0 z-10 h-px bg-linear-to-r from-transparent via-primary/70 to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"></div>

                    <button type="button"
                            @click="open({{ $idx }})"
                            aria-label="بزرگ‌نمایی {{ $item['title'] }}"
                            class="relative block aspect-16/10 w-full cursor-zoom-in overflow-hidden border-b border-line-border/70 bg-surface-base text-right focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary/50">

                        <img src="{{ $lightSrc }}"
                             alt="{{ $item['alt'] }}"
                             loading="lazy"
                             decoding="async"
                             class="block size-full object-cover object-center transition-transform duration-500 ease-out group-hover:scale-[1.03] dark:hidden">

                        <img src="{{ $darkSrc }}"
                             alt="{{ $item['alt'] }}"
                             loading="lazy"
                             decoding="async"
                             class="hidden size-full object-cover object-center transition-transform duration-500 ease-out group-hover:scale-[1.03] dark:block">

                        <div class="pointer-events-none absolute inset-x-0 bottom-0 h-10 bg-linear-to-t from-surface-card/55 to-transparent"></div>

                        <span aria-hidden="true"
                              class="pointer-events-none absolute bottom-3 left-3 flex size-8 items-center justify-center rounded-xl border border-line-border/80 bg-surface-card/85 text-content-muted shadow-xs backdrop-blur-xs transition-all duration-200 group-hover:border-primary/40 group-hover:text-primary md:opacity-0 md:group-hover:opacity-100">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <path d="m21 21-4.3-4.3"/>
                                <path d="M11 8v6"/>
                                <path d="M8 11h6"/>
                            </svg>
                        </span>
                    </button>

                    <div class="flex items-start gap-3.5 p-4 sm:p-5">
                        <span
                            class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-xl border border-line-border/80 bg-surface-base text-primary transition-colors duration-300 group-hover:border-primary/35 group-hover:bg-primary/10 sm:size-10">
                            <x-ui.icon :name="$item['icon']" class="size-4.5 sm:size-5"/>
                        </span>

                        <div class="min-w-0 flex-1">
                            <h3 class="text-sm font-extrabold text-content-main transition-colors duration-200 group-hover:text-primary sm:text-base">
                                {{ $item['title'] }}
                            </h3>

                            <p class="mt-1 text-xs leading-6 text-content-muted sm:text-sm sm:leading-6">
                                {{ $item['description'] }}
                            </p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </div>

    <template x-teleport="body">
        <div x-show="activeIndex !== null"
             x-cloak
             x-transition.opacity.duration.200ms
             @click.self="close()"
             role="dialog"
             aria-modal="true"
             class="fixed -inset-1 z-100 flex items-center justify-center bg-black/75 p-3 font-vazirmatn-rd backdrop-blur-sm sm:p-6">
            <template x-if="activeIndex !== null">
                <div x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     class="relative flex max-h-[90vh] w-full max-w-5xl flex-col overflow-hidden rounded-2xl border border-line-border bg-surface-card shadow-2xl sm:rounded-3xl">

                    <div class="flex items-center justify-between gap-3 border-b border-line-border/70 px-4 py-3 sm:px-5">
                        <span class="truncate text-sm font-bold text-content-main sm:text-base"
                              x-text="items[activeIndex].title"></span>

                        <button type="button"
                                @click="close()"
                                aria-label="بستن"
                                class="flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-xl border border-line-border/80 bg-surface-base text-content-muted transition-colors hover:border-primary/40 hover:text-content-main">
                            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 6 6 18"/>
                                <path d="m6 6 12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="overflow-auto bg-surface-base p-2 sm:p-4">
                        <img :src="items[activeIndex].light"
                             :alt="items[activeIndex].alt"
                             class="mx-auto block max-h-[76vh] w-auto rounded-xl object-contain dark:hidden">

                        <img :src="items[activeIndex].dark"
                             :alt="items[activeIndex].alt"
                             class="mx-auto hidden max-h-[76vh] w-auto rounded-xl object-contain dark:block">
                    </div>
                </div>
            </template>
        </div>
    </template>
</section>
