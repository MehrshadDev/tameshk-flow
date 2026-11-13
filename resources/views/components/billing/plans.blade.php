@props([
    'plans',
    'currentPlan',
    'activeSubscription' => null,
    'canRenewCurrent' => false,
    'canSelectMonthly' => true,
    'billingCycle' => 'monthly',
])

@php
    use App\Subscriptions\SubscriptionManager;

    $subscriptions = app(SubscriptionManager::class);
    $user = auth()->user();
    $planCount = $plans->count();
    $isSinglePlan = $planCount === 1;
    $showCycleToggle = $canSelectMonthly && ! ($currentPlan->slug === 'business' && ! $canRenewCurrent);

    $gridCols = match ($planCount) {
        1 => 'grid-cols-1',
        2 => 'grid-cols-1 lg:grid-cols-2',
        default => 'grid-cols-1 md:grid-cols-3',
    };

    $formatPrice = function (int $amount): array {
        if ($amount <= 0) {
            return ['value' => '0', 'unit' => 'تومان'];
        }

        if ($amount >= 1000000) {
            $millions = round($amount / 1000000, 2);
            $formatted = rtrim(rtrim(number_format($millions, 2, '.', ','), '0'), '.');

            return ['value' => $formatted, 'unit' => 'میلیون تومان'];
        }

        return [
            'value' => number_format((int) round($amount / 1000)),
            'unit' => 'هزار تومان',
        ];
    };
@endphp

