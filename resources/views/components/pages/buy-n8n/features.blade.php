@php
    $items = [
        [
            'icon' => 'bolt',
            'color' => 'bg-emerald-500/10 text-emerald-500',
            'title' => 'بدون نیاز به سرور، داکر و SSL',
            'desc' => 'نیازی به اجاره ماهانه VPS خارجی، کانفیگ لینوکس، نصب Docker و نگهداری دیتابیس ندارید. همه چیز روی کلود آماده است.',
        ],
        [
            'icon' => 'brand:ai-agent',
            'color' => 'bg-purple-500/10 text-purple-500',
            'title' => 'ایجنت هوش مصنوعی و مدل‌های روز',
            'desc' => 'اتصال آماده به Gemini، ChatGPT، Claude، DeepSeek، Groq و OpenRouter به همراه حافظه گفتگو و ابزارهای اجرایی.',
        ],
        [
            'icon' => 'brand:telegram',
            'color' => 'bg-sky-500/10 text-sky-500',
            'title' => 'تلگرام، ووکامرس و گوگل بدون تحریم',
            'desc' => 'ثبت خودکار وب‌هوک ربات و اکانت بیزینس تلگرام، مدیریت سفارش‌های ووکامرس، جیمیل و گوگل شیت بدون نیاز به پروکسی.',
        ],
        [
            'icon' => 'calendar',
            'color' => 'bg-amber-500/10 text-amber-500',
            'title' => 'بومی‌سازی‌شده برای کسب‌وکار ایرانی',
            'desc' => 'مجهز به نود تقویم شمسی (جلالی)، تبدیل ارقام فارسی، تولید کد تایید (OTP)، محیط راست‌به‌چپ و پرداخت ریالی.',
        ],
    ];
@endphp

<section id="features" class="w-full border-t border-line-border bg-surface-base py-20 font-vazirmatn-rd sm:py-24">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-2xl font-extrabold tracking-tight text-content-main sm:text-4xl">
                چرا کاربران ایرانی به جای سرور n8n، تمشک فلو را انتخاب می‌کنند؟
            </h2>
            <p class="mt-3 text-sm leading-7 text-content-muted sm:text-base">
                تمرکز شما باید روی ساختن منطق کسب‌وکارتان باشد، نه درگیری با خطاهای سرور و تحریم‌ها.
            </p>
        </div>

        <div class="mt-12 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($items as $item)
                <div class="flex flex-col justify-between rounded-2xl border border-line-border bg-surface-card p-6 shadow-2xs transition hover:border-content-muted/30">
                    <div>
                        <div class="flex size-12 items-center justify-center rounded-2xl {{ $item['color'] }}">
                            <x-ui.icon :name="$item['icon']" class="size-6"/>
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
    </div>
</section>
