@props([
    'metrics',
])

@php
    $revenue = $metrics['revenue'];
    $users = $metrics['users'];
    $platform = $metrics['platform'];
    $jMonthLabel = $metrics['current_jalali_month_label'];
    $jYearLabel = $metrics['current_jalali_year_label'];
@endphp

<div class="space-y-3.5 sm:space-y-4">
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4 sm:gap-3.5">
        <div class="relative overflow-hidden rounded-2xl border border-emerald-500/30 bg-linear-to-b from-emerald-500/8 to-surface-card p-4 sm:p-5 shadow-2xs">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-content-muted">درآمد این ماه ({{ $jMonthLabel }})</p>
                    <div class="mt-2 flex flex-wrap items-baseline gap-1.5">
                        <span class="font-mono text-xl sm:text-2xl font-extrabold tabular-nums text-content-main">
                            {{ number_format($revenue['month']) }}
                        </span>
                        <span class="text-xs font-bold text-content-muted">تومان</span>
                    </div>
                </div>
                <div class="flex size-10 sm:size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-500 ring-1 ring-emerald-500/25">
                    <x-ui.icon name="coins" class="size-5 sm:size-5.5"/>
                </div>
            </div>

            <div class="mt-3.5 flex items-center justify-between border-t border-line-border/60 pt-3 text-[11px]">
                <span class="text-content-muted">
                    <strong class="font-mono text-content-main">{{ number_format($revenue['month_count']) }}</strong> خرید موفق در این ماه
                </span>

                @if ($revenue['month_growth'] !== null)
                    <span dir="ltr" @class([
                        'inline-flex items-center gap-0.5 rounded-md px-1.5 py-0.5 font-mono text-[10px] font-bold',
                        'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' => $revenue['month_growth'] >= 0,
                        'bg-primary/15 text-primary' => $revenue['month_growth'] < 0,
                    ])>
                        <x-ui.icon :name="$revenue['month_growth'] >= 0 ? 'trending-up' : 'trending-down'" class="size-3"/>
                        <span>{{ $revenue['month_growth'] > 0 ? '+' : '' }}{{ $revenue['month_growth'] }}%</span>
                    </span>
                @endif
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl border border-amber-500/30 bg-linear-to-b from-amber-500/8 to-surface-card p-4 sm:p-5 shadow-2xs">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-content-muted">درآمد امسال ({{ $jYearLabel }})</p>
                    <div class="mt-2 flex flex-wrap items-baseline gap-1.5">
                        <span class="font-mono text-xl sm:text-2xl font-extrabold tabular-nums text-content-main">
                            {{ number_format($revenue['year']) }}
                        </span>
                        <span class="text-xs font-bold text-content-muted">تومان</span>
                    </div>
                </div>
                <div class="flex size-10 sm:size-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-500 ring-1 ring-amber-500/25">
                    <x-ui.icon name="crown" class="size-5 sm:size-5.5"/>
                </div>
            </div>

            <div class="mt-3.5 flex items-center justify-between border-t border-line-border/60 pt-3 text-[11px]">
                <span class="text-content-muted">درآمد کل از ابتدا:</span>
                <span class="font-mono font-bold text-content-main">{{ number_format($revenue['total']) }} تومان</span>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl border border-primary/30 bg-linear-to-b from-primary/7 to-surface-card p-4 sm:p-5 shadow-2xs">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-content-muted">درآمد و فروش امروز</p>
                    <div class="mt-2 flex flex-wrap items-baseline gap-1.5">
                        <span class="font-mono text-xl sm:text-2xl font-extrabold tabular-nums text-content-main">
                            {{ number_format($revenue['today']) }}
                        </span>
                        <span class="text-xs font-bold text-content-muted">تومان</span>
                    </div>
                </div>
                <div class="flex size-10 sm:size-11 shrink-0 items-center justify-center rounded-xl bg-primary/15 text-primary ring-1 ring-primary/25">
                    <x-ui.icon name="credit-card" class="size-5 sm:size-5.5"/>
                </div>
            </div>

            <div class="mt-3.5 flex items-center justify-between border-t border-line-border/60 pt-3 text-[11px]">
                <span class="text-content-muted">
                    امروز: <strong class="font-mono text-content-main">{{ number_format($revenue['today_count']) }}</strong> تراکنش
                </span>
                <span class="text-content-muted">
                    دیروز: <strong class="font-mono text-content-main">{{ number_format($revenue['yesterday']) }}</strong> ت
                </span>
            </div>
        </div>

        <div class="relative overflow-hidden rounded-2xl border border-sky-500/30 bg-linear-to-b from-sky-500/8 to-surface-card p-4 sm:p-5 shadow-2xs">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-content-muted">کل کاربران ثبت‌نامی</p>
                    <div class="mt-2 flex flex-wrap items-baseline gap-2">
                        <span class="font-mono text-xl sm:text-2xl font-extrabold tabular-nums text-content-main">
                            {{ number_format($users['total']) }}
                        </span>
                        <span class="rounded-lg bg-sky-500/15 px-2 py-0.5 text-[11px] font-bold text-sky-500">
                            +{{ number_format($users['today']) }} امروز
                        </span>
                    </div>
                </div>
                <div class="flex size-10 sm:size-11 shrink-0 items-center justify-center rounded-xl bg-sky-500/15 text-sky-500 ring-1 ring-sky-500/25">
                    <x-ui.icon name="users" class="size-5 sm:size-5.5"/>
                </div>
            </div>

            <div class="mt-3.5 flex items-center justify-between border-t border-line-border/60 pt-3 text-[11px]">
                <span class="text-content-muted">
                    این ماه: <strong class="font-mono text-content-main">{{ number_format($users['month']) }}</strong> کاربر جدید
                </span>

                @if ($users['month_growth'] !== null)
                    <span dir="ltr" @class([
                        'inline-flex items-center gap-0.5 rounded-md px-1.5 py-0.5 font-mono text-[10px] font-bold',
                        'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400' => $users['month_growth'] >= 0,
                        'bg-primary/15 text-primary' => $users['month_growth'] < 0,
                    ])>
                        <x-ui.icon :name="$users['month_growth'] >= 0 ? 'trending-up' : 'trending-down'" class="size-3"/>
                        <span>{{ $users['month_growth'] > 0 ? '+' : '' }}{{ $users['month_growth'] }}%</span>
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 lg:grid-cols-4 sm:gap-3.5">
        <div class="flex items-center gap-3 rounded-2xl border border-line-border bg-surface-card p-3.5 sm:p-4 shadow-2xs">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-500">
                <x-ui.icon name="user-plus" class="size-5"/>
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-xs font-medium text-content-muted">اشتراک‌های فعال فعلی</p>
                <div class="mt-1.5 flex items-baseline justify-between gap-2">
                    <div class="flex items-baseline gap-1">
                        <span class="font-mono text-base font-extrabold tabular-nums text-content-main">
                            {{ number_format($users['active_subscriptions']) }}
                        </span>
                        <span class="text-[10px] text-content-muted">اشتراک</span>
                    </div>
                    <span class="shrink-0 rounded-md bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-bold text-emerald-500">
                        تبدیل: %{{ $users['conversion_rate'] }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 rounded-2xl border border-line-border bg-surface-card p-3.5 sm:p-4 shadow-2xs">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500">
                <x-ui.icon name="receipt" class="size-5"/>
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-xs font-medium text-content-muted">میانگین مبلغ هر خرید</p>
                <div class="mt-1.5 flex items-baseline justify-between gap-2">
                    <div class="flex items-baseline gap-1">
                        <span class="font-mono text-base font-extrabold tabular-nums text-content-main">
                            {{ number_format($revenue['aov']) }}
                        </span>
                        <span class="text-[10px] text-content-muted">تومان</span>
                    </div>
                    <span class="shrink-0 text-[10px] text-content-muted">سفارش موفق</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 rounded-2xl border border-line-border bg-surface-card p-3.5 sm:p-4 shadow-2xs">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <x-ui.icon name="hierarchy" class="size-5"/>
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-xs font-medium text-content-muted">ورکفلوهای سایت</p>
                <div class="mt-1.5 flex items-baseline justify-between gap-2">
                    <div dir="ltr" class="font-mono text-base font-extrabold tabular-nums">
                        <span class="text-emerald-500">{{ number_format($platform['active_workflows']) }}</span>
                        <span class="text-content-muted">/</span>
                        <span class="text-content-main">{{ number_format($platform['total_workflows']) }}</span>
                    </div>
                    <span class="shrink-0 text-[10px] text-content-muted">فعال / کل</span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3 rounded-2xl border border-line-border bg-surface-card p-3.5 sm:p-4 shadow-2xs">
            <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500">
                <x-ui.icon name="bolt" class="size-5"/>
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-xs font-medium text-content-muted">کل اجراهای ورکفلو</p>
                <div class="mt-1.5 flex items-baseline justify-between gap-2">
                    <div class="flex items-baseline gap-1">
                        <span class="font-mono text-base font-extrabold tabular-nums text-content-main">
                            {{ number_format($platform['total_runs']) }}
                        </span>
                        <span class="text-[10px] text-content-muted">اجرا</span>
                    </div>
                    <span class="shrink-0 rounded-md bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-bold text-emerald-500">
                        %{{ $platform['run_success_rate'] }} موفق
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
