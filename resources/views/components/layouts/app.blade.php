@props([
    'title' => config('app.site_name', config('app.name')) . ' | پلتفرم اتوماسیون گردش‌کار و ایجنت هوش مصنوعی',
    'description' => 'تمشک فلو، پلتفرم بصری اتوماسیون گردش‌کار و ساخت ایجنت‌های هوش مصنوعی؛ اتصال سریع تلگرام، جیمیل، گوگل شیت، وب‌سرویس‌ها و مدل‌های زبانی بدون نیاز به کدنویسی.',
    'robots' => 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1',
    'canonical' => null,
    'ogType' => 'website',
    'ogImage' => null,
    'footer_visibility' => false,
    'header_visibility' => false,
])

@php
    $siteName = config('app.site_name') ?: config('app.name', 'تمشک فلو');
    $canonicalUrl = $canonical ?: request()->url();
    $socialImage = $ogImage ?: asset('images/logo/favicon.png');
@endphp
    <!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <x-layouts.head :title="$title" :robots="$robots">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        <link rel="alternate" hreflang="fa-IR" href="{{ $canonicalUrl }}">
        <link rel="alternate" hreflang="x-default" href="{{ $canonicalUrl }}">

        @if (! empty($description))
            <meta name="description" content="{{ $description }}">
        @endif

        <meta property="og:locale" content="fa_IR">
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:type" content="{{ $ogType }}">
        <meta property="og:title" content="{{ $title }}">
        @if (! empty($description))
            <meta property="og:description" content="{{ $description }}">
        @endif
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:image" content="{{ $socialImage }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ $title }}">
        @if (! empty($description))
            <meta name="twitter:description" content="{{ $description }}">
        @endif
        <meta name="twitter:image" content="{{ $socialImage }}">

        @isset($schema)
            {{ $schema }}
        @endisset
    </x-layouts.head>
</head>
<body class="font-sans antialiased bg-surface-base">
<div id="main-content" class="flex-1 overflow-y-auto overflow-x-hidden custom-scrollbar bg-surface-base">
    @if ($header_visibility)
        <x-layouts.app-header/>
    @endif
    <main class="flex-1 min-h-screen bg-surface-base">
        {{ $slot }}
    </main>
    @if ($footer_visibility)
        <x-layouts.app-footer/>
    @endif
</div>
@livewireScripts
</body>
</html>
