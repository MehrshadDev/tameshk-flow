@php
    $steps = [
        [
            'num' => '۱',
            'icon' => 'key',
            'title' => 'ثبت توکن ربات تلگرام',
            'desc' => 'توکن ربات خود را از BotFather دریافت کرده و با یک کلیک در بخش اعتبارنامه‌های رمزنگاری‌شده ثبت کنید.',
        ],
        [
            'num' => '۲',
            'icon' => 'hierarchy',
            'title' => 'چیدن بلوک‌ها و اتصال هوش مصنوعی',
            'desc' => 'نود شروع‌کننده تلگرام را روی بوم بکشید و به ایجنت هوش مصنوعی، شرط‌های منطقی یا سرویس‌های دلخواه وصل کنید.',
        ],
        [
            'num' => '۳',
            'icon' => 'cloud-upload',
            'title' => 'انتشار ابری و اجرای ۲۴ ساعته',
            'desc' => 'با کلیک روی دکمه انتشار، وب‌هوک ربات به صورت خودکار فعال شده و بدون نیاز به سرور شخصی ۲۴ ساعته کار می‌کند.',
        ],
    ];

    $traditionalPains = [
        'نیاز به پرداخت ۵ تا ۲۵ میلیون تومان دستمزد برنامه‌نویسی پایتون یا PHP',
        'اجاره ماهانه سرور مجازی خارجی و درگیری دائمی با فیلترینگ وب‌هوک تلگرام',
        'نیاز به نصب داکر، وب‌سرور، گواهی SSL و نگهداری فنی دیتابیس',
        'وابستگی کامل به برنامه‌نویس و پرداخت هزینه مجدد برای هر تغییر کوچک در سناریو',
        'دشواری اتصال همزمان به مدل‌های هوش مصنوعی، حافظه گفتگو، دیتابیس‌ها و وب‌سرویس‌ها',
    ];

    $tameshkAdvantages = [
        'شروع کاملاً رایگان در ۳۰ ثانیه؛ پلن‌های حرفه‌ای از ۳۹۰ هزار تومان در ماه',
        'کاملاً ابری و بدون تحریم؛ ثبت خودکار وب‌هوک تلگرام تنها با یک کلیک',
        'محیط گرافیکی Drag & Drop؛ ویرایش آنی منوها، پرامپت و مسیرها توسط خودتان',
        'ایجنت هوش مصنوعی آماده با پشتیبانی از Gemini، ChatGPT، Claude و DeepSeek',
        'مجهز به تقویم شمسی، تولید کد تایید، جدول داده داخلی و پشتیبانی تیکتی فارسی',
    ];
@endphp

