@props([
    'payments',
])

@php
    use App\Workflow\Support\DateTimeHelper;
@endphp

<div class="space-y-4">
    <div class="flex items-center justify-between px-1">
        <div class="flex items-center gap-2">
            <x-ui.icon name="receipt" class="size-5 text-primary"/>
            <h3 class="text-sm font-extrabold text-content-main">تاریخچه تراکنش‌ها و صورتحساب‌ها</h3>
        </div>
    </div>

    @if ($payments->isEmpty())
        <x-ui.card :padding="false">
            <x-ui.empty-state
                icon="receipt"
                title="هنوز تراکنشی ثبت نشده است"
                description="پس از خرید یا تمدید هر یک از پلن‌های اشتراکی، رسید و جزئیات پرداخت شما در این بخش نمایش داده می‌شود."
            />
        </x-ui.card>
    @else
        <div class="overflow-hidden rounded-2xl border border-line-border bg-surface-card shadow-2xs">
            <div class="divide-y divide-line-border/60 md:hidden">
                @foreach ($payments as $payment)
                    @php
                        $dateObj = ($payment->paid_at ?? $payment->created_at)->copy()->setTimezone('Asia/Tehran');
                        $persianDate = DateTimeHelper::toPersianDate($dateObj, 'yyyy/MM/dd HH:mm');
                    @endphp
                    <div class="space-y-3 p-4">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-extrabold text-content-main">
                                    پلن {{ $payment->plan?->name ?? '—' }}
                                </span>
                                <span class="rounded-md border border-line-border bg-surface-base px-1.5 py-0.5 text-[10px] font-medium text-content-muted">
                                    {{ $payment->billing_cycle === 'yearly' ? 'سالانه' : 'ماهانه' }}
                                </span>
                            </div>

                            <div class="flex items-center gap-1.5 text-xs">
                                @if ($payment->amount === 0)
                                    <span class="font-bold text-emerald-500">رایگان</span>
                                @else
                                    <span class="font-mono text-sm font-extrabold tabular-nums text-content-main">{{ number_format($payment->amount) }}</span>
                                    <span class="text-[11px] text-content-muted">تومان</span>
                                @endif
                            </div>
                        </div>

                        @if ($payment->discount_amount > 0)
                            <div class="flex items-center justify-between rounded-lg border border-emerald-500/25 bg-emerald-500/10 px-2.5 py-1 text-[11px] text-emerald-600 dark:text-emerald-400">
                                <span>کد تخفیف {{ $payment->discountCode ? "({$payment->discountCode->code})" : '' }}:</span>
                                <span dir="ltr" class="font-mono font-bold">-{{ number_format($payment->discount_amount) }} تومان</span>
                            </div>
                        @endif

                        <div class="grid grid-cols-2 gap-2 rounded-xl border border-line-border/60 bg-surface-base/60 p-2.5 text-[11px]">
                            <div class="flex items-center justify-between gap-1">
                                <span class="text-content-muted">کد رهگیری:</span>
                                <span dir="ltr" class="font-mono font-bold text-content-main select-all">{{ $payment->ref_id ?: '—' }}</span>
                            </div>

                            <div class="flex items-center justify-between gap-1 border-r border-line-border/60 pr-2">
                                <span class="text-content-muted">سفارش:</span>
                                <span dir="ltr" class="font-mono font-semibold text-content-muted">#{{ substr($payment->public_id, 0, 8) }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-between text-[11px] text-content-muted">
                            <span>تاریخ پرداخت:</span>
                            <span dir="ltr" class="font-mono">{{ $persianDate }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="hidden overflow-x-auto md:block">
                <table class="w-full text-start text-xs">
                    <thead>
                    <tr class="border-b border-line-border bg-surface-raised/60 text-content-muted">
                        <th class="px-5 py-3.5 text-start font-bold">شناسه سفارش</th>
                        <th class="px-5 py-3.5 text-start font-bold">پلن و دوره</th>
                        <th class="px-5 py-3.5 text-start font-bold">مبلغ پرداختی</th>
                        <th class="px-5 py-3.5 text-start font-bold">کد رهگیری</th>
                        <th class="px-5 py-3.5 text-start font-bold">تاریخ</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-line-border/60">
                    @foreach ($payments as $payment)
                        @php
                            $dateObj = ($payment->paid_at ?? $payment->created_at)->copy()->setTimezone('Asia/Tehran');
                            $persianDate = DateTimeHelper::toPersianDate($dateObj, 'yyyy/MM/dd HH:mm');
                        @endphp
                        <tr class="transition hover:bg-surface-raised/40">
                            <td class="px-5 py-3.5 text-start">
                                <span dir="ltr" class="inline-block font-mono text-[11px] font-semibold text-content-muted">
                                    #{{ substr($payment->public_id, 0, 8) }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-start">
                                <span class="font-bold text-content-main">پلن {{ $payment->plan?->name ?? '—' }}</span>
                                <span class="ms-1.5 rounded-md border border-line-border bg-surface-base px-1.5 py-0.5 text-[10px] text-content-muted">
                                    {{ $payment->billing_cycle === 'yearly' ? 'سالانه' : 'ماهانه' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-start">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($payment->amount === 0)
                                        <span class="font-bold text-emerald-500">رایگان (۰ تومان)</span>
                                    @else
                                        <div>
                                            <span class="font-mono font-bold tabular-nums text-content-main">{{ number_format($payment->amount) }}</span>
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
                            <td class="px-5 py-3.5 text-start">
                                <span dir="ltr" class="inline-block font-mono text-xs font-bold text-content-main select-all">
                                    {{ $payment->ref_id ?: '—' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-start">
                                <span dir="ltr" class="inline-block font-mono text-xs text-content-muted">
                                    {{ $persianDate }}
                                </span>
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
