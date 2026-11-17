@php
    $darkImage = is_file(public_path('images/landing/tameshk-canvas-dark.webp'))
        ? asset('images/landing/tameshk-canvas-dark.webp')
        : null;

    $lightImage = is_file(public_path('images/landing/tameshk-canvas-light.webp'))
        ? asset('images/landing/tameshk-canvas-light.webp')
        : $darkImage;

    $cards = [
        [
            'icon' => 'brand:ai-agent',
            'color' => 'bg-purple-500/10 text-purple-500',
            'title' => 'ایجنت هوش مصنوعی و حافظه گفتگو',
            'desc' => 'اتصال ربات به مدل‌های Gemini، ChatGPT، Claude، DeepSeek و Groq. ربات مکالمات قبلی هر کاربر تلگرام را به صورت مجزا در حافظه نگه می‌دارد.',
        ],
        [
            'icon' => 'brand:telegram',
            'color' => 'bg-sky-500/10 text-sky-500',
            'title' => 'ربات تلگرام و اکانت شخصی بیزینس',
            'desc' => 'پشتیبانی کامل از ربات‌های تلگرام، اتصال مستقیم به اکانت شخصی تلگرام بیزینس، مدیریت کانال و گروه و ساخت منوهای تعاملی.',
        ],
        [
            'icon' => 'brand:gemini',
            'color' => 'bg-blue-500/10 text-blue-500',
            'title' => 'تبدیل ویس به متن و تحلیل فایل‌ها',
            'desc' => 'دانلود خودکار ویس‌ها، تصاویر و اسناد PDF ارسالی کاربر در تلگرام و تبدیل دقیق گفتار فارسی به متن یا خلاصه‌سازی محتوا.',
        ],
        [
            'icon' => 'world',
            'color' => 'bg-emerald-500/10 text-emerald-500',
            'title' => 'اتصال به دیتابیس‌ها، سرویس‌ها و هر API',
            'desc' => 'مجهز به جدول داده داخلی، گوگل شیت، جیمیل، ووکامرس، MySQL و امکان اتصال به هر وب‌سرویس، سامانه پیامکی یا CRM از طریق HTTP و Webhook.',
        ],
    ];
@endphp

<section id="features" class="w-full border-t border-line-border bg-surface-base py-20 font-vazirmatn-rd sm:py-24">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-2xl font-extrabold tracking-tight text-content-main sm:text-4xl">
                طراحی بصری منطق ربات، بدون یک خط کدنویسی
            </h2>
            <p class="mt-3 text-sm leading-7 text-content-muted sm:text-base">
                ببینید چطور در محیط بوم تمشک فلو، شروع‌کننده تلگرام به ایجنت هوش مصنوعی، حافظه گفتگو و ابزارهای اجرایی متصل می‌شود.
            </p>
        </div>

        @if ($darkImage)
            <div class="mt-10 sm:mt-12">
                <div class="relative overflow-hidden rounded-2xl border border-line-border bg-surface-card p-1.5 shadow-2xl shadow-black/10 sm:rounded-3xl sm:p-2.5 dark:shadow-black/50">
                    <div class="pointer-events-none absolute inset-x-16 top-0 h-px bg-linear-to-r from-transparent via-primary/50 to-transparent"></div>

                    <img src="{{ $lightImage }}"
                         alt="بوم بصری ساخت ربات تلگرام و ایجنت هوش مصنوعی در تمشک فلو"
                         class="block w-full rounded-xl border border-line-border/60 object-cover dark:hidden sm:rounded-2xl"
                         loading="lazy">

                    <img src="{{ $darkImage }}"
                         alt="بوم بصری ساخت ربات تلگرام و ایجنت هوش مصنوعی در تمشک فلو"
                         class="hidden w-full rounded-xl border border-line-border/60 object-cover dark:block sm:rounded-2xl"
                         loading="lazy">
                </div>
            </div>
        @endif

        <div class="mt-12 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($cards as $card)
                <div class="flex flex-col justify-between rounded-2xl border border-line-border bg-surface-card p-6 shadow-2xs transition hover:border-content-muted/30">
                    <div>
                        <div class="flex size-12 items-center justify-center rounded-2xl {{ $card['color'] }}">
                            <x-ui.icon :name="$card['icon']" class="size-6"/>
                        </div>

                        <h3 class="mt-5 text-base font-extrabold text-content-main">
                            {{ $card['title'] }}
                        </h3>

                        <p class="mt-2.5 text-xs leading-6 text-content-muted">
                            {{ $card['desc'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
