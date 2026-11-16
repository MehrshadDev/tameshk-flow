@php
    use App\Models\Plan;

    $plans = Plan::query()->active()->get();

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

@if (config('saas.enabled') && $plans->isNotEmpty())
    <section id="pricing"
             x-data="{ cycle: 'monthly' }"
             class="w-full border-t border-line-border bg-surface-base py-20 font-vazirmatn-rd sm:py-24">
        <div class="mx-auto max-w-5xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <h2 class="text-2xl font-extrabold tracking-tight text-content-main sm:text-3xl">
                    پلن‌های اشتراک شفاف و منعطف
                </h2>
                <p class="mt-3 text-sm leading-7 text-content-muted">
                    با پلن رایگان شروع کنید و هر زمان که کسب‌وکارتان رشد کرد، ظرفیت اجرا و ورک‌فلوهای خود را ارتقا دهید.
                </p>

                <div class="mt-7 flex justify-center">
                    <x-ui.segmented
                        alpine="cycle"
                        variant="card"
                        :options="[
                            'monthly' => 'پرداخت ماهانه',
                            'yearly' => [
                                'label' => 'پرداخت سالانه',
                                'badge' => '۲ ماه رایگان',
                            ],
                        ]"
                    />
                </div>
            </div>

            <div class="mt-12 grid grid-cols-1 gap-6 md:grid-cols-3">
                @foreach ($plans as $plan)
                    @php
                        $isPopular = $plan->slug === 'pro';
                        $features = is_array($plan->features) ? $plan->features : [];
                        $monthlyPrice = $formatPrice($plan->monthly_price);
                        $yearlyPrice = $formatPrice($plan->yearly_price);
                    @endphp

                    <div @class([
                        'relative flex flex-col justify-between rounded-2xl border p-5 sm:p-7 transition-all duration-200',
                        'mt-2 md:mt-0 border-primary/80 bg-surface-card ring-1 ring-primary/20 shadow-xl shadow-primary/5' => $isPopular,
                        'border-line-border bg-surface-card/90 shadow-2xs hover:border-content-muted/30' => ! $isPopular,
                    ])>
                        @if ($isPopular)
                            <div class="pointer-events-none absolute inset-x-8 top-0 h-px bg-linear-to-r from-transparent via-primary to-transparent"></div>
                            <span class="absolute -top-3 right-5 inline-flex items-center gap-1 whitespace-nowrap rounded-full bg-primary px-3 py-0.5 text-[11px] font-bold text-white shadow-sm shadow-primary/30">
                                <x-ui.icon name="sparkles" class="size-3"/>
                                <span>محبوب‌ترین</span>
                            </span>
                        @endif

                        <div>
                            <h3 class="text-lg font-extrabold text-content-main">{{ $plan->name }}</h3>
                            <p class="mt-2 text-xs leading-6 text-content-muted md:min-h-12">{{ $plan->description }}</p>

                            <div class="my-5 border-y border-line-border/70 py-4 sm:my-6 sm:py-5">
                                @if ($plan->isFree())
                                    <div class="flex items-baseline gap-1.5">
                                        <span dir="ltr" class="font-mono text-3xl font-extrabold tracking-tight tabular-nums text-content-main">
                                            0
                                        </span>
                                        <span class="text-sm font-bold text-content-main">تومان</span>
                                        <span class="text-xs font-medium text-content-muted">/ همیشگی</span>
                                    </div>
                                @else
                                    <div x-show="cycle === 'monthly'" class="flex items-baseline gap-1.5">
                                        <span dir="ltr" class="font-mono text-3xl font-extrabold tracking-tight tabular-nums text-content-main">
                                            {{ $monthlyPrice['value'] }}
                                        </span>
                                        <span class="text-sm font-bold text-content-main">{{ $monthlyPrice['unit'] }}</span>
                                        <span class="text-xs font-medium text-content-muted">/ ماهانه</span>
                                    </div>

                                    <div x-show="cycle === 'yearly'" x-cloak class="flex items-baseline gap-1.5">
                                        <span dir="ltr" class="font-mono text-3xl font-extrabold tracking-tight tabular-nums text-content-main">
                                            {{ $yearlyPrice['value'] }}
                                        </span>
                                        <span class="text-sm font-bold text-content-main">{{ $yearlyPrice['unit'] }}</span>
                                        <span class="text-xs font-medium text-content-muted">/ سالانه</span>
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
                            <x-ui.button href="{{ route('register') }}"
                                         :variant="$isPopular ? 'primary' : 'secondary'"
                                         class="w-full py-2.5 text-xs font-bold">
                                {{ $plan->isFree() ? 'شروع رایگان' : 'انتخاب پلن ' . $plan->name }}
                            </x-ui.button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