<section id="comparison" class="w-full border-t border-line-border bg-surface-base py-20 font-vazirmatn-rd sm:py-24">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">

        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-2xl font-extrabold tracking-tight text-content-main sm:text-4xl">
                راه‌اندازی ربات در ۳ گام ساده
            </h2>
            <p class="mt-3 text-sm leading-7 text-content-muted sm:text-base">
                از دریافت توکن تا روشن شدن ربات هوشمند روی فضای ابری، کمتر از ۵ دقیقه فاصله دارید.
            </p>
        </div>

        <div class="mt-10 grid grid-cols-1 gap-5 md:grid-cols-3">
            @foreach ($steps as $item)
                <div class="flex flex-col justify-between rounded-2xl border border-line-border bg-surface-card p-6 shadow-2xs">
                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex size-11 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                <x-ui.icon :name="$item['icon']" class="size-5.5"/>
                            </div>
                            <span class="flex size-7 items-center justify-center rounded-full border border-line-border bg-surface-base font-mono text-xs font-extrabold text-content-muted">
                                {{ $item['num'] }}
                            </span>
                        </div>

                        <h3 class="mt-5 text-base font-extrabold text-content-main">
                            {{ $item['title'] }}
                        </h3>

                        <p class="mt-2.5 text-xs leading-6 text-content-muted">
                            {{ $item['desc'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-20 mx-auto max-w-2xl text-center">
            <h2 class="text-2xl font-extrabold tracking-tight text-content-main sm:text-4xl">
                کدنویسی سنتی یا ساخت بصری در تمشک فلو؟
            </h2>
            <p class="mt-3 text-sm leading-7 text-content-muted sm:text-base">
                ببینید چرا ساخت ربات در بوم ابری تمشک فلو میلیون‌ها تومان در هزینه و هفته‌ها در زمان شما صرفه‌جویی می‌کند.
            </p>
        </div>

        <div class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-2">

            <div class="flex flex-col justify-between rounded-2xl border border-line-border bg-surface-card p-5 shadow-2xs sm:p-7">
                <div>
                    <div class="flex items-center gap-3 border-b border-line-border/70 pb-4">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-rose-500/10 text-rose-500">
                            <x-ui.icon name="terminal-2" class="size-5"/>
                        </span>
                        <div class="min-w-0">
                            <h3 class="text-sm font-extrabold text-content-main sm:text-base">روش سنتی (کدنویسی و اجاره سرور)</h3>
                            <p class="mt-0.5 text-xs text-content-muted">برون‌سپاری پروژه یا توسعه دستی با پایتون و PHP</p>
                        </div>
                    </div>

                    <ul class="mt-5 space-y-3.5">
                        @foreach ($traditionalPains as $pain)
                            <li class="flex items-start gap-2.5 text-xs leading-6 text-content-muted sm:text-sm">
                                <span class="mt-1 flex size-5 shrink-0 items-center justify-center rounded-full bg-rose-500/15 text-rose-500">
                                    <x-ui.icon name="x" class="size-3"/>
                                </span>
                                <span>{{ $pain }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="mt-6 rounded-xl border border-line-border/70 bg-surface-base px-4 py-3 text-center text-xs leading-6 text-content-muted">
                    زمان راه‌اندازی: <strong class="text-content-main">۲ تا ۴ هفته</strong> • هزینه: <strong class="text-content-main">چندین میلیون تومان + هزینه VPS</strong>
                </div>
            </div>

            <div class="relative flex flex-col justify-between rounded-2xl border border-primary/70 bg-surface-card p-5 shadow-xl shadow-primary/5 ring-1 ring-primary/20 sm:p-7">
                <div class="pointer-events-none absolute inset-x-10 top-0 h-px bg-linear-to-r from-transparent via-primary to-transparent"></div>

                <div>
                    <div class="flex items-center gap-3 border-b border-line-border/70 pb-4">
                        <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-500">
                            <x-ui.icon name="bolt" class="size-5"/>
                        </span>
                        <div class="min-w-0">
                            <h3 class="text-sm font-extrabold text-content-main sm:text-base">ربات‌ساز ابری تمشک فلو</h3>
                            <p class="mt-0.5 text-xs text-content-muted">طراحی بصری بدون کدنویسی + زیرساخت ابری آماده</p>
                        </div>
                    </div>

                    <ul class="mt-5 space-y-3.5">
                        @foreach ($tameshkAdvantages as $adv)
                            <li class="flex items-start gap-2.5 text-xs leading-6 font-medium text-content-main sm:text-sm">
                                <span class="mt-1 flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-500/20 text-emerald-500">
                                    <x-ui.icon name="check" class="size-3.5"/>
                                </span>
                                <span>{{ $adv }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="mt-6 flex flex-col items-center justify-between gap-3 rounded-xl border border-emerald-500/30 bg-emerald-500/8 px-4 py-3 sm:flex-row">
                    <span class="text-xs font-bold text-content-main">
                        زمان راه‌اندازی: <strong class="text-emerald-600 dark:text-emerald-400">کمتر از ۵ دقیقه</strong>
                    </span>
                    <x-ui.button href="{{ route('register') }}" size="md" class="w-full sm:w-auto shadow-sm shadow-primary/20">
                        <span>ساخت رایگان ربات</span>
                        <x-ui.icon name="chevron-left" class="size-4"/>
                    </x-ui.button>
                </div>
            </div>

        </div>
    </div>
</section>
