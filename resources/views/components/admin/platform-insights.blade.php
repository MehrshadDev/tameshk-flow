@props([
    'insights',
    'platform',
    'revenue',
])

@php
    $plans = $insights['plans'] ?? [];
    $cycles = $insights['cycles'] ?? [];
    $topNodes = $insights['top_nodes'] ?? [];
    $topCredentials = $insights['top_credentials'] ?? [];
@endphp

<div class="grid grid-cols-1 gap-4 lg:grid-cols-3 sm:gap-5">
    <div class="flex flex-col justify-between rounded-2xl border border-line-border bg-surface-card p-4 sm:p-5 shadow-2xs">
        <div class="space-y-4">
            <div class="flex items-center justify-between border-b border-line-border/60 pb-3.5">
                <div class="flex items-center gap-2">
                    <x-ui.icon name="crown" class="size-4.5 text-amber-500"/>
                    <h3 class="text-xs font-extrabold text-content-main">تفکیک درآمد پلن‌ها و دوره‌ها</h3>
                </div>
                <span class="font-mono text-[11px] font-bold text-content-muted">
                    {{ number_format($revenue['total_count']) }} خرید کل
                </span>
            </div>

            @if ($plans === [])
                <p class="py-8 text-center text-xs text-content-muted">هنوز خریدی ثبت نشده است.</p>
            @else
                <div class="space-y-3.5">
                    @foreach ($plans as $planItem)
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between gap-2 text-xs">
                                <span class="font-bold text-content-main">
                                    پلن {{ $planItem['name'] }}
                                    <span class="ms-1 font-mono text-[11px] font-normal text-content-muted">
                                        ({{ number_format($planItem['orders_count']) }} فروش)
                                    </span>
                                </span>
                                <span class="font-mono font-bold text-content-main">
                                    {{ number_format($planItem['amount']) }}
                                    <span class="font-sans text-[10px] font-normal text-content-muted">تومان (%{{ $planItem['percent'] }})</span>
                                </span>
                            </div>

                            <div class="h-2 w-full overflow-hidden rounded-full bg-line-border/60">
                                <div @class([
                                        'h-full rounded-full transition-all duration-300',
                                        'bg-amber-500' => $planItem['slug'] === 'business',
                                        'bg-primary' => $planItem['slug'] !== 'business',
                                    ])
                                     style="width: {{ max(4, $planItem['percent']) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="mt-5 grid grid-cols-2 gap-2.5 border-t border-line-border/60 pt-4 text-xs">
            <div class="rounded-xl border border-line-border/70 bg-surface-base p-3">
                <div class="flex items-center justify-between text-[11px] text-content-muted">
                    <span>دوره ماهانه</span>
                    <span class="font-mono font-bold text-primary">%{{ $cycles['monthly']['percent'] ?? 0 }}</span>
                </div>
                <div class="mt-1.5 flex items-baseline gap-1">
                    <span class="font-mono text-sm font-extrabold text-content-main">{{ number_format($cycles['monthly']['count'] ?? 0) }}</span>
                    <span class="text-[11px] text-content-muted">سفارش</span>
                </div>
            </div>

            <div class="rounded-xl border border-line-border/70 bg-surface-base p-3">
                <div class="flex items-center justify-between text-[11px] text-content-muted">
                    <span>دوره سالانه</span>
                    <span class="font-mono font-bold text-emerald-500">%{{ $cycles['yearly']['percent'] ?? 0 }}</span>
                </div>
                <div class="mt-1.5 flex items-baseline gap-1">
                    <span class="font-mono text-sm font-extrabold text-content-main">{{ number_format($cycles['yearly']['count'] ?? 0) }}</span>
                    <span class="text-[11px] text-content-muted">سفارش</span>
                </div>
            </div>
        </div>
    </div>

    <div class="flex flex-col justify-between rounded-2xl border border-line-border bg-surface-card p-4 sm:p-5 shadow-2xs">
        <div class="space-y-4">
            <div class="flex items-center justify-between border-b border-line-border/60 pb-3.5">
                <div class="flex items-center gap-2">
                    <x-ui.icon name="sparkles" class="size-4.5 text-purple-500"/>
                    <h3 class="text-xs font-extrabold text-content-main">محبوب‌ترین نودها در ورکفلوها</h3>
                </div>
                <span class="font-mono text-[11px] font-bold text-content-muted">
                    {{ number_format($platform['total_nodes']) }} نود کل
                </span>
            </div>

            @if ($topNodes === [])
                <p class="py-8 text-center text-xs text-content-muted">هنوز نودی در سیستم ثبت نشده است.</p>
            @else
                <div class="space-y-2">
                    @foreach ($topNodes as $nodeItem)
                        <div class="flex items-center justify-between gap-3 rounded-xl border border-line-border/60 bg-surface-base/70 px-3 py-2">
                            <div class="flex min-w-0 items-center gap-2.5">
                                <span class="flex size-7.5 shrink-0 items-center justify-center rounded-lg border border-line-border/80 bg-surface-card text-content-main">
                                    <x-ui.icon :name="$nodeItem['icon']" class="size-4"/>
                                </span>
                                <span dir="ltr" class="inline-block truncate text-xs font-bold text-content-main">
                                    {{ $nodeItem['name'] }}
                                </span>
                            </div>

                            <span class="shrink-0 rounded-lg bg-primary/10 px-2 py-0.5 font-mono text-[11px] font-bold text-primary">
                                {{ number_format($nodeItem['count']) }} نود
                            </span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($topCredentials !== [])
            <div class="mt-4 border-t border-line-border/60 pt-3.5">
                <p class="mb-2 text-[11px] font-bold text-content-muted">اعتبارنامه‌های پرکاربرد:</p>
                <div class="flex flex-wrap gap-1.5">
                    @foreach ($topCredentials as $cred)
                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-line-border bg-surface-base px-2.5 py-1 text-[11px] font-semibold text-content-main">
                            <x-ui.icon :name="$cred['icon']" class="size-3.5"/>
                            <span>{{ $cred['name'] }}</span>
                            <strong class="font-mono text-primary">({{ $cred['count'] }})</strong>
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="flex flex-col justify-between rounded-2xl border border-line-border bg-surface-card p-4 sm:p-5 shadow-2xs">
        <div class="space-y-3.5">
            <div class="flex items-center justify-between border-b border-line-border/60 pb-3.5">
                <div class="flex items-center gap-2">
                    <x-ui.icon name="database" class="size-4.5 text-sky-500"/>
                    <h3 class="text-xs font-extrabold text-content-main">وضعیت زیرساخت و پشتیبانی</h3>
                </div>
                <x-ui.badge variant="success" dot>آنلاین</x-ui.badge>
            </div>

            <div class="space-y-2.5 text-xs">
                <div class="flex items-center justify-between rounded-xl border border-line-border/70 bg-surface-base px-3.5 py-2.5">
                    <span class="text-content-muted">اجراهای امروز / این ماه</span>
                    <span dir="ltr" class="inline-block font-mono font-bold text-content-main">
                        {{ number_format($platform['runs_today']) }} / {{ number_format($platform['runs_month']) }}
                    </span>
                </div>

                <div class="flex items-center justify-between rounded-xl border border-line-border/70 bg-surface-base px-3.5 py-2.5">
                    <span class="text-content-muted">مجموع تخفیف‌های داده‌شده</span>
                    <span class="font-mono font-bold text-emerald-500">
                        {{ number_format($revenue['total_discount']) }} <span class="font-sans text-[11px] font-normal">تومان</span>
                    </span>
                </div>

                <a href="{{ route('app.tickets.index') }}"
                   class="flex items-center justify-between rounded-xl border border-line-border/70 bg-surface-base px-3.5 py-2.5 transition hover:border-primary/40">
                    <span class="text-content-muted">تیکت‌های منتظر پاسخ ادمین</span>
                    <span @class([
                        'rounded-lg px-2 py-0.5 font-mono text-xs font-extrabold',
                        'bg-primary/15 text-primary' => $platform['open_tickets'] > 0,
                        'bg-emerald-500/15 text-emerald-500' => $platform['open_tickets'] === 0,
                    ])>
                        {{ number_format($platform['open_tickets']) }} تیکت
                    </span>
                </a>

                <div class="flex items-center justify-between rounded-xl border border-line-border/70 bg-surface-base px-3.5 py-2.5">
                    <span class="text-content-muted">صف پردازش (در انتظار / خطا)</span>
                    <span dir="ltr" class="inline-flex items-center gap-1.5 font-mono font-bold">
                        <span class="text-sky-500">{{ number_format($platform['pending_jobs']) }}</span>
                        <span class="text-content-muted">/</span>
                        <span @class([
                            'text-primary' => $platform['failed_jobs'] > 0,
                            'text-emerald-500' => $platform['failed_jobs'] === 0,
                        ])>{{ number_format($platform['failed_jobs']) }}</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="mt-4 flex items-center gap-2 border-t border-line-border/60 pt-3.5">
            <a href="{{ route('app.discounts.index') }}"
               class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-line-border bg-surface-base py-2 text-xs font-bold text-content-main transition hover:border-primary/40 hover:text-primary">
                <x-ui.icon name="discount-2" class="size-4 text-primary"/>
                <span>کدهای تخفیف ({{ $platform['active_discounts'] }})</span>
            </a>

            @if (auth()->user()?->canAccessTerminal())
                <a href="{{ route('terminal.index') }}"
                   class="flex flex-1 items-center justify-center gap-1.5 rounded-xl border border-line-border bg-surface-base py-2 text-xs font-bold text-content-main transition hover:border-primary/40 hover:text-primary">
                    <x-ui.icon name="terminal-2" class="size-4 text-primary"/>
                    <span>ترمینال سرور</span>
                </a>
            @endif
        </div>
    </div>
</div>
