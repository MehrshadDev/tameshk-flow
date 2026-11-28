@php
    $rows = [
        [
            'label' => 'هزینه و پرداخت',
            'tameshk' => 'رایگان / ریالی از ۳۹۰ هزار تومان',
            'cloud' => '۲۴ یورو ماهانه (کارت ارزی)',
            'self' => 'اجاره ماهانه VPS + دامنه',
        ],
        [
            'label' => 'نصب و نگهداری',
            'tameshk' => 'ابری و آماده در ۳۰ ثانیه',
            'cloud' => 'ابری (نیازمند آی‌پی غیرایرانی)',
            'self' => 'نصب دستی Docker و SSL',
        ],
        [
            'label' => 'تلگرام و گوگل',
            'tameshk' => 'مستقیم و بدون نیاز به پروکسی',
            'cloud' => 'مسدود برای IP ایران',
            'self' => 'درگیری با فیلترینگ و پروکسی',
        ],
        [
            'label' => 'ایجنت هوش مصنوعی',
            'tameshk' => 'Gemini، GPT، Claude، DeepSeek',
            'cloud' => 'موجود در پلتفرم',
            'self' => 'موجود در پلتفرم',
        ],
        [
            'label' => 'ابزارهای فارسی و کاربردی',
            'tameshk' => 'تقویم شمسی، بله، کد OTP',
            'cloud' => 'فقط میلادی و انگلیسی',
            'self' => 'نیازمند کدنویسی دستی',
        ],
        [
            'label' => 'پشتیبانی فنی',
            'tameshk' => 'پشتیبانی تیکتی فارسی',
            'cloud' => 'انگلیسی (اکانت غیرایرانی)',
            'self' => 'بدون پشتیبانی رسمی',
        ],
    ];
@endphp

<section id="comparison" class="w-full border-t border-line-border bg-surface-base py-20 font-vazirmatn-rd sm:py-24">
    <div class="mx-auto max-w-6xl px-4 sm:px-6">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-balance text-2xl font-extrabold tracking-tight text-content-main sm:text-4xl">
                تمشک فلو یا خرید <span dir="ltr" class="inline-block">n8n</span>&rlm;؟
            </h2>
            <p class="mt-3 text-sm leading-7 text-content-muted sm:text-base">
                ببینید کدام گزینه با نیاز، زمان و بودجه شما همخوانی بیشتری دارد.
            </p>
        </div>

        <div class="mt-10 space-y-3.5 md:hidden">
            @foreach ($rows as $row)
                <div class="overflow-hidden rounded-2xl border border-line-border bg-surface-card shadow-2xs">
                    <div class="border-b border-line-border/70 bg-surface-raised/60 px-4 py-3">
                        <h3 class="text-xs font-extrabold text-content-main">
                            {{ $row['label'] }}
                        </h3>
                    </div>

                    <div class="space-y-2.5 p-3.5 text-xs">
                        <div class="rounded-xl border border-primary/25 bg-primary/8 p-3">
                            <div class="flex items-center gap-1.5 text-[11px] font-bold text-primary">
                                <span class="size-1.5 rounded-full bg-primary"></span>
                                <span>تمشک فلو (ابری)</span>
                            </div>
                            <div class="mt-1.5 flex items-center gap-2 font-bold text-content-main">
                                <x-ui.icon name="check" class="size-4 shrink-0 text-emerald-500"/>
                                <span>{{ $row['tameshk'] }}</span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-2 rounded-xl border border-line-border/60 bg-surface-base/70 p-3">
                            <div class="flex flex-col gap-0.5">
                                <span class="text-[10px] font-bold text-content-muted/80">
                                    اکانت <span dir="ltr" class="inline-block font-mono">n8n Cloud</span>
                                </span>
                                <span class="font-medium text-content-muted">{{ $row['cloud'] }}</span>
                            </div>

                            <div class="h-px bg-line-border/60"></div>

                            <div class="flex flex-col gap-0.5">
                                <span class="text-[10px] font-bold text-content-muted/80">
                                    سرور شخصی <span dir="ltr" class="inline-block font-mono">n8n</span>
                                </span>
                                <span class="font-medium text-content-muted">{{ $row['self'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-12 hidden overflow-hidden rounded-2xl border border-line-border bg-surface-card shadow-2xs md:block">
            <table class="w-full text-start text-xs lg:text-sm">
                <thead>
                <tr class="border-b border-line-border bg-surface-raised/70">
                    <th class="w-[19%] px-4 py-4 text-start font-extrabold whitespace-nowrap text-content-muted lg:px-5">ویژگی</th>
                    <th class="w-[31%] bg-primary/6 px-4 py-4 text-start font-extrabold whitespace-nowrap text-primary lg:px-5">
                        <span class="inline-flex items-center gap-1.5">
                            <span class="size-2 rounded-full bg-primary"></span>
                            <span>تمشک فلو (ابری)</span>
                        </span>
                    </th>
                    <th class="w-[25%] px-4 py-4 text-start font-bold whitespace-nowrap text-content-main lg:px-5">اکانت n8n Cloud</th>
                    <th class="w-[25%] px-4 py-4 text-start font-bold whitespace-nowrap text-content-main lg:px-5">سرور شخصی n8n</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-line-border/60">
                @foreach ($rows as $row)
                    <tr class="transition hover:bg-surface-raised/30">
                        <td class="px-4 py-3.5 font-bold whitespace-nowrap text-content-main lg:px-5">
                            {{ $row['label'] }}
                        </td>
                        <td class="bg-primary/4 px-4 py-3.5 font-bold text-content-main lg:px-5">
                            <div class="flex items-center gap-2">
                                <x-ui.icon name="check" class="size-4 shrink-0 text-emerald-500"/>
                                <span>{{ $row['tameshk'] }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3.5 text-content-muted lg:px-5">
                            {{ $row['cloud'] }}
                        </td>
                        <td class="px-4 py-3.5 text-content-muted lg:px-5">
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
