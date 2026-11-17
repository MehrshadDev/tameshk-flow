@php
    $demos = [
        [
            'tab' => 'پاسخگوی هوشمند',
            'type' => 'text',
            'user_time' => '14:28',
            'bot_time' => '14:28',
            'user_text' => 'سلام، اشتراک پرو چه امکاناتی داره و چطور می‌تونم فعالش کنم؟',
            'bot_reply' => "سلام وقت بخیر 👋\nدر پلن پرو ماهانه ۱۰٬۰۰۰ اجرا و ۱۰ ورک‌فلو فعال همزمان در اختیار دارید.\n\nگزینه مورد نظر خود را انتخاب کنید:",
            'buttons' => [
                ['label' => '💳 خرید آنی اشتراک', 'type' => 'url'],
                ['label' => '📊 مقایسه پلن‌ها', 'type' => 'callback'],
            ],
            'steps' => [
                ['name' => 'Telegram Trigger', 'desc' => 'دریافت پیام از ربات یا اکانت بیزینس', 'icon' => 'brand:telegram', 'badge_bg' => 'bg-sky-500/15 text-sky-500 border-sky-500/30', 'time' => '45 ms'],
                ['name' => 'AI Agent + Memory', 'desc' => 'پاسخ هوشمند با حافظه اختصاصی کاربر', 'icon' => 'brand:ai-agent', 'badge_bg' => 'bg-purple-500/15 text-purple-500 border-purple-500/30', 'time' => '680 ms'],
                ['name' => 'Telegram Send', 'desc' => 'ارسال پاسخ نهایی در چت تلگرام', 'icon' => 'brand:telegram', 'badge_bg' => 'bg-sky-500/15 text-sky-500 border-sky-500/30', 'time' => '90 ms'],
            ],
        ],
        [
            'tab' => 'پردازش ویس',
            'type' => 'voice',
            'voice_duration' => '0:14',
            'user_time' => '18:05',
            'bot_time' => '18:05',
            'user_text' => '«سلام، می‌خواستم برای فردا ساعت ۵ عصر وقت مشاوره رزرو کنم...»',
            'bot_reply' => "🎙 پیام صوتی شما تبدیل به متن و بررسی شد!\n\n✅ نوبت مشاوره برای فردا ساعت ۱۷:۰۰ ثبت گردید.\nکد پیگیری: TMK-8492",
            'buttons' => [
                ['label' => '📅 تغییر ساعت نوبت', 'type' => 'callback'],
                ['label' => '❌ لغو درخواست', 'type' => 'callback'],
            ],
            'steps' => [
                ['name' => 'Telegram Get File', 'desc' => 'دانلود خودکار فایل صوتی از تلگرام', 'icon' => 'brand:telegram', 'badge_bg' => 'bg-sky-500/15 text-sky-500 border-sky-500/30', 'time' => '180 ms'],
                ['name' => 'Google Gemini Audio', 'desc' => 'تبدیل گفتار فارسی به متن و استخراج زمان', 'icon' => 'brand:gemini', 'badge_bg' => 'bg-blue-500/15 text-blue-500 border-blue-500/30', 'time' => '920 ms'],
                ['name' => 'Data Table & Reply', 'desc' => 'ذخیره نوبت در جدول داده و ارسال تاییدیه', 'icon' => 'table', 'badge_bg' => 'bg-teal-500/15 text-teal-500 border-teal-500/30', 'time' => '65 ms'],
            ],
        ],
        [
            'tab' => 'استعلام سفارش',
            'type' => 'api',
            'user_time' => '21:12',
            'bot_time' => '21:12',
            'user_text' => 'وضعیت سفارش شماره 2094 من در چه مرحله‌ای هست؟',
            'bot_reply' => "📦 نتیجه استعلام لحظه‌ای از فروشگاه:\n\nسفارش #2094 شما «تکمیل‌شده» و تحویل پست پیشتاز شده است.\n📮 کد رهگیری: 2984019948201",
            'buttons' => [
                ['label' => '🔍 رهگیری مرسوله', 'type' => 'url'],
                ['label' => '🧾 دریافت فاکتور', 'type' => 'callback'],
            ],
            'steps' => [
                ['name' => 'Telegram Trigger', 'desc' => 'دریافت شماره سفارش از پیام کاربر', 'icon' => 'brand:telegram', 'badge_bg' => 'bg-sky-500/15 text-sky-500 border-sky-500/30', 'time' => '40 ms'],
                ['name' => 'Store & DB Tool', 'desc' => 'جستجوی خودکار در دیتابیس یا وب‌سرویس', 'icon' => 'brand:woocommerce', 'badge_bg' => 'bg-violet-500/15 text-violet-500 border-violet-500/30', 'time' => '310 ms'],
                ['name' => 'Telegram Send', 'desc' => 'ارسال وضعیت سفارش و کد رهگیری', 'icon' => 'brand:telegram', 'badge_bg' => 'bg-sky-500/15 text-sky-500 border-sky-500/30', 'time' => '85 ms'],
            ],
        ],
    ];
