@php
    $traceId = strtoupper(substr(md5(uniqid('', true)), 0, 8));
    $occurredAt = now()->setTimezone('Asia/Tehran')->format('Y-m-d H:i:s');
@endphp

<x-errors.layout
    code="500"
    image="500.webp"
    badge="اختلال فنی سرور"
    title="مشکلی در پردازش درخواست پیش آمد!"
    description="یک خطای غیرمنتظره در سمت سرور رخ داده است. جزئیات فنی این خطا در سیستم ثبت شده و تیم فنی تمشک فلو آن را بررسی خواهد کرد."
    accent="primary"
    primary-action="reload"
    primary-label="تلاش مجدد"
    secondary-action="support"
>
    <div class="mx-auto flex items-center justify-between gap-3 rounded-2xl border border-line-border/80 bg-surface-card/90 px-4 py-2.5 text-[11px] text-content-muted shadow-2xs">
        <span class="font-medium">کد پیگیری خطا:</span>
        <div dir="ltr" class="flex items-center gap-2 font-mono">
            <span class="rounded-md bg-primary/10 px-2 py-0.5 font-bold text-primary select-all">ERR-{{ $traceId }}</span>
            <span class="text-content-muted/70">{{ $occurredAt }}</span>
        </div>
    </div>
</x-errors.layout>
