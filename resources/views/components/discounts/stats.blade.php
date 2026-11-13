@props([
    'total' => 0,
    'active' => 0,
    'totalUses' => 0,
    'totalDiscountedAmount' => 0,
])

<div class="grid grid-cols-2 gap-3 md:grid-cols-4 sm:gap-4">
    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <x-ui.icon name="discount-2" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">کل کدهای تخفیف</p>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">{{ number_format($total) }}</span>
                <span class="text-[11px] text-content-muted">کد</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500">
            <x-ui.icon name="check" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">کدهای فعال و معتبر</p>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">{{ number_format($active) }}</span>
                <span class="text-[11px] text-content-muted">فعال</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-500">
            <x-ui.icon name="receipt" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">کل دفعات استفاده</p>
            <div class="mt-1 flex items-baseline gap-1.5">
                <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">{{ number_format($totalUses) }}</span>
                <span class="text-[11px] text-content-muted">خرید موفق</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3.5 rounded-2xl border border-line-border bg-surface-card p-4 shadow-2xs">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500">
            <x-ui.icon name="percentage" class="size-5.5"/>
        </div>
        <div class="min-w-0">
            <p class="truncate text-xs font-medium text-content-muted">مجموع تخفیف داده‌شده</p>
            <div class="mt-1 flex items-baseline gap-1">
                <span class="font-mono text-lg font-extrabold tabular-nums text-content-main">{{ number_format($totalDiscountedAmount) }}</span>
                <span class="text-[11px] text-content-muted">تومان</span>
            </div>
        </div>
    </div>
</div>