@endphp

<section x-data="{ activeDemo: 0, demos: @js($demos) }"
         class="relative isolate w-full overflow-hidden bg-surface-base pt-28 pb-20 font-vazirmatn-rd sm:pt-36 sm:pb-28">
    <div class="pointer-events-none absolute inset-0 -z-10" aria-hidden="true">
        <div class="absolute inset-0"
             style="background:
                radial-gradient(45% 40% at 70% 12%, color-mix(in oklab, var(--color-primary) 15%, transparent), transparent 70%),
                radial-gradient(40% 35% at 25% 80%, color-mix(in oklab, #0ea5e9 12%, transparent), transparent 70%);"></div>
        <div class="bot-hero-grid absolute inset-0 opacity-65"></div>
    </div>

    <div class="mx-auto flex max-w-5xl flex-col items-center px-4 text-center sm:px-6">
        <div class="inline-flex items-center gap-2 rounded-full border border-line-border bg-surface-card/90 px-3.5 py-1.5 text-xs font-bold text-content-muted shadow-2xs backdrop-blur-md">
            <span class="size-2 rounded-full bg-primary"></span>
            <span>ربات‌ساز ابری و بصری تلگرام + ایجنت هوش مصنوعی</span>
        </div>

        <h1 class="mt-6 max-w-4xl text-3xl font-extrabold leading-[1.55] tracking-tight text-content-main sm:text-5xl sm:leading-[1.35] md:text-6xl">
            ساخت ربات تلگرام با
            <span class="bg-linear-to-l from-primary via-rose-400 to-primary bg-clip-text text-transparent">
                هوش مصنوعی،
            </span>
            <span class="mt-1 block text-2xl sm:mt-2 sm:text-4xl md:text-5xl">
                بدون کدنویسی و بدون نیاز به سرور
            </span>
        </h1>

        <p class="mt-5 max-w-2xl text-sm leading-7 text-content-muted sm:mt-6 sm:text-lg sm:leading-8">
            بدون نیاز به برنامه‌نویسی پایتون یا اجاره سرور مجازی؛ تنها با کشیدن و رها کردن بلوک‌ها، ربات تلگرام و اکانت بیزینس خود را به مدل‌های هوش مصنوعی، دیتابیس‌ها و هر وب‌سرویس یا API دلخواه متصل کنید.
        </p>

        <div class="mt-8 flex flex-row flex-wrap items-center justify-center gap-3 sm:mt-10">
            <x-ui.button href="{{ route('register') }}" size="lg" class="shadow-lg shadow-primary/25 transition-transform hover:scale-[1.02]">
                شروع رایگان
            </x-ui.button>
            <x-ui.button href="#comparison" variant="secondary" size="lg" class="backdrop-blur transition hover:bg-surface-card">
                مقایسه با کدنویسی سنتی
            </x-ui.button>
        </div>

        <div class="mt-12 w-full max-w-5xl sm:mt-16">
            <div class="mx-auto mb-4 grid max-w-md grid-cols-3 gap-1 rounded-2xl border border-line-border bg-surface-card p-1 shadow-2xs">
                @foreach ($demos as $idx => $demo)
                    <button type="button"
                            @click="activeDemo = {{ $idx }}"
                            :class="activeDemo === {{ $idx }}
                                ? 'bg-primary text-white font-extrabold shadow-2xs'
                                : 'text-content-muted font-semibold hover:text-content-main'"
                            class="rounded-xl py-2 text-center text-xs whitespace-nowrap transition cursor-pointer sm:py-2.5">
                        {{ $demo['tab'] }}
                    </button>
                @endforeach
            </div>

            <div class="relative overflow-hidden rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xl shadow-black/10 sm:rounded-3xl sm:p-5 dark:shadow-black/50">
                <div class="pointer-events-none absolute inset-x-16 top-0 h-px bg-linear-to-r from-transparent via-primary/50 to-transparent"></div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-stretch lg:gap-5">

                    <div class="flex flex-col justify-between overflow-hidden rounded-2xl border border-line-border bg-surface-raised text-start lg:col-span-6">
                        <div class="flex items-center justify-between gap-2 border-b border-line-border/70 bg-surface-card px-4 py-3">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <div class="relative flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-500/15 text-sky-500">
                                    <x-ui.icon name="brand:telegram" class="size-5"/>
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-xs font-extrabold text-content-main">دستیار هوشمند تلگرام</p>
                                    <p class="truncate text-[11px] text-emerald-500">آنلاین • پاسخگویی خودکار ابری</p>
                                </div>
                            </div>

                            <span class="shrink-0 whitespace-nowrap rounded-lg border border-line-border bg-surface-base px-2.5 py-1 text-[10px] font-bold text-content-muted">
                                پیش‌نمایش زنده
                            </span>
                        </div>

                        <div class="flex flex-1 flex-col justify-center space-y-3 bg-surface-base/60 p-3.5 text-xs sm:p-5">
                            <div class="me-auto w-fit max-w-[88%] rounded-2xl rounded-tr-xs bg-primary px-3.5 py-2.5 text-start text-white shadow-xs">
                                <template x-if="demos[activeDemo].type === 'voice'">
                                    <div class="mb-2 flex items-center gap-2 border-b border-white/20 pb-2">
                                        <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-white text-primary">
                                            <x-ui.icon name="player-play" class="size-3"/>
                                        </span>
                                        <div class="flex flex-1 items-center gap-1" dir="ltr">
                                            <span class="h-2 w-1 rounded-full bg-white/80"></span>
                                            <span class="h-3.5 w-1 rounded-full bg-white"></span>
                                            <span class="h-4 w-1 rounded-full bg-white"></span>
                                            <span class="h-2.5 w-1 rounded-full bg-white/90"></span>
                                            <span class="h-4 w-1 rounded-full bg-white"></span>
                                            <span class="h-3 w-1 rounded-full bg-white/80"></span>
                                            <span class="ms-1.5 font-mono text-[10px]" x-text="demos[activeDemo].voice_duration"></span>
                                        </div>
                                    </div>
                                </template>

                                <p class="leading-6" x-text="demos[activeDemo].user_text"></p>

                                <div dir="ltr" class="mt-1 flex items-center justify-end gap-1 font-mono text-[10px] text-white/80">
                                    <span x-text="demos[activeDemo].user_time"></span>
                                    <span>✓✓</span>
                                </div>
                            </div>

                            <div class="ms-auto w-full max-w-[92%] space-y-1.5">
                                <div class="rounded-2xl rounded-tl-xs border border-line-border bg-surface-card px-3.5 py-2.5 text-start text-content-main shadow-xs sm:px-4 sm:py-3">
                                    <p class="leading-6 whitespace-pre-line" x-text="demos[activeDemo].bot_reply"></p>
                                    <div dir="ltr" class="mt-1 flex justify-end font-mono text-[10px] text-content-muted">
                                        <span x-text="demos[activeDemo].bot_time"></span>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-1.5">
                                    <template x-for="(btn, bIdx) in demos[activeDemo].buttons" :key="bIdx">
                                        <div class="relative flex items-center justify-center rounded-xl border border-line-border bg-surface-card/90 px-2.5 py-2 text-center text-[11px] font-bold text-content-main shadow-2xs">
                                            <span class="truncate" x-text="btn.label"></span>
                                            <template x-if="btn.type === 'url'">
                                                <span class="absolute top-1 left-1.5 text-[9px] text-content-muted" dir="ltr">↗</span>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bot-canvas-dots flex flex-col justify-between rounded-2xl border border-line-border bg-surface-base/80 p-3.5 sm:p-5 lg:col-span-6">
                        <div class="flex items-center justify-between gap-2 border-b border-line-border/70 pb-3">
                            <span class="flex min-w-0 items-center gap-2 text-xs font-extrabold text-content-main">
                                <x-ui.icon name="hierarchy" class="size-4 shrink-0 text-primary"/>
                                <span class="truncate">مسیر اجرای این پیام در بوم</span>
                            </span>
                            <span class="shrink-0 whitespace-nowrap rounded-md bg-emerald-500/15 px-2 py-0.5 text-[10px] font-bold text-emerald-500">
                                ۳ نود فعال
                            </span>
                        </div>

                        <div dir="ltr" class="my-4 flex flex-col text-left">
                            @foreach ([0, 1, 2] as $stepIdx)
                                <div class="flex items-center justify-between gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:p-3.5">
                                    <div class="flex min-w-0 items-center gap-3">
                                        @foreach ($demos as $dIdx => $d)
                                            <div x-show="activeDemo === {{ $dIdx }}"
                                                 @if ($dIdx > 0) x-cloak @endif
                                                 class="flex size-10 shrink-0 items-center justify-center rounded-xl border {{ $d['steps'][$stepIdx]['badge_bg'] }}">
                                                <x-ui.icon :name="$d['steps'][$stepIdx]['icon']" class="size-5"/>
                                            </div>
                                        @endforeach

                                        <div class="min-w-0 text-left">
                                            <p class="truncate font-mono text-xs font-extrabold text-content-main"
                                               x-text="demos[activeDemo].steps[{{ $stepIdx }}].name"></p>
                                            <p dir="rtl"
                                               class="mt-0.5 text-left text-[11px] leading-5 font-medium text-content-muted"
                                               x-text="demos[activeDemo].steps[{{ $stepIdx }}].desc"></p>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-1.5">
                                        <span class="whitespace-nowrap font-mono text-[10px] font-semibold text-content-muted"
                                              x-text="demos[activeDemo].steps[{{ $stepIdx }}].time"></span>
                                        <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-white">
                                            <x-ui.icon name="check" class="size-3"/>
                                        </span>
                                    </div>
                                </div>

                                @if ($stepIdx < 2)
                                    <div class="pl-3 sm:pl-3.5">
                                        <div class="flex w-10 justify-center">
                                            <div class="h-3.5 w-0.5 bg-emerald-500/50"></div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>

                        <div class="flex items-center justify-between gap-2 rounded-xl border border-line-border/70 bg-surface-card px-3.5 py-2.5 text-[11px] text-content-muted">
                            <span class="truncate">قابل توسعه با بیش از ۳۰ نود کاربردی</span>
                            <a href="{{ route('register') }}" class="shrink-0 whitespace-nowrap font-extrabold text-primary hover:underline">
                                ساخت رایگان ←
                            </a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

@once
    <style>
        .bot-hero-grid {
            background-image:
                linear-gradient(color-mix(in oklab, var(--color-line-border) 55%, transparent) 1px, transparent 1px),
                linear-gradient(90deg, color-mix(in oklab, var(--color-line-border) 55%, transparent) 1px, transparent 1px);
            background-size: 32px 32px;
            -webkit-mask-image: radial-gradient(ellipse 70% 55% at 50% 35%, #000 20%, transparent 100%);
            mask-image: radial-gradient(ellipse 70% 55% at 50% 35%, #000 20%, transparent 100%);
        }

        .bot-canvas-dots {
            background-image: radial-gradient(color-mix(in oklab, var(--color-content-muted) 22%, transparent) 1.25px, transparent 1.25px);
            background-size: 18px 18px;
        }
    </style>
@endonce
