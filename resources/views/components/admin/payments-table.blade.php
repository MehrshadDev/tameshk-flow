@props([
    'payments',
    'paymentSearch' => '',
    'paymentStatus' => 'paid',
])

@php
    use App\Workflow\Support\DateTimeHelper;
@endphp

<div class="space-y-4">
    <div class="flex flex-col gap-2.5 rounded-2xl border border-line-border bg-surface-card p-3 shadow-2xs sm:flex-row sm:items-center sm:justify-between">
        <div class="relative flex-1">
            <x-ui.icon name="search"
                       class="pointer-events-none absolute inset-s-3.5 top-1/2 size-4.5 -translate-y-1/2 text-content-muted"/>
            <input type="text"
                   maxlength="100"
                   wire:model.live.debounce.300ms="paymentSearch"
                   placeholder="جستجو در نام، ایمیل، کد رهگیری یا شماره کارت..."
                   class="h-9.5 w-full rounded-xl border border-line-border bg-surface-base pe-9 ps-10 text-xs font-medium text-content-main transition placeholder:text-content-muted/60 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"/>
            @if ($paymentSearch !== '')
                <button type="button"
                        wire:click="$set('paymentSearch', '')"
                        class="absolute inset-e-2.5 top-1/2 flex size-6 -translate-y-1/2 items-center justify-center rounded-lg text-content-muted transition hover:bg-line-border/60 hover:text-content-main cursor-pointer">
                    <x-ui.icon name="x" class="size-3.5"/>
                </button>
            @endif
        </div>

        <x-ui.segmented
            model="paymentStatus"
            action="setPaymentStatus"
            cols="4"
            size="sm"
            :options="[
                'all' => 'همه',
                'paid' => 'موفق',
                'pending' => 'در انتظار',
                'failed' => 'ناموفق',
            ]"
        />
    </div>

    <div class="relative min-h-40">
        <x-ui.loading-overlay
            target="setPaymentStatus, paymentStatus, paymentSearch, previousPage, nextPage, gotoPage"
            label="در حال بارگذاری تراکنش‌ها..."
        />

        @if ($payments->isEmpty())
            <x-ui.card :padding="false">
                <x-ui.empty-state
                    icon="receipt"
                    title="تراکنشی یافت نشد"
                    description="هیچ خرید یا تراکنشی متناسب با فیلتر انتخابی شما وجود ندارد."
                />
            </x-ui.card>
        @else
            <div class="overflow-hidden rounded-2xl border border-line-border bg-surface-card shadow-2xs">
                <div class="divide-y divide-line-border/60 md:hidden">
                    @foreach ($payments as $payment)
                        @php
                            $dt = ($payment->paid_at ?? $payment->created_at)?->copy()->setTimezone('Asia/Tehran');
                            $persianDate = $dt ? DateTimeHelper::toPersianDate($dt, 'yyyy/MM/dd HH:mm') : '—';
                            $statusVariant = match ($payment->status) {
                                'paid' => 'success',
                                'pending' => 'warning',
                                default => 'danger',
                            };
                            $statusLabel = match ($payment->status) {
                                'paid' => 'پرداخت موفق',
                                'pending' => 'در انتظار',
                                default => 'ناموفق',
                            };
                        @endphp
                        <div class="space-y-3 p-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p dir="auto" class="truncate text-start text-xs font-extrabold text-content-main">
                                        {{ $payment->user?->name ?? 'کاربر حذف‌شده' }}
                                    </p>
                                    <div class="mt-0.5 text-start">
                                        <span dir="ltr" class="inline-block truncate font-mono text-[11px] text-content-muted">
                                            {{ $payment->user?->email ?? '—' }}
                                        </span>
                                    </div>
                                </div>

                                <x-ui.badge :variant="$statusVariant" dot class="shrink-0 whitespace-nowrap">
                                    {{ $statusLabel }}
                                </x-ui.badge>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-line-border/60 bg-surface-base/60 px-3 py-2.5 text-xs">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-content-main">پلن {{ $payment->plan?->name ?? '—' }}</span>
                                    <span class="rounded-md border border-line-border bg-surface-card px-1.5 py-0.5 text-[10px] text-content-muted">
                                        {{ $payment->billing_cycle === 'yearly' ? 'سالانه' : 'ماهانه' }}
                                    </span>
                                </div>

                                <div class="flex items-center gap-1">
                                    @if ($payment->amount === 0)
                                        <span class="font-bold text-emerald-500">رایگان (۰ تومان)</span>
                                    @else
                                        <span class="font-mono text-sm font-extrabold tabular-nums text-content-main">{{ number_format($payment->amount) }}</span>
                                        <span class="text-[11px] text-content-muted">تومان</span>
                                    @endif
                                </div>
                            </div>

                            @if ($payment->discount_amount > 0)
                                <div class="flex items-center justify-between rounded-xl border border-emerald-500/25 bg-emerald-500/10 px-3 py-1.5 text-[11px] text-emerald-600 dark:text-emerald-400">
                                    <span>کد تخفیف {{ $payment->discountCode ? "({$payment->discountCode->code})" : '' }}:</span>
                                    <span dir="ltr" class="inline-block font-mono font-bold">-{{ number_format($payment->discount_amount) }} تومان</span>
                                </div>
                            @endif

                            <div class="flex items-center justify-between text-[11px] text-content-muted">
                                <div class="flex items-center gap-1.5">
                                    <span>رهگیری:</span>
                                    <span dir="ltr" class="inline-block font-mono font-bold text-content-main select-all">
                                        {{ $payment->ref_id ?: '#' . substr($payment->public_id, 0, 8) }}
                                    </span>
                                </div>

                                <span dir="ltr" class="inline-block font-mono">{{ $persianDate }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full text-start text-xs">
                        <thead>
                        <tr class="border-b border-line-border bg-surface-raised/60 text-content-muted">
                            <th class="px-5 py-3.5 text-start font-bold whitespace-nowrap">کاربر خریدار</th>
                            <th class="px-5 py-3.5 text-start font-bold whitespace-nowrap">پلن و دوره</th>
                            <th class="px-5 py-3.5 text-start font-bold whitespace-nowrap">مبلغ و تخفیف</th>
                            <th class="px-5 py-3.5 text-start font-bold whitespace-nowrap">کد رهگیری / کارت</th>
                            <th class="px-5 py-3.5 text-start font-bold whitespace-nowrap">وضعیت</th>
                            <th class="px-5 py-3.5 text-start font-bold whitespace-nowrap">زمان ثبت</th>
                        </tr>
                        </thead>
                        <tbody class="divide-y divide-line-border/60">
                        @foreach ($payments as $payment)
                            @php
                                $dt = ($payment->paid_at ?? $payment->created_at)?->copy()->setTimezone('Asia/Tehran');
                                $persianDate = $dt ? DateTimeHelper::toPersianDate($dt, 'yyyy/MM/dd HH:mm') : '—';
                                $statusVariant = match ($payment->status) {
                                    'paid' => 'success',
                                    'pending' => 'warning',
                                    default => 'danger',
                                };
                                $statusLabel = match ($payment->status) {
                                    'paid' => 'پرداخت موفق',
                                    'pending' => 'در انتظار',
                                    default => 'ناموفق',
                                };
                            @endphp
                            <tr class="transition hover:bg-surface-raised/40">
                                <td class="px-5 py-3.5 text-start">
                                    <div class="font-bold text-content-main">{{ $payment->user?->name ?? 'کاربر حذف‌شده' }}</div>
                                    <div class="mt-0.5">
                                        <span dir="ltr" class="inline-block font-mono text-[11px] text-content-muted">{{ $payment->user?->email ?? '—' }}</span>
                                    </div>
                                </td>

                                <td class="px-5 py-3.5 text-start whitespace-nowrap">
                                    <span class="font-bold text-content-main">پلن {{ $payment->plan?->name ?? '—' }}</span>
                                    <span class="ms-1.5 rounded-md border border-line-border bg-surface-base px-1.5 py-0.5 text-[10px] text-content-muted">
                                        {{ $payment->billing_cycle === 'yearly' ? 'سالانه' : 'ماهانه' }}
                                    </span>
                                </td>

                                <td class="px-5 py-3.5 text-start whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        @if ($payment->amount === 0)
                                            <span class="font-bold text-emerald-500">رایگان (۰ تومان)</span>
                                        @else
                                            <div>
                                                <span class="font-mono font-extrabold tabular-nums text-content-main">{{ number_format($payment->amount) }}</span>
                                                <span class="text-[11px] text-content-muted">تومان</span>
                                            </div>
                                        @endif

                                        @if ($payment->discount_amount > 0)
                                            <span class="inline-flex items-center gap-1 rounded-md border border-emerald-500/25 bg-emerald-500/10 px-1.5 py-0.5 text-[10px] font-bold text-emerald-600 dark:text-emerald-400">
                                                @if ($payment->discountCode)
                                                    <span dir="ltr" class="font-mono">{{ $payment->discountCode->code }}</span>
                                                    <span>•</span>
                                                @endif
                                                <span dir="ltr" class="font-mono">-{{ number_format($payment->discount_amount) }}</span>
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-5 py-3.5 text-start whitespace-nowrap">
                                    <div>
                                        <span dir="ltr" class="inline-block font-mono text-xs font-bold text-content-main select-all">
                                            {{ $payment->ref_id ?: '#' . substr($payment->public_id, 0, 8) }}
                                        </span>
                                    </div>
                                    @if ($payment->card_pan)
                                        <div class="mt-0.5">
                                            <span dir="ltr" class="inline-block font-mono text-[10px] text-content-muted">
                                                {{ $payment->card_pan }}
                                            </span>
                                        </div>
                                    @endif
                                </td>

                                <td class="px-5 py-3.5 text-start whitespace-nowrap">
                                    <x-ui.badge :variant="$statusVariant" dot class="whitespace-nowrap">{{ $statusLabel }}</x-ui.badge>
                                </td>

                                <td class="px-5 py-3.5 text-start whitespace-nowrap">
                                    <div>
                                        <span dir="ltr" class="inline-block font-mono text-xs text-content-main">{{ $persianDate }}</span>
                                    </div>
                                    <div class="mt-0.5 text-[10px] text-content-muted">
                                        {{ ($payment->paid_at ?? $payment->created_at)?->diffForHumans() }}
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <x-ui.pagination :paginator="$payments" />
        @endif
    </div>
</div>
