@props([
    'editingId' => null,
    'type' => 'percent',
    'planRestriction' => 'all',
    'cycleRestriction' => 'all',
    'expiryMode' => 'unlimited',
    'firstPurchaseOnly' => false,
    'isActive' => true,
    'currentExpiresPersian' => null,
    'deletingCode' => null,
])

@php
    $typeOptions = [
        ['value' => 'percent', 'label' => 'درصدی (Percentage)', 'icon' => 'percentage'],
        ['value' => 'fixed', 'label' => 'مبلغ ثابت (تومان)', 'icon' => 'discount-2'],
    ];

    $planOptions = [
        ['value' => 'all', 'label' => 'همه پلن‌ها (پرو و بیزینس)'],
        ['value' => 'pro', 'label' => 'فقط پلن پرو'],
        ['value' => 'business', 'label' => 'فقط پلن بیزینس'],
    ];

    $cycleOptions = [
        ['value' => 'all', 'label' => 'همه دوره‌ها (ماهانه و سالانه)'],
        ['value' => 'monthly', 'label' => 'فقط دوره ماهانه'],
        ['value' => 'yearly', 'label' => 'فقط دوره سالانه'],
    ];

    $expiryOptions = array_values(array_filter([
        ['value' => 'unlimited', 'label' => 'بدون محدودیت زمانی (همیشگی)'],
        $editingId && $currentExpiresPersian
            ? ['value' => 'keep', 'label' => "حفظ تاریخ انقضای فعلی ($currentExpiresPersian)"]
            : null,
        ['value' => 'days', 'label' => 'تعیین مدت اعتبار به روز (از الان)'],
    ]));

    $inputClass = 'h-9.5 w-full rounded-xl border border-line-border bg-surface-base px-3.5 text-xs font-medium text-content-main transition placeholder:text-content-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30';
    $labelClass = 'mb-1.5 flex h-5 items-center justify-between text-xs font-bold text-content-main';
@endphp

