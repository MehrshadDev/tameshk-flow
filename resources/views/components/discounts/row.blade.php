@props([
    'discount',
])

@php
    use App\Workflow\Support\DateTimeHelper;

    $isUsable = $discount->isCurrentlyUsable();
    $isExpired = $discount->isExpired();
    $isExhausted = $discount->isExhausted();

    $expiresPersian = $discount->expires_at
        ? DateTimeHelper::toPersianDate($discount->expires_at->copy()->setTimezone('Asia/Tehran'), 'yyyy/MM/dd HH:mm')
        : null;

    $planSlug = $discount->applicable_plan_slugs[0] ?? null;
    $planLabel = match ($planSlug) {
        'pro' => 'فقط پلن پرو',
        'business' => 'فقط پلن بیزینس',
        default => 'همه پلن‌ها',
    };

    $cycleSlug = $discount->applicable_cycles[0] ?? null;
    $cycleLabel = match ($cycleSlug) {
        'monthly' => 'فقط ماهانه',
        'yearly' => 'فقط سالانه',
        default => 'ماهانه و سالانه',
    };
@endphp

<div wire:click="openEdit({{ $discount->id }})"
     class="group relative has-aria-expanded:z-30 flex flex-col gap-3 rounded-2xl border border-line-border bg-surface-card p-3.5 shadow-2xs transition-all duration-150 hover:border-primary/40 hover:shadow-md select-none sm:p-4 md:flex-row md:items-center md:gap-4">

    <div class="flex items-start justify-between gap-2.5 md:min-w-0 md:flex-1 md:items-center md:gap-4">
        <div class="flex min-w-0 flex-1 items-start gap-2.5 sm:gap-3.5 md:items-center">
            <div @class([
                'flex size-10 shrink-0 items-center justify-center rounded-xl transition-transform group-hover:scale-105 sm:size-11',
                'bg-emerald-500/10 text-emerald-500 ring-1 ring-emerald-500/20' => $isUsable,
                'bg-amber-500/10 text-amber-500 ring-1 ring-amber-500/20' => ! $isUsable && ($isExpired || $isExhausted),
                'bg-line-border/50 text-content-muted' => ! $discount->is_active,
            ])>
                <x-ui.icon :name="$discount->isPercent() ? 'percentage' : 'discount-2'" class="size-5 sm:size-5.5"/>
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-1.5 sm:gap-2">
                    <button type="button"
                            dir="ltr"
                            data-code="{{ $discount->code }}"
                            title="کلیک برای کپی کد"
                            @click.stop="navigator.clipboard.writeText($el.dataset.code); $dispatch('notify', { message: 'کد تخفیف ' + $el.dataset.code + ' کپی شد.', type: 'success' })"
                            class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-primary/30 bg-primary/10 px-2 py-0.5 font-mono text-[11px] font-extrabold tracking-wider text-primary transition hover:bg-primary hover:text-white cursor-pointer sm:px-2.5 sm:text-xs">
                        <span>{{ $discount->code }}</span>
                        <x-ui.icon name="copy" class="size-3"/>
                    </button>

                    <span class="shrink-0 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-extrabold text-emerald-600 dark:text-emerald-400 sm:text-[11px]">
                        @if ($discount->isPercent())
                            {{ $discount->value }}٪ تخفیف
                        @else
                            {{ number_format($discount->value) }} تومان
                        @endif
                    </span>

                    <h3 dir="auto" class="w-full truncate text-start text-xs font-bold text-content-main transition-colors group-hover:text-primary sm:w-auto sm:text-sm">
                        {{ $discount->title }}
                    </h3>
                </div>

                <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-content-muted">
                    <span>{{ $planLabel }}</span>
                    <span>•</span>
                    <span>{{ $cycleLabel }}</span>

                    @if ($discount->isPercent() && $discount->max_discount_amount)
                        <span>•</span>
                        <span>سقف: {{ number_format($discount->max_discount_amount) }} ت</span>
                    @endif

                    @if ($discount->min_order_amount)
                        <span>•</span>
                        <span>حداقل خرید: {{ number_format($discount->min_order_amount) }} ت</span>
                    @endif

                    @if ($discount->first_purchase_only)
                        <span class="rounded-md border border-amber-500/30 bg-amber-500/10 px-1.5 py-0.5 text-[10px] font-bold text-amber-500">
                            فقط خرید اول
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-1.5 md:hidden" @click.stop>
            <x-ui.switch :checked="$discount->is_active"
                         :label="$discount->is_active ? 'غیرفعال کردن' : 'فعال کردن'"
                         wire:click="toggleActive({{ $discount->id }})"
                         wire:loading.attr="disabled"
                         wire:target="toggleActive({{ $discount->id }})"/>

            <x-ui.dropdown align="left" width="w-48">
                <x-slot:trigger>
                    <button type="button"
                            aria-label="گزینه‌ها"
                            class="flex size-8 items-center justify-center rounded-xl text-content-muted transition hover:bg-line-border/60 hover:text-content-main in-[[aria-expanded=true]]:bg-line-border/60 in-[[aria-expanded=true]]:text-content-main focus:outline-none cursor-pointer">
                        <x-ui.icon name="dots-vertical" class="size-4.5"/>
                    </button>
                </x-slot:trigger>

                <x-ui.dropdown-item icon="pencil"
                                    wire:click="openEdit({{ $discount->id }})">
                    ویرایش کد تخفیف
                </x-ui.dropdown-item>

                <x-ui.dropdown-item icon="copy"
                                    data-code="{{ $discount->code }}"
                                    @click="navigator.clipboard.writeText($el.dataset.code); $dispatch('notify', { message: 'کد تخفیف کپی شد.', type: 'success' })">
                    کپی کد تخفیف
                </x-ui.dropdown-item>

                <div class="my-1 border-t border-line-border/60"></div>

                <x-ui.dropdown-item icon="trash" danger
                                    wire:click="confirmDelete({{ $discount->id }})">
                    حذف کد تخفیف
                </x-ui.dropdown-item>
            </x-ui.dropdown>
        </div>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-line-border/60 bg-surface-base/60 px-3 py-2 text-[11px] md:hidden">
        <div class="flex items-center gap-1.5">
            <span class="text-content-muted">استفاده:</span>
            <span dir="ltr" class="font-mono font-bold tabular-nums text-content-main">
                {{ number_format($discount->used_count) }} / {{ $discount->max_uses !== null ? number_format($discount->max_uses) : '∞' }}
            </span>
        </div>

        <div class="flex items-center gap-1.5">
            @if ($isExpired)
                <x-ui.badge variant="danger" dot>منقضی</x-ui.badge>
            @elseif ($isExhausted)
                <x-ui.badge variant="warning" dot>تکمیل ظرفیت</x-ui.badge>
            @elseif ($expiresPersian)
                <span class="text-content-muted">انقضا:</span>
                <span dir="ltr" class="font-mono text-content-main">{{ $expiresPersian }}</span>
            @else
                <span class="text-content-muted">بدون انقضا</span>
            @endif
        </div>
    </div>

    <div class="hidden w-36 shrink-0 md:block">
        <div class="flex items-center gap-1.5 text-xs font-bold text-content-main">
            <span dir="ltr" class="font-mono tabular-nums">
                {{ number_format($discount->used_count) }} / {{ $discount->max_uses !== null ? number_format($discount->max_uses) : '∞' }}
            </span>
            <span class="text-[11px] font-normal text-content-muted">استفاده</span>
        </div>
        <p class="mt-0.5 text-[11px] text-content-muted">
            {{ $discount->max_uses_per_user !== null ? "سقف هر کاربر: {$discount->max_uses_per_user} بار" : 'هر کاربر: نامحدود' }}
        </p>
    </div>

    <div class="hidden w-40 shrink-0 lg:block">
        @if ($expiresPersian)
            <div class="flex items-center gap-1.5 text-xs font-medium text-content-main">
                <x-ui.icon name="calendar" @class(['size-3.5', 'text-primary' => $isExpired, 'text-sky-500' => ! $isExpired])/>
                <span dir="ltr" class="font-mono text-[11px]">{{ $expiresPersian }}</span>
            </div>
            <p class="mt-0.5 text-[11px] text-content-muted">
                {{ $isExpired ? 'منقضی شده' : $discount->expires_at->diffForHumans() }}
            </p>
        @else
            <span class="text-xs font-medium text-content-muted">بدون تاریخ انقضا</span>
        @endif
    </div>

    <div class="hidden shrink-0 items-center gap-2.5 md:flex" @click.stop>
        @if ($isExpired)
            <x-ui.badge variant="danger" dot>منقضی</x-ui.badge>
        @elseif ($isExhausted)
            <x-ui.badge variant="warning" dot>تکمیل ظرفیت</x-ui.badge>
        @endif

        <x-ui.switch :checked="$discount->is_active"
                     :label="$discount->is_active ? 'غیرفعال کردن' : 'فعال کردن'"
                     wire:click="toggleActive({{ $discount->id }})"
                     wire:loading.attr="disabled"
                     wire:target="toggleActive({{ $discount->id }})"/>

        <x-ui.dropdown align="left" width="w-48">
            <x-slot:trigger>
                <button type="button"
                        aria-label="گزینه‌ها"
                        class="flex size-8.5 items-center justify-center rounded-xl text-content-muted transition hover:bg-line-border/60 hover:text-content-main in-[[aria-expanded=true]]:bg-line-border/60 in-[[aria-expanded=true]]:text-content-main focus:outline-none cursor-pointer">
                    <x-ui.icon name="dots-vertical" class="size-4.5"/>
                </button>
            </x-slot:trigger>

            <x-ui.dropdown-item icon="pencil"
                                wire:click="openEdit({{ $discount->id }})">
                ویرایش کد تخفیف
            </x-ui.dropdown-item>

            <x-ui.dropdown-item icon="copy"
                                data-code="{{ $discount->code }}"
                                @click="navigator.clipboard.writeText($el.dataset.code); $dispatch('notify', { message: 'کد تخفیف کپی شد.', type: 'success' })">
                کپی کد تخفیف
            </x-ui.dropdown-item>

            <div class="my-1 border-t border-line-border/60"></div>

            <x-ui.dropdown-item icon="trash" danger
                                wire:click="confirmDelete({{ $discount->id }})">
                حذف کد تخفیف
            </x-ui.dropdown-item>
        </x-ui.dropdown>
    </div>
</div>
