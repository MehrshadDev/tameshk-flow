@php
    $rows = [
        [
            'label' => 'هزینه شروع و پرداخت',
            'tameshk' => 'شروع رایگان / پرداخت ریالی (از ۳۹۰ هزار تومان)',
            'cloud' => 'حداقل ۲۴ یورو در ماه (~۲.۵ میلیون تومان)',
            'self' => 'هزینه اجاره ماهانه VPS خارجی + دامنه',
        ],
        [
            'label' => 'نصب و نگهداری فنی',
            'tameshk' => 'صفر — ابری و آماده در ۳۰ ثانیه',
            'cloud' => 'ابری (نیازمند کارت ارزی و آی‌پی غیرایرانی)',
            'self' => 'نیازمند لینوکس، Docker، SSL و مدیریت دیتابیس',
        ],
        [
            'label' => 'وب‌هوک تلگرام و سرویس‌های گوگل',
            'tameshk' => 'متصل و بدون نیاز به پروکسی با یک کلیک',
            'cloud' => 'مسدود برای IP ایران و نیازمند وریفای ارزی',
            'self' => 'نیازمند تنظیم Reverse Proxy و رفع فیلترینگ',
        ],
        [
            'label' => 'ایجنت هوش مصنوعی (AI Agent)',
            'tameshk' => 'آماده (Gemini, GPT, Claude, DeepSeek, Groq)',
            'cloud' => 'موجود در پلتفرم',
            'self' => 'موجود در پلتفرم',
        ],
        [
            'label' => 'تقویم شمسی و ابزارهای بومی',
            'tameshk' => 'نود اختصاصی تاریخ جلالی، کد OTP و اعداد فارسی',
            'cloud' => 'ندارد (فقط تقویم میلادی و محیط انگلیسی)',
            'self' => 'ندارد (نیازمند کدنویسی جاوااسکریپت دستی)',
        ],
        [
            'label' => 'پشتیبانی فنی',
            'tameshk' => 'پشتیبانی مستقیم فارسی از طریق تیکت',
            'cloud' => 'انگلیسی (محدود به اکانت‌های غیرتحریمی)',
            'self' => 'بدون پشتیبانی رسمی (جستجو در انجمن‌ها)',
        ],
    ];
@endphp

<section id="comparison" class="w-full border-t border-line-border bg-surface-base py-20 font-vazirmatn-rd sm:py-24">
    <div class="mx-auto max-w-5xl px-4 sm:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-2xl font-extrabold tracking-tight text-content-main sm:text-4xl">
                مقایسه صادقانه: تمشک فلو یا خرید n8n؟
            </h2>
            <p class="mt-3 text-sm leading-7 text-content-muted sm:text-base">
                ببینید کدام گزینه با نیاز، زمان و بودجه شما همخوانی بیشتری دارد.
            </p>
        </div>

        <div class="mt-10 space-y-3 md:hidden">
            @foreach ($rows as $row)
                <div class="rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs space-y-3">
                    <h3 class="text-xs font-extrabold text-content-main border-b border-line-border/60 pb-2.5">
                        {{ $row['label'] }}
                    </h3>

                    <div class="space-y-2 text-xs">
                        <div class="flex items-start justify-between gap-3 rounded-xl bg-primary/6 p-2.5">
                            <span class="shrink-0 font-bold text-primary">تمشک فلو:</span>
                            <span class="text-end font-bold text-content-main">{{ $row['tameshk'] }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3 px-2 py-1">
                            <span class="shrink-0 font-medium text-content-muted">n8n Cloud:</span>
                            <span class="text-end text-content-muted">{{ $row['cloud'] }}</span>
                        </div>

                        <div class="flex items-start justify-between gap-3 px-2 py-1">
                            <span class="shrink-0 font-medium text-content-muted">سرور شخصی n8n:</span>
                            <span class="text-end text-content-muted">{{ $row['self'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-12 hidden overflow-hidden rounded-2xl border border-line-border bg-surface-card shadow-2xs md:block">
            <table class="w-full text-start text-sm">
                <thead>
                <tr class="border-b border-line-border bg-surface-raised/70">
                    <th class="w-1/4 px-5 py-4 text-start font-extrabold text-content-muted">ویژگی</th>
                    <th class="w-1/4 bg-primary/6 px-5 py-4 text-start font-extrabold text-primary">
                            <span class="inline-flex items-center gap-1.5">
                                <span class="size-2 rounded-full bg-primary"></span>
                                <span>تمشک فلو (ابری)</span>
                            </span>
                    </th>
                    <th class="w-1/4 px-5 py-4 text-start font-bold text-content-main">اکانت n8n Cloud</th>
                    <th class="w-1/4 px-5 py-4 text-start font-bold text-content-main">سرور شخصی n8n</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-line-border/60">
                @foreach ($rows as $row)
                    <tr class="transition hover:bg-surface-raised/30">
                        <td class="px-5 py-4 font-bold text-content-main">
                            {{ $row['label'] }}
                        </td>
                        <td class="bg-primary/4 px-5 py-4 font-bold text-content-main">
                            <div class="flex items-start gap-2">
                                <x-ui.icon name="check" class="mt-0.5 size-4 shrink-0 text-emerald-500"/>
                                <span>{{ $row['tameshk'] }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-content-muted">
                            {{ $row['cloud'] }}
                        </td>
                        <td class="px-5 py-4 text-content-muted">
                            {{ $row['self'] }}
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
            <div class="rounded-2xl border border-emerald-500/30 bg-emerald-500/5 p-6">
                <div class="flex items-center gap-2.5">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-500">
                        <x-ui.icon name="check" class="size-4.5"/>
                    </span>
                    <h3 class="text-sm font-extrabold text-content-main">تمشک فلو برای شما بهترین انتخاب است اگر:</h3>
                </div>
                <p class="mt-3 text-xs leading-7 text-content-muted">
                    می‌خواهید بدون درگیری با سرور لینوکس و تحریم‌ها، سریعاً ربات تلگرام، ایجنت هوشمند، چت‌بات سایت یا اتوماسیون ووکامرس، گوگل شیت، جیمیل و وب‌هوک بسازید و به محیط فارسی و تاریخ شمسی نیاز دارید.
                </p>
            </div>

            <div class="rounded-2xl border border-line-border bg-surface-card p-6">
                <div class="flex items-center gap-2.5">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-line-border/60 text-content-muted">
                        <x-ui.icon name="terminal-2" class="size-4.5"/>
                    </span>
                    <h3 class="text-sm font-extrabold text-content-main">نصب n8n روی سرور برای شما مناسب‌تر است اگر:</h3>
                </div>
                <p class="mt-3 text-xs leading-7 text-content-muted">
                    دانش فنی مدیریت سرور و داکر دارید و ورک‌فلوی شما به کانکتورهای سازمانی خاص خارجی (مانند Salesforce، Jira یا AWS) وابسته است که هنوز در تمشک فلو اضافه نشده‌اند.
                </p>
            </div>
        </div>
    </div>
</section>
