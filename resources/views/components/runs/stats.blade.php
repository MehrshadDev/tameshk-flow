@props([
    'total' => 0,
    'succeeded' => 0,
    'failed' => 0,
    'avgDurationSeconds' => null,
])

<div class="grid grid-cols-2 gap-2.5 sm:gap-4 lg:grid-cols-4">
    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary sm:size-11">
            <x-ui.icon name="history" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">کل اجراها</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg">{{ number_format($total) }}</span>
                <span class="text-[10px] text-content-muted sm:text-[11px]">بار</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500 sm:size-11">
            <x-ui.icon name="check" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">اجراهای موفق</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg">{{ number_format($succeeded) }}</span>
                <span class="text-[10px] text-content-muted sm:text-[11px]">مورد</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-rose-500/10 text-rose-500 sm:size-11">
            <x-ui.icon name="alert-triangle" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">اجراهای ناموفق</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg">{{ number_format($failed) }}</span>
                <span class="text-[10px] text-content-muted sm:text-[11px]">خطا</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-500 sm:size-11">
            <x-ui.icon name="clock" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">میانگین زمان اجرا</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                @if ($avgDurationSeconds !== null)
                    <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg" dir="ltr">{{ $avgDurationSeconds }}s</span>
                @else
                    <span class="text-xs font-semibold text-content-muted">بدون داده</span>
                @endif
            </div>
        </div>
    </div>
</div>
