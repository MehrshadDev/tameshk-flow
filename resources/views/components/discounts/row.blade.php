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
     class="group relative has-aria-expanded:z-30 flex cursor-pointer items-center gap-3 rounded-2xl border border-line-border bg-surface-card p-3.5 shadow-2xs transition-all duration-150 hover:border-primary/40 hover:shadow-md select-none sm:gap-4 sm:p-4">

    <div @class([
        'flex size-11 shrink-0 items-center justify-center rounded-xl transition-transform group-hover:scale-105',
        'bg-emerald-500/10 text-emerald-500 ring-1 ring-emerald-500/20' => $isUsable,
        'bg-amber-500/10 text-amber-500 ring-1 ring-amber-500/20' => ! $isUsable && ($isExpired || $isExhausted),
        'bg-line-border/50 text-content-muted' => ! $discount->is_active,
    ])>
        <x-ui.icon :name="$discount->isPercent() ? 'percentage' : 'discount-2'" class="size-5.5"/>
    </div>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <button type="button"
                    dir="ltr"
                    data-code="{{ $discount->code }}"
                    title="کلیک برای کپی کد"
                    @click.stop="navigator.clipboard.writeText($el.dataset.code); $dispatch('notify', { message: 'کد تخفیف ' + $el.dataset.code + ' کپی شد.', type: 'success' })"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-primary/30 bg-primary/10 px-2.5 py-0.5 font-mono text-xs font-extrabold tracking-wider text-primary transition hover:bg-primary hover:text-white cursor-pointer">
                <span>{{ $discount->code }}</span>
                <x-ui.icon name="copy" class="size-3"/>
            </button>

            <h3 dir="auto" class="truncate text-start text-sm font-bold text-content-main transition-colors group-hover:text-primary">
                {{ $discount->title }}
            </h3>

            <span class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[11px] font-extrabold text-emerald-600 dark:text-emerald-400">
                @if ($discount->isPercent())
                    {{ $discount->value }}٪ تخفیف
                @else
                    {{ number_format($discount->value) }} تومان
                @endif
            </span>
        </div>

        <div class="mt-1.5 flex flex-wrap items-center gap-2 text-[11px] text-content-muted">
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

    <div class="flex shrink-0 items-center gap-2 sm:gap-3" @click.stop>
        @if ($isExpired)
            <x-ui.badge variant="danger" dot>منقضی</x-ui.badge>
        @elseif ($isExhausted)
            <x-ui.badge variant="warning" dot>تکمیل ظرفیت</x-ui.badge>
        @endif

        <div class="flex items-center gap-2">
            <x-ui.switch :checked="$discount->is_active"
                         :label="$discount->is_active ? 'غیرفعال کردن' : 'فعال کردن'"
                         wire:click="toggleActive({{ $discount->id }})"
                         wire:loading.attr="disabled"
                         wire:target="toggleActive({{ $discount->id }})"/>
        </div>

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
