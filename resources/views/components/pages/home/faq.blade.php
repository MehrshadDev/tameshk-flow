@props([
    'faqs' => [],
])

@if ($faqs !== [])
    <section class="w-full border-t border-line-border bg-surface-base py-20 font-vazirmatn-rd sm:py-24">
        <div class="mx-auto max-w-3xl px-4 sm:px-6">
            <div class="text-center">
                <h2 class="text-2xl font-extrabold leading-normal tracking-tight text-content-main sm:text-3xl">
                    سوالات متداول
                </h2>
                <p class="mt-3 text-sm leading-7 text-content-muted">
                    پاسخ کوتاه به پرتکرارترین پرسش‌ها پیش از شروع کار با تمشک فلو.
                </p>
            </div>

            <div x-data="{ active: 0 }" class="mt-10 space-y-3">
                @foreach ($faqs as $index => $faq)
                    <div class="overflow-hidden rounded-2xl border border-line-border bg-surface-card transition">
                        <button type="button"
                                @click="active = (active === {{ $index }} ? null : {{ $index }})"
                                class="flex w-full items-center justify-between gap-4 px-5 py-4 text-start text-sm font-extrabold text-content-main transition hover:text-primary cursor-pointer sm:px-6">
                            <span>{{ $faq['q'] }}</span>
                            <x-ui.icon name="chevron-down"
                                       x-bind:class="active === {{ $index }} && 'rotate-180 text-primary'"
                                       class="size-4.5 shrink-0 text-content-muted transition-transform duration-200"/>
                        </button>

                        <div x-show="active === {{ $index }}"
                             x-cloak
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="border-t border-line-border/60 px-5 py-4 text-xs leading-7 text-content-muted sm:px-6 sm:text-sm sm:leading-8">
                            {{ $faq['a'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
