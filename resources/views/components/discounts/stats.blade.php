@props([
    'total' => 0,
    'active' => 0,
    'totalUses' => 0,
    'totalDiscountedAmount' => 0,
])

<div class="grid grid-cols-2 gap-2.5 sm:gap-4 lg:grid-cols-4">
    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary sm:size-11">
            <x-ui.icon name="discount-2" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">کل کدهای تخفیف</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg">{{ number_format($total) }}</span>
                <span class="text-[10px] text-content-muted sm:text-[11px]">کد</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500 sm:size-11">
            <x-ui.icon name="check" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">کدهای فعال</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg">{{ number_format($active) }}</span>
                <span class="text-[10px] text-content-muted sm:text-[11px]">معتبر</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-500 sm:size-11">
            <x-ui.icon name="receipt" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">دفعات استفاده</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg">{{ number_format($totalUses) }}</span>
                <span class="text-[10px] text-content-muted sm:text-[11px]">خرید</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 sm:size-11">
            <x-ui.icon name="percentage" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">مجموع تخفیف‌ها</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-x-1 gap-y-0">
                <span class="font-mono text-sm font-extrabold tabular-nums text-content-main sm:text-lg">{{ number_format($totalDiscountedAmount) }}</span>
                <span class="text-[10px] text-content-muted sm:text-[11px]">تومان</span>
            </div>
        </div>
    </div>
</div>