<x-ui.modal name="discount-form-modal"
            :title="$editingId ? 'ویرایش کد تخفیف' : 'ایجاد کد تخفیف جدید'"
            icon="discount-2"
            maxWidth="2xl">
    <form id="discount- code-form" wire:submit="saveDiscount" class="space-y-4">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <div class="{{ $labelClass }}">
                    <label for="discount_code_field">کد تخفیف (انگلیسی)</label>
                    <button type="button"
                            wire:click="generateRandomCode"
                            class="inline-flex items-center gap-1 text-[11px] font-bold text-primary transition hover:underline cursor-pointer">
                        <x-ui.icon name="sparkles" class="size-3.5"/>
                        <span>تولید کد تصادفی</span>
                    </button>
                </div>

                <input id="discount_code_field"
                       type="text"
                       dir="ltr"
                       maxlength="40"
                       wire:model="code"
                       placeholder="NOROUZ1405"
                       class="{{ $inputClass }} text-left font-mono font-bold uppercase tracking-wider placeholder:font-mono placeholder:font-normal"/>
                @error('code')
                <p class="mt-1.5 text-xs text-primary">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="discount_title_field" class="{{ $labelClass }}">
                    <span>عنوان یا مناسبت کد تخفیف</span>
                </label>
                <input id="discount_title_field"
                       type="text"
                       dir="auto"
                       maxlength="150"
                       wire:model="title"
                       placeholder="مثلاً: تخفیف ویژه جشنواره بهاره"
                       class="{{ $inputClass }} text-start"/>
                @error('title')
                <p class="mt-1.5 text-xs text-primary">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="{{ $labelClass }}">
                    <span>نوع محاسبه تخفیف</span>
                </label>
                <x-ui.select
                    :options="$typeOptions"
                    :selected="$type"
                    wire-model="type"
                    :searchable="false"
                    :full-width="true"
                />
            </div>

            <div>
                <label for="discount_value_field" class="{{ $labelClass }}">
                    <span>{{ $type === 'percent' ? 'درصد تخفیف (۱ تا ۱۰۰)' : 'مبلغ تخفیف (تومان)' }}</span>
                </label>
                <input id="discount_value_field"
                       type="text"
                       inputmode="numeric"
                       dir="ltr"
                       wire:model="value"
                       placeholder="{{ $type === 'percent' ? '30' : '150000' }}"
                       class="{{ $inputClass }} text-left font-mono font-bold"/>
                @error('value')
                <p class="mt-1.5 text-xs text-primary">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            @if ($type === 'percent')
                <div>
                    <label for="discount_max_amount_field" class="{{ $labelClass }}">
                        <span>سقف مبلغ تخفیف به تومان (اختیاری)</span>
                    </label>
                    <input id="discount_max_amount_field"
                           type="text"
                           inputmode="numeric"
                           dir="ltr"
                           wire:model="maxDiscountAmount"
                           placeholder="خالی = بدون سقف"
                           class="{{ $inputClass }} text-left font-mono placeholder:text-right placeholder:font-sans"/>
                    @error('maxDiscountAmount')
                    <p class="mt-1.5 text-xs text-primary">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            <div @class(['sm:col-span-2' => $type !== 'percent'])>
                <label for="discount_min_order_field" class="{{ $labelClass }}">
                    <span>حداقل مبلغ سفارش به تومان (اختیاری)</span>
                </label>
                <input id="discount_min_order_field"
                       type="text"
                       inputmode="numeric"
                       dir="ltr"
                       wire:model="minOrderAmount"
                       placeholder="خالی = بدون حداقل خرید"
                       class="{{ $inputClass }} text-left font-mono placeholder:text-right placeholder:font-sans"/>
                @error('minOrderAmount')
                <p class="mt-1.5 text-xs text-primary">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="{{ $labelClass }}">
                    <span>پلن‌های مجاز</span>
                </label>
                <x-ui.select
                    :options="$planOptions"
                    :selected="$planRestriction"
                    wire-model="planRestriction"
                    :searchable="false"
                    :full-width="true"
                />
            </div>

            <div>
                <label class="{{ $labelClass }}">
                    <span>دوره‌های پرداخت مجاز</span>
                </label>
                <x-ui.select
                    :options="$cycleOptions"
                    :selected="$cycleRestriction"
                    wire-model="cycleRestriction"
                    :searchable="false"
                    :full-width="true"
                />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="discount_max_uses_field" class="{{ $labelClass }}">
                    <span>سقف کل دفعات مجاز استفاده (اختیاری)</span>
                </label>
                <input id="discount_max_uses_field"
                       type="text"
                       inputmode="numeric"
                       dir="ltr"
                       wire:model="maxUses"
                       placeholder="خالی = نامحدود"
                       class="{{ $inputClass }} text-left font-mono placeholder:text-right placeholder:font-sans"/>
                @error('maxUses')
                <p class="mt-1.5 text-xs text-primary">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="discount_max_per_user_field" class="{{ $labelClass }}">
                    <span>سقف استفاده برای هر کاربر (اختیاری)</span>
                </label>
                <input id="discount_max_per_user_field"
                       type="text"
                       inputmode="numeric"
                       dir="ltr"
                       wire:model="maxUsesPerUser"
                       placeholder="خالی = نامحدود"
                       class="{{ $inputClass }} text-left font-mono placeholder:text-right placeholder:font-sans"/>
                @error('maxUsesPerUser')
                <p class="mt-1.5 text-xs text-primary">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div @class(['sm:col-span-2' => $expiryMode !== 'days'])>
                <label class="{{ $labelClass }}">
                    <span>مدت اعتبار کد تخفیف</span>
                </label>
                <x-ui.select
                    :options="$expiryOptions"
                    :selected="$expiryMode"
                    wire-model="expiryMode"
                    :searchable="false"
                    :full-width="true"
                />
            </div>

            @if ($expiryMode === 'days')
                <div>
                    <label for="discount_valid_days_field" class="{{ $labelClass }}">
                        <span>تعداد روز اعتبار (از الان)</span>
                    </label>
                    <input id="discount_valid_days_field"
                           type="text"
                           inputmode="numeric"
                           dir="ltr"
                           wire:model="validDays"
                           placeholder="30"
                           class="{{ $inputClass }} text-left font-mono font-bold"/>
                    @error('validDays')
                    <p class="mt-1.5 text-xs text-primary">{{ $message }}</p>
                    @enderror
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 gap-3 pt-1 sm:grid-cols-2">
            <div wire:click="$toggle('firstPurchaseOnly')"
                 class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-line-border bg-surface-base p-3.5 transition hover:border-primary/40 select-none">
                <div>
                    <p class="text-xs font-bold text-content-main">فقط مخصوص اولین خرید</p>
                    <p class="mt-0.5 text-[11px] text-content-muted">کاربرانی که قبلاً خرید داشته‌اند نتوانند استفاده کنند</p>
                </div>
                <x-ui.switch :checked="$firstPurchaseOnly" label="فقط اولین خرید"/>
            </div>

            <div wire:click="$toggle('isActive')"
                 class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-line-border bg-surface-base p-3.5 transition hover:border-primary/40 select-none">
                <div>
                    <p class="text-xs font-bold text-content-main">وضعیت فعال بودن کد</p>
                    <p class="mt-0.5 text-[11px] text-content-muted">امکان استفاده از این کد در بخش پرداخت</p>
                </div>
                <x-ui.switch :checked="$isActive" label="وضعیت فعال بودن"/>
            </div>
        </div>
    </form>

    <x-slot:footer>
        <x-ui.button type="button" variant="ghost" @click="$dispatch('close-modal', 'discount-form-modal')" class="cursor-pointer">
            انصراف
        </x-ui.button>
        <x-ui.button type="button" wire:click="saveDiscount" wire:loading.attr="disabled" wire:target="saveDiscount" class="cursor-pointer">
            <span wire:loading.remove wire:target="saveDiscount">
                {{ $editingId ? 'ذخیره تغییرات' : 'ایجاد کد تخفیف' }}
            </span>
            <span wire:loading wire:target="saveDiscount">در حال ذخیره...</span>
        </x-ui.button>
    </x-slot:footer>
</x-ui.modal>

<x-ui.modal name="delete-discount-modal" title="حذف کد تخفیف" maxWidth="sm">
    <div class="flex items-start gap-3.5">
        <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
            <x-ui.icon name="trash" class="size-5"/>
        </div>
        <p class="text-xs leading-6 text-content-muted">
            کد تخفیف
            <span dir="ltr" class="font-mono font-bold text-content-main">{{ $deletingCode }}</span>
            برای همیشه حذف خواهد شد (سوابق پرداخت‌های قبلی حفظ می‌شوند). آیا مطمئن هستید؟
        </p>
    </div>

    <x-slot:footer>
        <x-ui.button type="button" variant="ghost" @click="$dispatch('close-modal', 'delete-discount-modal')" class="cursor-pointer">
            انصراف
        </x-ui.button>
        <x-ui.button type="button" wire:click="deleteDiscount" wire:loading.attr="disabled" wire:target="deleteDiscount" class="cursor-pointer">
            بله، حذف کن
        </x-ui.button>
    </x-slot:footer>
</x-ui.modal>
