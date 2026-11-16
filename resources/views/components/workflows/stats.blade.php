@props([
    'total' => 0,
    'max' => null,
    'active' => 0,
    'maxActive' => null,
    'runs' => 0,
    'successRate' => null,
])

<div class="grid grid-cols-2 gap-2.5 sm:gap-4 lg:grid-cols-4">
    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary sm:size-11">
            <x-ui.icon name="hierarchy" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">کل ورکفلوها</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg">{{ $total }}</span>
                @if ($max !== null)
                    <span class="text-[10px] text-content-muted sm:text-[11px]">از {{ $max }}</span>
                @else
                    <span class="text-[10px] text-content-muted sm:text-[11px]">ورکفلو</span>
                @endif
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500 sm:size-11">
            <x-ui.icon name="bolt" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">منتشرشده و فعال</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg">{{ $active }}</span>
                @if ($maxActive !== null)
                    <span class="text-[10px] text-content-muted sm:text-[11px]">از {{ $maxActive }}</span>
                @else
                    <span class="text-[10px] text-content-muted sm:text-[11px]">ورکفلو</span>
                @endif
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-500 sm:size-11">
            <x-ui.icon name="player-play" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">کل اجراها</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg">{{ number_format($runs) }}</span>
                <span class="text-[10px] text-content-muted sm:text-[11px]">بار</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-500 sm:size-11">
            <x-ui.icon name="sparkles" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">نرخ موفقیت اجرا</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                @if ($successRate !== null)
                    <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg">%{{ $successRate }}</span>
                @else
                    <span class="text-xs font-semibold text-content-muted">بدون اجرا</span>
                @endif
            </div>
        </div>
    </div>
</div>
