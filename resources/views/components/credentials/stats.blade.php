@props([
    'total' => 0,
    'aiCount' => 0,
    'oauthCount' => 0,
    'inUseCount' => 0,
])

<div class="grid grid-cols-2 gap-2.5 sm:gap-4 lg:grid-cols-4">
    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary sm:size-11">
            <x-ui.icon name="key" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">کل اعتبارنامه‌ها</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg">{{ $total }}</span>
                <span class="text-[10px] text-content-muted sm:text-[11px]">حساب</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-purple-500/10 text-purple-500 sm:size-11">
            <x-ui.icon name="sparkles" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">مدل‌های هوش مصنوعی</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg">{{ $aiCount }}</span>
                <span class="text-[10px] text-content-muted sm:text-[11px]">کلید API</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500 sm:size-11">
            <x-ui.icon name="link" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">حساب‌های OAuth</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg">{{ $oauthCount }}</span>
                <span class="text-[10px] text-content-muted sm:text-[11px]">متصل</span>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:gap-3.5 sm:p-4">
        <div class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-500 sm:size-11">
            <x-ui.icon name="hierarchy" class="size-4.5 sm:size-5.5"/>
        </div>
        <div class="min-w-0 flex-1">
            <p class="truncate text-[11px] font-medium text-content-muted sm:text-xs">در حال استفاده</p>
            <div class="mt-1 flex flex-wrap items-baseline gap-1">
                <span class="font-mono text-base font-extrabold tabular-nums text-content-main sm:text-lg">{{ $inUseCount }}</span>
                <span class="text-[10px] text-content-muted sm:text-[11px]">اعتبارنامه</span>
            </div>
        </div>
    </div>
</div>
