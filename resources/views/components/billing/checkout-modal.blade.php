@props([
    'plan' => null,
    'cycle' => 'monthly',
    'quote' => null,
    'canSwitchCycle' => false,
    'appliedCode' => null,
    'discountMessage' => null,
    'discountSuccess' => false,
])

<x-ui.modal name="billing-checkout-modal" title="بررسی صورتحساب و تایید خرید" icon="receipt" maxWidth="md">
    @if ($plan && is_array($quote))
        @php
            $isBusiness = $plan->slug === 'business';
            $isFreeCheckout = ((int) $quote['payable_amount']) === 0;
        @endphp

        <div class="space-y-4">
            <div @class([
                'flex items-center justify-between gap-3 rounded-2xl border p-4',
                'border-amber-500/35 bg-linear-to-l from-amber-500/10 to-surface-card' => $isBusiness,
                'border-primary/30 bg-linear-to-l from-primary/8 to-surface-card' => ! $isBusiness,
            ])>
                <div class="flex min-w-0 items-center gap-3">
                    <div @class([
                        'flex size-11 shrink-0 items-center justify-center rounded-xl ring-1',
                        'bg-amber-500/15 text-amber-500 ring-amber-500/30' => $isBusiness,
                        'bg-primary/10 text-primary ring-primary/20' => ! $isBusiness,
                    ])>
                        <x-ui.icon name="crown" class="size-5.5"/>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-extrabold text-content-main">پلن {{ $plan->name }}</h3>
                            <span class="rounded-lg border border-line-border/80 bg-surface-base px-2 py-0.5 text-[11px] font-bold text-content-main">
                                {{ $cycle === 'yearly' ? 'سالانه (۳۶۵ روز)' : 'ماهانه (۳۰ روز)' }}
                            </span>
                        </div>
                        <p class="mt-1 text-[11px] text-content-muted">
                            {{ $plan->max_monthly_runs !== null ? number_format($plan->max_monthly_runs) . ' اجرا در ماه' : 'اجرای ماهانه نامحدود' }}
                            •
                            {{ $plan->max_active_workflows !== null ? $plan->max_active_workflows . ' ورک‌فلو فعال' : 'ورک‌فلو فعال نامحدود' }}
                        </p>
                    </div>
                </div>
            </div>

            @if ($canSwitchCycle)
                <div class="flex items-center justify-between gap-2 rounded-xl border border-line-border/80 bg-surface-base p-1.5 text-xs">
                    <span class="ps-2 font-semibold text-content-muted">دوره پرداخت:</span>
                    <div class="flex items-center gap-1">
                        <button type="button"
                                wire:click="setCheckoutCycle('monthly')"
                            @class([
                                'rounded-lg px-3 py-1.5 text-xs transition cursor-pointer',
                                'bg-primary text-white font-bold shadow-2xs' => $cycle === 'monthly',
                                'font-medium text-content-muted hover:text-content-main' => $cycle !== 'monthly',
                            ])>
                            ماهانه
                        </button>
                        <button type="button"
                                wire:click="setCheckoutCycle('yearly')"
                            @class([
                                'inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs transition cursor-pointer',
                                'bg-primary text-white font-bold shadow-2xs' => $cycle === 'yearly',
                                'font-medium text-content-muted hover:text-content-main' => $cycle !== 'yearly',
                            ])>
                            <span>سالانه</span>
                            <span @class([
                                'rounded px-1 py-0.5 text-[10px] font-bold',
                                'bg-white/20 text-white' => $cycle === 'yearly',
                                'bg-emerald-500/15 text-emerald-500' => $cycle !== 'yearly',
                            ])>۲ ماه رایگان</span>
                        </button>
                    </div>
                </div>
            @endif

            <div class="rounded-2xl border border-line-border bg-surface-card p-3.5 space-y-2.5">
                <div class="flex items-center justify-between">
                    <label for="checkout-discount-input" class="flex items-center gap-1.5 text-xs font-bold text-content-main">
                        <x-ui.icon name="discount-2" class="size-4 text-primary"/>
                        <span>کد تخفیف دارید؟</span>
                    </label>

                    @if ($appliedCode)
                        <button type="button"
                                wire:click="removeDiscountCode"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-primary hover:underline cursor-pointer">
                            <x-ui.icon name="x" class="size-3.5"/>
                            <span>حذف کد</span>
                        </button>
                    @endif
                </div>

                @if ($appliedCode)
                    <div class="flex items-center justify-between gap-2 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3.5 py-2.5 text-xs text-emerald-600 dark:text-emerald-400">
                        <div class="flex min-w-0 items-center gap-2">
                            <x-ui.icon name="check" class="size-4 shrink-0 text-emerald-500"/>
                            <span class="truncate font-semibold">{{ $discountMessage ?: 'کد تخفیف اعمال شد.' }}</span>
                        </div>
                        <span dir="ltr" class="shrink-0 rounded-lg bg-emerald-500/20 px-2 py-0.5 font-mono text-[11px] font-extrabold">
                            {{ $appliedCode }}
                        </span>
                    </div>
                @else
                    <div class="flex items-center gap-2">
                        <input id="checkout-discount-input"
                               type="text"
                               dir="ltr"
                               maxlength="40"
                               wire:model="discountInput"
                               wire:keydown.enter.prevent="applyDiscountCode"
                               placeholder="CODE..."
                               class="h-10 min-w-0 flex-1 rounded-xl border border-line-border bg-surface-base px-3.5 text-left font-mono text-xs font-bold uppercase tracking-wider text-content-main transition placeholder:font-sans placeholder:tracking-normal placeholder:font-normal placeholder:text-content-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"/>

                        <button type="button"
                                wire:click="applyDiscountCode"
                                wire:loading.attr="disabled"
                                wire:target="applyDiscountCode"
                                class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl border border-line-border bg-surface-raised px-4 text-xs font-bold text-content-main transition hover:border-primary hover:text-primary disabled:opacity-50 cursor-pointer">
                            <span wire:loading.remove wire:target="applyDiscountCode">اعمال کد</span>
                            <span wire:loading wire:target="applyDiscountCode">بررسی...</span>
                        </button>
                    </div>

                    @if ($discountMessage && ! $discountSuccess)
                        <p class="flex items-center gap-1.5 text-[11px] font-medium text-primary">
                            <x-ui.icon name="alert-triangle" class="size-3.5 shrink-0"/>
                            <span>{{ $discountMessage }}</span>
                        </p>
                    @endif
                @endif
            </div>

            <div class="rounded-2xl border border-line-border bg-surface-base/70 p-4 space-y-2.5 text-xs">
                <div class="flex items-center justify-between text-content-muted">
                    <span>هزینه اشتراک {{ $plan->name }} ({{ $cycle === 'yearly' ? 'سالانه' : 'ماهانه' }})</span>
                    <span class="font-mono font-bold tabular-nums text-content-main">
                        {{ number_format($quote['base_price']) }} <span class="font-sans text-[11px] font-normal text-content-muted">تومان</span>
                    </span>
                </div>

                @if ($quote['is_prorated'] && $quote['credit_amount'] > 0)
                    <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400">
                        <span>کسر اعتبار {{ $quote['unused_days'] }} روز باقی‌مانده اشتراک فعلی</span>
                        <span class="font-mono font-bold tabular-nums">
                            {{ number_format($quote['credit_amount']) }}- <span class="font-sans text-[11px] font-normal">تومان</span>
                        </span>
                    </div>
                @endif

                @if ($quote['discount_amount'] > 0)
                    <div class="flex items-center justify-between text-emerald-600 dark:text-emerald-400">
                        <span>کسر کد تخفیف ({{ $quote['discount_code'] }})</span>
                        <span dir="ltr" class="font-mono font-bold tabular-nums">
                            -{{ number_format($quote['discount_amount']) }} <span class="font-sans text-[11px] font-normal">تومان</span>
                        </span>
                    </div>
                @endif

                <div class="border-t border-line-border/80 pt-3 flex items-center justify-between">
                    <span class="font-extrabold text-content-main">مبلغ نهایی قابل پرداخت</span>
                    @if ($isFreeCheckout)
                        <span class="inline-flex items-center gap-1.5 font-extrabold text-emerald-500">
                            <span class="rounded-md bg-emerald-500/15 px-2 py-0.5 text-[11px]">۱۰۰٪ تخفیف</span>
                            <span>رایگان (۰ تومان)</span>
                        </span>
                    @else
                        <div class="flex items-baseline gap-1">
                            <span class="font-mono text-lg font-extrabold tabular-nums text-primary">
                                {{ number_format($quote['payable_amount']) }}
                            </span>
                            <span class="text-[11px] font-bold text-content-muted">تومان</span>
                        </div>
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ route('app.billing.checkout') }}" class="pt-1">
                @csrf
                <input type="hidden" name="plan" value="{{ $plan->slug }}">
                <input type="hidden" name="billing_cycle" value="{{ $cycle }}">
                @if ($appliedCode)
                    <input type="hidden" name="discount_code" value="{{ $appliedCode }}">
                @endif

                <div class="flex items-center justify-end gap-2 border-t border-line-border/60 pt-4">
                    <x-ui.button type="button"
                                 variant="ghost"
                                 @click="$dispatch('close-modal', 'billing-checkout-modal')"
                                 class="cursor-pointer">
                        انصراف
                    </x-ui.button>

                    <x-ui.button type="submit" class="cursor-pointer">
                        @if ($isFreeCheckout)
                            <x-ui.icon name="sparkles" class="size-4"/>
                            <span>فعال‌سازی رایگان اشتراک</span>
                        @else
                            <x-ui.icon name="credit-card" class="size-4"/>
                            <span>پرداخت آنلاین با زیبال</span>
                        @endif
                    </x-ui.button>
                </div>
            </form>
        </div>
    @endif
</x-ui.modal>
