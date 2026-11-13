@php
    $customMessage = isset($exception) ? trim((string) $exception->getMessage()) : '';
    $hasPersianMessage = $customMessage !== '' && preg_match('/[\x{0600}-\x{06FF}]/u', $customMessage);
@endphp

<x-errors.layout
    code="403"
    image="403.webp"
    badge="محدودیت سطح دسترسی"
    title="اجازه دسترسی به این بخش را ندارید!"
    :description="$hasPersianMessage ? $customMessage : 'حساب کاربری شما مجوز لازم برای مشاهده این صفحه یا انجام این عملیات را ندارد. اگر فکر می‌کنید اشتباهی رخ داده، با پشتیبانی در ارتباط باشید.'"
    accent="amber"
    primary-action="home"
    secondary-action="support"
/>