<div class="space-y-5 rounded-2xl border border-line-border bg-surface-card p-5 shadow-2xs sm:p-6">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h3 class="text-base font-extrabold text-content-main">
                {{ $currentPlan->slug === 'business' && ! $canRenewCurrent ? 'وضعیت پلن اشتراک شما' : 'ارتقا و مدیریت پلن اشتراک' }}
            </h3>
            <p class="mt-1 text-xs text-content-muted">
                @if ($currentPlan->slug === 'business' && ! $canRenewCurrent)
                    @if ($activeSubscription?->billing_cycle === 'yearly')
                        شما در حال استفاده از بالاترین پلن و دوره سالانه تمشک فلو هستید.
                    @else
                        در صورت ارتقا به دوره سالانه، اعتبار روزهای استفاده‌نشده اشتراک فعلی شما به‌صورت روزشمار محاسبه و از مبلغ کسر می‌شود.
                    @endif
                @elseif (! $canSelectMonthly)
                    شما در حال استفاده از دوره سالانه هستید و در صورت ارتقا، اعتبار روزهای باقی‌مانده شما کسر می‌گردد.
                @else
                    در صورت ارتقای پلن یا دوره، اعتبار روزهای استفاده‌نشده اشتراک فعلی شما به‌صورت روزشمار محاسبه و از مبلغ کسر می‌شود.
                @endif
            </p>
        </div>

        @if ($showCycleToggle)
            <div class="inline-flex self-start rounded-xl border border-line-border bg-surface-base p-1 sm:self-auto">
                <button type="button"
                        wire:click="setBillingCycle('monthly')"
                    @class([
                        'rounded-lg px-4 py-1.5 text-xs transition cursor-pointer',
                        'bg-primary text-white font-bold shadow-2xs' => $billingCycle === 'monthly',
                        'font-medium text-content-muted hover:text-content-main' => $billingCycle !== 'monthly',
                    ])>
                    ماهانه
                </button>

                <button type="button"
                        wire:click="setBillingCycle('yearly')"
                    @class([
                        'inline-flex items-center gap-1.5 rounded-lg px-4 py-1.5 text-xs transition cursor-pointer',
                        'bg-primary text-white font-bold shadow-2xs' => $billingCycle === 'yearly',
                        'font-medium text-content-muted hover:text-content-main' => $billingCycle !== 'yearly',
                    ])>
                    <span>سالانه</span>
                    <span @class([
                        'rounded-md px-1.5 py-0.5 text-[10px] font-bold',
                        'bg-white/20 text-white' => $billingCycle === 'yearly',
                        'bg-emerald-500/15 text-emerald-500' => $billingCycle !== 'yearly',
                    ])>
                        ۲ ماه رایگان
                    </span>
                </button>
            </div>
        @endif
    </div>

    <div class="grid gap-5 {{ $gridCols }}">
        @foreach ($plans as $plan)
            @php
                $isCurrentPlan = $currentPlan->id === $plan->id;
                $isDowngrade = $plan->sort_order < $currentPlan->sort_order;
                $currentSubCycle = $activeSubscription?->billing_cycle;

                $effectiveCardCycle = ($isCurrentPlan && ! $canRenewCurrent && $currentSubCycle === 'monthly')
                    ? 'yearly'
                    : $billingCycle;

                $quote = $user
                    ? $subscriptions->calculateQuote($user, $plan, $effectiveCardCycle)
                    : [
                        'base_price' => $plan->priceFor($effectiveCardCycle),
                        'credit_amount' => 0,
                        'payable_amount' => $plan->priceFor($effectiveCardCycle),
                        'unused_days' => 0,
                        'is_prorated' => false,
                    ];

                $baseFormatted = $formatPrice($quote['base_price']);
                $payableFormatted = $formatPrice($quote['payable_amount']);
                $creditFormatted = $formatPrice($quote['credit_amount']);
                $regularFormatted = $formatPrice($plan->priceFor($billingCycle));

                $isHighlighted = ($plan->slug === 'pro' && $currentPlan->slug === 'free')
                    || ($plan->slug === 'business' && $currentPlan->slug === 'pro');
                $features = is_array($plan->features) ? $plan->features : [];
            @endphp

            @if ($isSinglePlan)
                <div class="grid grid-cols-1 gap-6 rounded-2xl border border-amber-500/30 bg-linear-to-br from-amber-500/5 via-surface-base/60 to-surface-card p-5 sm:p-6 lg:grid-cols-12 lg:items-center">
                    <div class="flex flex-col justify-between space-y-5 lg:col-span-5 lg:border-l lg:border-line-border/70 lg:pl-6">
                        <div>
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/15 text-amber-500 ring-1 ring-amber-500/30">
                                        <x-ui.icon name="crown" class="size-5"/>
                                    </span>
                                    <h4 class="text-lg font-extrabold text-content-main">پلن {{ $plan->name }}</h4>
                                </div>

                                @if ($isCurrentPlan)
                                    <x-ui.badge variant="success" dot>
                                        پلن فعال شما ({{ $currentSubCycle === 'yearly' ? 'سالانه' : ($currentSubCycle === 'monthly' ? 'ماهانه' : 'پایه') }})
                                    </x-ui.badge>
                                @endif
                            </div>

                            <p class="mt-2.5 text-xs leading-6 text-content-muted">
                                {{ $plan->description }}
                            </p>

                            <div class="mt-5 border-y border-line-border/70 py-4">
                                @if ($quote['is_prorated'])
                                    <div class="flex flex-wrap items-baseline gap-2">
                                        <span dir="ltr" class="font-mono text-sm font-semibold tabular-nums text-content-muted line-through">
                                            {{ $baseFormatted['value'] }}
                                        </span>
                                        <span dir="ltr" class="font-mono text-3xl font-extrabold tracking-tight tabular-nums text-emerald-500">
                                            {{ $payableFormatted['value'] }}
                                        </span>
                                        <span class="text-sm font-bold text-content-main">{{ $payableFormatted['unit'] }}</span>
                                        <span class="text-xs font-medium text-content-muted">
                                            / {{ $effectiveCardCycle === 'yearly' ? 'سالانه' : 'ماهانه' }}
                                        </span>
                                    </div>
                                    <p class="mt-1.5 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                        با کسر <span dir="ltr" class="font-mono">{{ $creditFormatted['value'] }}</span> {{ $creditFormatted['unit'] }} بابت {{ $quote['unused_days'] }} روز باقی‌مانده از اشتراک فعلی
                                    </p>
                                @else
                                    <div class="flex items-baseline gap-1.5">
                                        <span dir="ltr" class="font-mono text-3xl font-extrabold tracking-tight tabular-nums text-content-main">
                                            {{ $baseFormatted['value'] }}
                                        </span>
                                        <span class="text-sm font-bold text-content-main">{{ $baseFormatted['unit'] }}</span>
                                        <span class="text-xs font-medium text-content-muted">
                                            / {{ $effectiveCardCycle === 'yearly' ? 'سالانه' : 'ماهانه' }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div>
                            @if ($canRenewCurrent)
                                <button type="button"
                                        wire:click="openCheckout('{{ $plan->slug }}', '{{ $billingCycle }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="openCheckout('{{ $plan->slug }}', '{{ $billingCycle }}')"
                                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-white shadow-sm shadow-primary/20 transition hover:bg-primary/90 active:scale-[0.99] cursor-pointer disabled:opacity-60">
                                    <x-ui.icon name="credit-card" class="size-4"/>
                                    <span>تمدید اشتراک {{ $plan->name }} ({{ $billingCycle === 'yearly' ? 'سالانه' : 'ماهانه' }})</span>
                                </button>
                            @elseif ($currentSubCycle === 'monthly')
                                <button type="button"
                                        wire:click="openCheckout('{{ $plan->slug }}', 'yearly')"
                                        wire:loading.attr="disabled"
                                        wire:target="openCheckout('{{ $plan->slug }}', 'yearly')"
                                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-primary/40 bg-primary/10 px-4 py-2.5 text-xs font-bold text-primary transition hover:bg-primary hover:text-white active:scale-[0.99] cursor-pointer disabled:opacity-60">
                                    <x-ui.icon name="sparkles" class="size-4"/>
                                    <span>ارتقا به دوره سالانه — پرداخت <span dir="ltr" class="font-mono">{{ $payableFormatted['value'] }}</span> {{ $payableFormatted['unit'] }}</span>
                                </button>
                            @else
                                <div class="flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 select-none">
                                    <x-ui.icon name="check" class="size-4"/>
                                    <span>اشتراک سالانه شما فعال است ({{ $activeSubscription?->remainingDays() }} روز باقی‌مانده)</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="space-y-4 lg:col-span-7">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="flex items-center justify-between rounded-xl border border-line-border/70 bg-surface-card p-3.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-sky-500/10 text-sky-500">
                                        <x-ui.icon name="player-play" class="size-4"/>
                                    </span>
                                    <span class="text-xs font-medium text-content-muted">تعداد اجرای ماهانه</span>
                                </div>
                                <span class="font-mono text-sm font-extrabold text-content-main">
                                    {{ $plan->max_monthly_runs !== null ? number_format($plan->max_monthly_runs) : 'نامحدود' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-between rounded-xl border border-line-border/70 bg-surface-card p-3.5">
                                <div class="flex items-center gap-2.5">
                                    <span class="flex size-8 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-500">
                                        <x-ui.icon name="bolt" class="size-4"/>
                                    </span>
                                    <span class="text-xs font-medium text-content-muted">ورک‌فلوهای فعال</span>
                                </div>
                                <span class="font-mono text-sm font-extrabold text-content-main">
                                    {{ $plan->max_active_workflows !== null ? $plan->max_active_workflows . ' عدد' : 'نامحدود' }}
                                </span>
                            </div>
                        </div>

                        <div class="rounded-xl border border-line-border/60 bg-surface-card/70 p-4">
                            <p class="mb-3 text-xs font-bold text-content-main">امکانات و ظرفیت‌های پلن شما:</p>
                            <ul class="grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                                @foreach ($features as $feature)
                                    <li class="flex items-center gap-2.5 text-xs text-content-muted">
                                        <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-500">
                                            <x-ui.icon name="check" class="size-3"/>
                                        </span>
                                        <span class="leading-5 text-content-main/90">{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @else
                <div @class([
                    'relative flex flex-col justify-between rounded-2xl border p-6 sm:p-7 transition-all duration-200',
                    'border-primary/80 bg-surface-raised ring-1 ring-primary/20 shadow-xl shadow-primary/5' => $isHighlighted,
                    'border-line-border bg-surface-base/60 hover:border-content-muted/30' => ! $isHighlighted,
                ])>
                    @if ($isHighlighted)
                        <div class="pointer-events-none absolute inset-x-8 top-0 h-px bg-linear-to-r from-transparent via-primary to-transparent"></div>
                        <span class="absolute -top-3 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-primary px-3.5 py-0.5 text-[11px] font-bold text-white shadow-sm shadow-primary/30">
                            پیشنهاد ارتقا
                        </span>
                    @endif

                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <h4 class="text-lg font-extrabold text-content-main">پلن {{ $plan->name }}</h4>
                            @if ($isCurrentPlan)
                                <x-ui.badge variant="success" dot>
                                    پلن فعال شما ({{ $currentSubCycle === 'yearly' ? 'سالانه' : ($currentSubCycle === 'monthly' ? 'ماهانه' : 'پایه') }})
                                </x-ui.badge>
                            @endif
                        </div>

                        <p class="mt-2 min-h-12 text-xs leading-6 text-content-muted">
                            {{ $plan->description }}
                        </p>

                        <div class="my-6 border-y border-line-border/70 py-5">
                            @if ($plan->isFree())
                                <div class="flex items-baseline gap-1.5">
                                    <span dir="ltr" class="font-mono text-3xl font-extrabold tracking-tight tabular-nums text-content-main">0</span>
                                    <span class="text-sm font-bold text-content-main">تومان</span>
                                    <span class="text-xs font-medium text-content-muted">/ همیشگی</span>
                                </div>
                            @elseif ($quote['is_prorated'])
                                <div class="flex flex-wrap items-baseline gap-2">
                                    <span dir="ltr" class="font-mono text-sm font-semibold tabular-nums text-content-muted line-through">
                                        {{ $baseFormatted['value'] }}
                                    </span>
                                    <span dir="ltr" class="font-mono text-3xl font-extrabold tracking-tight tabular-nums text-emerald-500">
                                        {{ $payableFormatted['value'] }}
                                    </span>
                                    <span class="text-sm font-bold text-content-main">{{ $payableFormatted['unit'] }}</span>
                                    <span class="text-xs font-medium text-content-muted">
                                        / {{ $effectiveCardCycle === 'yearly' ? 'سالانه' : 'ماهانه' }}
                                    </span>
                                </div>
                                <p class="mt-1.5 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                    با کسر <span dir="ltr" class="font-mono">{{ $creditFormatted['value'] }}</span> {{ $creditFormatted['unit'] }} بابت {{ $quote['unused_days'] }} روز باقی‌مانده از اشتراک فعلی
                                </p>
                            @else
                                <div class="flex items-baseline gap-1.5">
                                    <span dir="ltr" class="font-mono text-3xl font-extrabold tracking-tight tabular-nums text-content-main">
                                        {{ $regularFormatted['value'] }}
                                    </span>
                                    <span class="text-sm font-bold text-content-main">{{ $regularFormatted['unit'] }}</span>
                                    <span class="text-xs font-medium text-content-muted">
                                        / {{ $billingCycle === 'yearly' ? 'سالانه' : 'ماهانه' }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <ul class="space-y-3">
                            @foreach ($features as $feature)
                                <li class="flex items-center gap-2.5 text-xs text-content-muted">
                                    <span class="flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-500/15 text-emerald-500">
                                        <x-ui.icon name="check" class="size-3"/>
                                    </span>
                                    <span class="leading-5 text-content-main/90">{{ $feature }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="mt-8 pt-1">
                        @if ($plan->isFree())
                            <div class="flex w-full items-center justify-center gap-1.5 rounded-xl border border-line-border bg-surface-card py-2.5 text-xs font-bold text-content-muted select-none">
                                <x-ui.icon name="check" class="size-4 text-emerald-500"/>
                                <span>پلن پیش‌فرض فعال</span>
                            </div>
                        @elseif ($isCurrentPlan)
                            @if ($canRenewCurrent)
                                <button type="button"
                                        wire:click="openCheckout('{{ $plan->slug }}', '{{ $billingCycle }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="openCheckout('{{ $plan->slug }}', '{{ $billingCycle }}')"
                                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-white shadow-sm shadow-primary/20 transition hover:bg-primary/90 active:scale-[0.99] cursor-pointer disabled:opacity-60">
                                    <x-ui.icon name="credit-card" class="size-4"/>
                                    <span>تمدید اشتراک {{ $plan->name }} ({{ $billingCycle === 'yearly' ? 'سالانه' : 'ماهانه' }})</span>
                                </button>
                            @elseif ($currentSubCycle === 'monthly')
                                <button type="button"
                                        wire:click="openCheckout('{{ $plan->slug }}', 'yearly')"
                                        wire:loading.attr="disabled"
                                        wire:target="openCheckout('{{ $plan->slug }}', 'yearly')"
                                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-primary/40 bg-primary/10 px-4 py-2.5 text-xs font-bold text-primary transition hover:bg-primary hover:text-white active:scale-[0.99] cursor-pointer disabled:opacity-60">
                                    <x-ui.icon name="sparkles" class="size-4"/>
                                    <span>ارتقا به دوره سالانه — پرداخت <span dir="ltr" class="font-mono">{{ $payableFormatted['value'] }}</span> {{ $payableFormatted['unit'] }}</span>
                                </button>
                            @else
                                <div class="flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-2.5 text-xs font-bold text-emerald-600 dark:text-emerald-400 select-none">
                                    <x-ui.icon name="check" class="size-4"/>
                                    <span>اشتراک سالانه شما فعال است ({{ $activeSubscription?->remainingDays() }} روز باقی‌مانده)</span>
                                </div>
                            @endif
                        @elseif ($isDowngrade)
                            <div>
                                <button type="button"
                                        wire:click="openCheckout('{{ $plan->slug }}', '{{ $billingCycle }}')"
                                        wire:loading.attr="disabled"
                                        wire:target="openCheckout('{{ $plan->slug }}', '{{ $billingCycle }}')"
                                        class="flex w-full items-center justify-center gap-2 rounded-xl border border-line-border bg-surface-card px-4 py-2.5 text-xs font-bold text-content-main transition hover:border-primary hover:text-primary active:scale-[0.99] cursor-pointer disabled:opacity-60">
                                    <x-ui.icon name="credit-card" class="size-4"/>
                                    <span>تغییر به پلن {{ $plan->name }} ({{ $billingCycle === 'yearly' ? 'سالانه' : 'ماهانه' }})</span>
                                </button>
                                <p class="mt-1.5 text-center text-[10px] text-content-muted">
                                    با تغییر به پرو، حداکثر ۱۰ ورک‌فلوی آخر شما فعال می‌ماند و بقیه متوقف می‌شوند.
                                </p>
                            </div>
                        @else
                            <button type="button"
                                    wire:click="openCheckout('{{ $plan->slug }}', '{{ $billingCycle }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="openCheckout('{{ $plan->slug }}', '{{ $billingCycle }}')"
                                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-bold text-white shadow-sm shadow-primary/20 transition hover:bg-primary/90 active:scale-[0.99] cursor-pointer disabled:opacity-60">
                                <x-ui.icon name="credit-card" class="size-4"/>
                                <span>
                                    ارتقا به پلن {{ $plan->name }}
                                    @if ($quote['is_prorated'])
                                        (<span dir="ltr" class="font-mono">{{ $payableFormatted['value'] }}</span> {{ $payableFormatted['unit'] }})
                                    @endif
                                </span>
                            </button>
                        @endif
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-line-border/70 bg-surface-base px-4 py-3 text-xs text-content-muted">
        <div class="flex items-center gap-2.5">
            <x-ui.icon name="brand:zibal" class="size-5"/>
            <span>پرداخت امن و آنی از طریق درگاه پرداخت اینترنتی <strong class="text-content-main">زیبال</strong> با تمامی کارت‌های عضو شتاب</span>
        </div>
    </div>
</div>
