@props([
    'total' => 0,
    'pending' => 0,
    'answered' => 0,
    'closed' => 0,
    'isAdmin' => false,
])

<div class="grid grid-cols-2 gap-2.5 sm:gap-4 lg:grid-cols-4">
    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary sm:size-11">
            <x-ui.icon name="ticket" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">
                {{ $isAdmin ? 'کل تیکت‌ها' : 'تیکت‌های من' }}
            </p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg">{{ number_format($total) }}</span>
                <span class="text-[10px] text-content-muted sm:text-[11px]">تیکت</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 sm:size-11">
            <x-ui.icon name="clock" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">در انتظار بررسی</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg">{{ number_format($pending) }}</span>
                <span class="text-[10px] text-content-muted sm:text-[11px]">مورد</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500 sm:size-11">
            <x-ui.icon name="check" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">پاسخ داده شده</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg">{{ number_format($answered) }}</span>
                <span class="text-[10px] text-content-muted sm:text-[11px]">مورد</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-500 sm:size-11">
            <x-ui.icon name="lock" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">بسته شده</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg">{{ number_format($closed) }}</span>
                <span class="text-[10px] text-content-muted sm:text-[11px]">تیکت</span>
            </div>
        </div>
    </div>
</div>
