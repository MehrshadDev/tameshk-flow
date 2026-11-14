@php
    $darkImage = is_file(public_path('images/landing/tameshk-canvas-dark.webp'))
        ? asset('images/landing/tameshk-canvas-dark.webp')
        : (is_file(public_path('images/landing/tameshk-canvas-preview.webp'))
            ? asset('images/landing/tameshk-canvas-preview.webp')
            : null);

    $lightImage = is_file(public_path('images/landing/tameshk-canvas-light.webp'))
        ? asset('images/landing/tameshk-canvas-light.webp')
        : $darkImage;
@endphp

<section class="relative isolate w-full overflow-hidden bg-surface-base pt-28 pb-20 font-vazirmatn-rd sm:pt-36 sm:pb-28">
    <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
        <div class="absolute inset-0"
             style="background:
                radial-gradient(45% 40% at 70% 12%, color-mix(in oklab, var(--color-primary) 15%, transparent), transparent 70%),
                radial-gradient(40% 35% at 25% 80%, color-mix(in oklab, #a855f7 10%, transparent), transparent 70%);"></div>
        <div class="buy-n8n-grid absolute inset-0 opacity-65"></div>
    </div>

    <div class="mx-auto flex max-w-5xl flex-col items-center px-4 text-center sm:px-6">
        <div class="inline-flex items-center gap-2 rounded-full border border-line-border bg-surface-card/90 px-3.5 py-1.5 text-xs font-bold text-content-muted shadow-2xs backdrop-blur-md">
            <span class="size-2 rounded-full bg-primary"></span>
            <span>جایگزین ابری، فارسی و بی‌دردسر n8n در ایران</span>
        </div>

        <h1 class="mt-6 max-w-4xl text-3xl font-extrabold leading-[1.55] tracking-tight text-content-main sm:text-5xl sm:leading-[1.35] md:text-6xl">
            خرید اشتراک
            <span class="bg-linear-to-l from-primary via-rose-400 to-primary bg-clip-text text-transparent">
                n8n ایرانی،
            </span>
            <span class="mt-1 block text-2xl sm:mt-2 sm:text-4xl md:text-5xl">
                بدون نیاز به سرور و پرداخت دلاری
            </span>
        </h1>

        <p class="mt-5 max-w-2xl text-sm leading-7 text-content-muted sm:mt-6 sm:text-lg sm:leading-8">
            بدون درگیری با خرید سرور مجازی، نصب داکر و تحریم‌ها؛ در همان محیط بوم بصری مشابه n8n، ایجنت هوش مصنوعی، ربات تلگرام و اتوماسیون‌های خود را بسازید.
        </p>

        <div class="mt-8 flex flex-row flex-wrap items-center justify-center gap-3 sm:mt-10">
            <x-ui.button href="{{ route('register') }}" size="lg" class="shadow-lg shadow-primary/25 transition-transform hover:scale-[1.02]">
                شروع رایگان
            </x-ui.button>
            <x-ui.button href="#comparison" variant="secondary" size="lg" class="backdrop-blur transition hover:bg-surface-card">
                مقایسه با n8n
            </x-ui.button>
        </div>

        @if ($darkImage)
            <div class="mt-14 w-full max-w-5xl sm:mt-16">
                <div class="relative overflow-hidden rounded-2xl border border-line-border bg-surface-card p-1.5 shadow-2xl shadow-black/10 sm:rounded-3xl sm:p-2.5 dark:shadow-black/50">
                    <div class="pointer-events-none absolute inset-x-16 top-0 h-px bg-linear-to-r from-transparent via-primary/50 to-transparent"></div>

                    <img src="{{ $lightImage }}"
                         alt="محیط ویرایشگر بصری تمشک فلو — جایگزین ایرانی n8n"
                         class="block w-full rounded-xl border border-line-border/60 object-cover dark:hidden sm:rounded-2xl"
                         fetchpriority="high">

                    <img src="{{ $darkImage }}"
                         alt="محیط ویرایشگر بصری تمشک فلو — جایگزین ایرانی n8n"
                         class="hidden w-full rounded-xl border border-line-border/60 object-cover dark:block sm:rounded-2xl"
                         fetchpriority="high">
                </div>
            </div>
        @endif
    </div>
</section>

@once
    <style>
        .buy-n8n-grid {
            background-image:
                linear-gradient(color-mix(in oklab, var(--color-line-border) 55%, transparent) 1px, transparent 1px),
                linear-gradient(90deg, color-mix(in oklab, var(--color-line-border) 55%, transparent) 1px, transparent 1px);
            background-size: 32px 32px;
            -webkit-mask-image: radial-gradient(ellipse 70% 55% at 50% 35%, #000 20%, transparent 100%);
            mask-image: radial-gradient(ellipse 70% 55% at 50% 35%, #000 20%, transparent 100%);
        }
    </style>
@endonce
