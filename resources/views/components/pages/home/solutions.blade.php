@php
    use App\Templates\TemplateRepository;

    $templates = array_slice(TemplateRepository::all(), 0, 3);

    $integrations = [
        ['name' => 'تلگرام و بیزینس', 'icon' => 'brand:telegram'],
        ['name' => 'بازوی بله', 'icon' => 'brand:bale'],
        ['name' => 'ووکامرس', 'icon' => 'brand:woocommerce'],
        ['name' => 'Google Gemini', 'icon' => 'brand:gemini'],
        ['name' => 'OpenAI ChatGPT', 'icon' => 'brand:chatgpt'],
        ['name' => 'Anthropic Claude', 'icon' => 'brand:claude'],
        ['name' => 'DeepSeek', 'icon' => 'brand:deepseek'],
        ['name' => 'Groq', 'icon' => 'brand:groq'],
        ['name' => 'OpenRouter', 'icon' => 'brand:openrouter'],
        ['name' => 'Gmail', 'icon' => 'brand:gmail'],
        ['name' => 'Google Sheets', 'icon' => 'brand:google-sheets'],
        ['name' => 'MySQL', 'icon' => 'brand:mysql'],
        ['name' => 'FTP / FTPS', 'icon' => 'server'],
        ['name' => 'خوراک RSS', 'icon' => 'rss'],
        ['name' => 'Webhook و API', 'icon' => 'webhook'],
        ['name' => 'فرم‌ساز آنلاین', 'icon' => 'clipboard-list'],
        ['name' => 'جدول داده داخلی', 'icon' => 'table'],
    ];
@endphp

<section class="w-full border-t border-line-border bg-surface-base py-16 font-vazirmatn-rd sm:py-24">
    <div class="mx-auto max-w-6xl space-y-14 px-4 sm:space-y-18 sm:px-6">

        @if ($templates !== [])
            <div>
                <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-end">
                    <div>
                        <h2 class="text-balance text-xl font-extrabold leading-[1.5] tracking-tight text-content-main sm:text-3xl">
                            ورکفلوهای آماده برای شروع سریع
                        </h2>
                        <p class="mt-2 text-xs leading-6 text-content-muted sm:text-sm">
                            هر قالب را با یک کلیک وارد حساب خود کنید، اتصال سرویس‌ها را انتخاب نمایید و بلافاصله منتشر کنید.
                        </p>
                    </div>

                    <a href="{{ route('templates.index') }}"
                       class="inline-flex shrink-0 items-center gap-1.5 self-start text-xs font-extrabold text-primary hover:underline sm:self-auto sm:text-sm">
                        <span>مشاهده همه قالب‌ها</span>
                        <x-ui.icon name="chevron-left" class="size-4"/>
                    </a>
                </div>

                <div class="mt-7 grid grid-cols-1 gap-5 md:grid-cols-3">
                    @foreach ($templates as $template)
                        <x-templates.card :template="$template"/>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="rounded-3xl border border-line-border bg-surface-card p-6 shadow-2xs sm:p-8">
            <div class="text-center">
                <h2 class="text-balance text-lg font-extrabold leading-[1.5] text-content-main sm:text-2xl">
                    متصل به سرویس‌ها، دیتابیس‌ها و مدل‌های هوش مصنوعی روز
                </h2>
                <p class="mt-2 text-xs leading-6 text-content-muted sm:text-sm">
                    همه ابزارهای پرکاربرد کسب‌وکارها بدون نیاز به پروکسی و تنظیمات پیچیده در بوم آماده‌اند.
                </p>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-center gap-2.5 sm:gap-3">
                @foreach ($integrations as $item)
                    <span class="inline-flex items-center gap-2 rounded-xl border border-line-border bg-surface-base px-3 py-2 text-xs font-bold text-content-main shadow-2xs">
                        <x-ui.icon :name="$item['icon']" class="size-4 shrink-0 text-primary"/>
                        <span>{{ $item['name'] }}</span>
                    </span>
                @endforeach
            </div>
        </div>

    </div>
</section>
