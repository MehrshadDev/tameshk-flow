@props([
    'code' => '404',
    'image' => '404.webp',
    'badge' => 'خطای سیستم',
    'title' => 'خطایی رخ داده است',
    'description' => 'متاسفانه مشکلی در بارگذاری این صفحه پیش آمده است.',
    'accent' => 'primary', // primary, amber, purple, sky
    'primaryAction' => 'home', // home, reload, login
    'primaryLabel' => null,
    'secondaryAction' => 'back', // back, home, support, none
    'secondaryLabel' => null,
    'autoRefresh' => null,
])

@php
    $isAuthenticated = false;
    try {
        $isAuthenticated = auth()->check();
    } catch (Throwable) {
        $isAuthenticated = false;
    }

    $safeRoute = function (string $name, string $fallback = '/') {
        try {
            return route($name);
        } catch (Throwable) {
            return url($fallback);
        }
    };

    $homeUrl = $safeRoute('home', '/');
    $dashboardUrl = $isAuthenticated ? $safeRoute('app.workflows.index', '/app/workflows') : $homeUrl;
    $docsUrl = $safeRoute('docs.index', '/docs');
    $ticketsUrl = $isAuthenticated ? $safeRoute('app.tickets.index', '/app/tickets') : $safeRoute('login', '/login');
    $loginUrl = $safeRoute('login', '/login');

    $resolvedPrimaryLabel = $primaryLabel ?? match ($primaryAction) {
        'reload' => 'تلاش مجدد و نوسازی صفحه',
        'login' => 'ورود مجدد به حساب',
        default => $isAuthenticated ? 'بازگشت به پنل ورکفلوها' : 'بازگشت به صفحه اصلی',
    };

    $resolvedPrimaryHref = match ($primaryAction) {
        'login' => $loginUrl,
        'home' => $dashboardUrl,
        default => null,
    };

    $resolvedSecondaryLabel = $secondaryLabel ?? match ($secondaryAction) {
        'support' => 'گزارش به پشتیبانی',
        'home' => $isAuthenticated ? 'پنل ورکفلوها' : 'صفحه اصلی',
        default => 'صفحه قبلی',
    };

    $accentStyles = match ($accent) {
        'amber' => [
            'glow' => 'rgba(245, 158, 11, 0.20)',
            'badge' => 'border-amber-500/30 bg-amber-500/10 text-amber-600 dark:text-amber-400',
            'dot' => 'bg-amber-500',
        ],
        'purple' => [
            'glow' => 'rgba(168, 85, 247, 0.20)',
            'badge' => 'border-purple-500/30 bg-purple-500/10 text-purple-600 dark:text-purple-400',
            'dot' => 'bg-purple-500',
        ],
        'sky' => [
            'glow' => 'rgba(14, 165, 233, 0.20)',
            'badge' => 'border-sky-500/30 bg-sky-500/10 text-sky-600 dark:text-sky-400',
            'dot' => 'bg-sky-500',
        ],
        default => [
            'glow' => 'rgba(226, 28, 72, 0.20)',
            'badge' => 'border-primary/30 bg-primary/10 text-primary',
            'dot' => 'bg-primary',
        ],
    };

    $imagePath = asset('images/errors/' . ltrim($image, '/'));
    $siteName = config('app.site_name') ?: config('app.name', 'تمشک فلو');
@endphp

<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <x-layouts.head :title="$code . ' - ' . $title . ' | ' . $siteName" :livewire="false"/>
    <style>
        .error-grid-bg {
            background-image:
                linear-gradient(color-mix(in oklab, var(--color-line-border) 55%, transparent) 1px, transparent 1px),
                linear-gradient(90deg, color-mix(in oklab, var(--color-line-border) 55%, transparent) 1px, transparent 1px);
            background-size: 32px 32px;
            -webkit-mask-image: radial-gradient(ellipse 75% 65% at 50% 45%, #000 20%, transparent 100%);
            mask-image: radial-gradient(ellipse 75% 65% at 50% 45%, #000 20%, transparent 100%);
        }

        @keyframes error-float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }

        .animate-error-float {
            animation: error-float 5.5s ease-in-out infinite;
        }
    </style>
</head>
<body class="relative flex min-h-dvh flex-col justify-between overflow-x-hidden bg-surface-base font-vazirmatn-rd text-content-main antialiased selection:bg-primary/20 selection:text-primary">

<div class="pointer-events-none fixed inset-0 -z-10 overflow-hidden" aria-hidden="true">
    <div class="error-grid-bg absolute inset-0 opacity-75"></div>
    <div class="absolute left-1/2 top-1/3 size-128 -translate-x-1/2 -translate-y-1/2 rounded-full blur-3xl"
         style="background: radial-gradient(circle, {{ $accentStyles['glow'] }} 0%, transparent 70%);"></div>
</div>

<header class="mx-auto flex h-16 w-full max-w-6xl shrink-0 items-center justify-between px-4 sm:h-20 sm:px-6">
    <a href="{{ $homeUrl }}" aria-label="{{ $siteName }}" class="inline-flex items-center gap-2">
        <img src="{{ asset('images/logo/svg/logo-lockup.svg') }}"
             alt="{{ $siteName }}"
             class="h-8 w-auto sm:h-9"
             fetchpriority="high">
    </a>

    <div class="flex items-center gap-2">
        <button type="button"
                onclick="const d = !document.documentElement.classList.contains('dark'); document.documentElement.classList.toggle('dark', d); localStorage.setItem('theme', d ? 'dark' : 'light');"
                aria-label="تغییر تم"
                class="flex size-9 items-center justify-center rounded-xl border border-line-border bg-surface-card text-content-muted shadow-2xs transition hover:border-content-muted/40 hover:text-content-main cursor-pointer">
            <span class="flex items-center justify-center dark:hidden">
                <x-ui.icon name="moon" class="size-4.5"/>
            </span>
            <span class="hidden items-center justify-center dark:flex">
                <x-ui.icon name="sun" class="size-4.5"/>
            </span>
        </button>

        <a href="{{ $dashboardUrl }}"
           class="inline-flex items-center gap-1.5 rounded-xl border border-line-border bg-surface-card px-3.5 py-2 text-xs font-bold text-content-main shadow-2xs transition hover:border-primary/40 hover:text-primary">
            <span>{{ $isAuthenticated ? 'پنل کاربری' : 'صفحه اصلی' }}</span>
            <x-ui.icon name="chevron-left" class="size-3.5"/>
        </a>
    </div>
</header>

<main class="mx-auto flex w-full max-w-2xl flex-1 flex-col items-center justify-center px-4 py-4 text-center sm:px-6">

    <div class="animate-error-float relative flex h-40 w-full max-w-md items-center justify-center sm:h-52">
        <img id="error-raw-img"
             src="{{ $imagePath }}"
             alt="Error {{ $code }}"
             crossorigin="anonymous"
             fetchpriority="high"
             class="max-h-full max-w-full object-contain opacity-0 transition-opacity duration-200 select-none pointer-events-none"
             style="filter: drop-shadow(0 18px 28px rgba(226, 28, 72, 0.22));">
    </div>

    <div class="mt-3 flex flex-col items-center">
        <div class="inline-flex items-center gap-2 rounded-full border px-3.5 py-1 text-xs font-extrabold shadow-2xs {{ $accentStyles['badge'] }}">
            <span class="size-1.5 animate-pulse rounded-full {{ $accentStyles['dot'] }}"></span>
            <span>{{ $badge }}</span>
            <span class="h-3 w-px bg-current opacity-25"></span>
            <span dir="ltr" class="font-mono text-[11px] font-bold tracking-wider">HTTP {{ $code }}</span>
        </div>

        <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-content-main sm:text-4xl">
            {{ $title }}
        </h1>

        <p class="mt-3 max-w-lg text-xs leading-7 text-content-muted sm:text-sm sm:leading-8">
            {{ $description }}
        </p>

        @if ($slot->isNotEmpty())
            <div class="mt-4 w-full max-w-md">
                {{ $slot }}
            </div>
        @endif

        @if ($autoRefresh)
            <div id="auto-refresh-box"
                 data-seconds="{{ (int) $autoRefresh }}"
                 class="mt-4 inline-flex items-center gap-2 rounded-xl border border-line-border bg-surface-card px-3.5 py-2 text-xs text-content-muted shadow-2xs">
                <x-ui.icon name="loader" class="size-3.5 animate-spin text-primary"/>
                <span>بررسی مجدد خودکار تا <strong id="refresh-countdown" class="font-mono font-bold text-content-main">{{ (int) $autoRefresh }}</strong> ثانیه دیگر...</span>
            </div>
        @endif

        <div class="mt-6 flex w-full flex-col items-center justify-center gap-2.5 sm:w-auto sm:flex-row sm:gap-3">
            @if ($primaryAction === 'reload')
                <button type="button"
                        onclick="window.location.reload()"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-xs font-bold text-white shadow-lg shadow-primary/25 transition hover:bg-primary/90 active:scale-98 sm:w-auto sm:text-sm cursor-pointer">
                    <x-ui.icon name="bolt" class="size-4"/>
                    <span>{{ $resolvedPrimaryLabel }}</span>
                </button>
            @else
                <a href="{{ $resolvedPrimaryHref }}"
                   class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-primary px-6 py-2.5 text-xs font-bold text-white shadow-lg shadow-primary/25 transition hover:bg-primary/90 active:scale-98 sm:w-auto sm:text-sm">
                    <x-ui.icon :name="$isAuthenticated && $primaryAction === 'home' ? 'hierarchy-2' : 'sparkles'" class="size-4"/>
                    <span>{{ $resolvedPrimaryLabel }}</span>
                </a>
            @endif

            @if ($secondaryAction === 'back')
                <button type="button"
                        onclick="if (window.history.length > 1) { window.history.back(); } else { window.location.href = '{{ $homeUrl }}'; }"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-line-border bg-surface-card px-5 py-2.5 text-xs font-bold text-content-main shadow-2xs transition hover:border-content-muted/40 hover:bg-surface-raised active:scale-98 sm:w-auto sm:text-sm cursor-pointer">
                    <x-ui.icon name="arrow-right" class="size-4 text-content-muted"/>
                    <span>{{ $resolvedSecondaryLabel }}</span>
                </button>
            @elseif ($secondaryAction === 'support')
                <a href="{{ $ticketsUrl }}"
                   class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-line-border bg-surface-card px-5 py-2.5 text-xs font-bold text-content-main shadow-2xs transition hover:border-primary/40 hover:text-primary active:scale-98 sm:w-auto sm:text-sm">
                    <x-ui.icon name="headset" class="size-4 text-primary"/>
                    <span>{{ $resolvedSecondaryLabel }}</span>
                </a>
            @elseif ($secondaryAction === 'home')
                <a href="{{ $dashboardUrl }}"
                   class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-line-border bg-surface-card px-5 py-2.5 text-xs font-bold text-content-main shadow-2xs transition hover:border-content-muted/40 hover:bg-surface-raised active:scale-98 sm:w-auto sm:text-sm">
                    <x-ui.icon name="arrow-right" class="size-4 text-content-muted"/>
                    <span>{{ $resolvedSecondaryLabel }}</span>
                </a>
            @endif
        </div>
    </div>
</main>

<footer class="mx-auto w-full max-w-3xl shrink-0 px-4 pb-5 pt-2 sm:px-6">
    <div class="flex flex-col items-center justify-between gap-2 rounded-2xl border border-line-border/70 bg-surface-raised/80 px-4 py-2.5 text-xs text-content-muted shadow-2xs backdrop-blur-md sm:flex-row">
        <div class="flex flex-wrap items-center justify-center gap-1 sm:gap-2">
            <a href="{{ $homeUrl }}" class="rounded-lg px-2.5 py-1 font-medium transition hover:bg-surface-card hover:text-primary">
                صفحه اصلی
            </a>
            <span class="text-line-border">•</span>
            <a href="{{ $docsUrl }}" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 font-medium transition hover:bg-surface-card hover:text-primary">
                <x-ui.icon name="file-text" class="size-3.5 text-primary"/>
                <span>مستندات و راهنما</span>
            </a>
            <span class="text-line-border">•</span>
            <a href="{{ $ticketsUrl }}" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 font-medium transition hover:bg-surface-card hover:text-primary">
                <x-ui.icon name="ticket" class="size-3.5 text-primary"/>
                <span>پشتیبانی و تیکت‌ها</span>
            </a>
        </div>

        <span dir="ltr" class="font-mono text-[11px] text-content-muted/80">
            Tameshk Flow • Status {{ $code }}
        </span>
    </div>
</footer>

<script>
    (() => {
        const img = document.getElementById('error-raw-img');
        if (!img) return;

        const stripBlackBackground = () => {
            if (img.dataset.processed === '1') return;
            img.dataset.processed = '1';

            try {
                const w = img.naturalWidth;
                const h = img.naturalHeight;
                if (!w || !h) {
                    img.classList.remove('opacity-0');
                    return;
                }

                const canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                const ctx = canvas.getContext('2d', { willReadFrequently: true });
                if (!ctx) {
                    img.classList.remove('opacity-0');
                    return;
                }

                ctx.drawImage(img, 0, 0);
                const imgData = ctx.getImageData(0, 0, w, h);
                const d = imgData.data;

                const low = 6;
                const high = 38;
                const range = high - low;

                let minX = w, minY = h, maxX = 0, maxY = 0;

                for (let y = 0; y < h; y++) {
                    const rowOffset = y * w * 4;
                    for (let x = 0; x < w; x++) {
                        const i = rowOffset + x * 4;
                        const r = d[i];
                        const g = d[i + 1];
                        const b = d[i + 2];
                        const maxC = r > g ? (r > b ? r : b) : (g > b ? g : b);

                        if (maxC <= low) {
                            d[i + 3] = 0;
                        } else {
                            if (maxC < high) {
                                const alpha = (maxC - low) / range;
                                d[i] = Math.min(255, Math.round(r / alpha));
                                d[i + 1] = Math.min(255, Math.round(g / alpha));
                                d[i + 2] = Math.min(255, Math.round(b / alpha));
                                d[i + 3] = Math.round(alpha * 255);
                            }

                            if (x < minX) minX = x;
                            if (x > maxX) maxX = x;
                            if (y < minY) minY = y;
                            if (y > maxY) maxY = y;
                        }
                    }
                }

                ctx.putImageData(imgData, 0, 0);

                if (maxX > minX && maxY > minY) {
                    const pad = 12;
                    const sx = Math.max(0, minX - pad);
                    const sy = Math.max(0, minY - pad);
                    const sw = Math.min(w - sx, (maxX - minX) + pad * 2);
                    const sh = Math.min(h - sy, (maxY - minY) + pad * 2);

                    const cropped = document.createElement('canvas');
                    cropped.width = sw;
                    cropped.height = sh;
                    cropped.getContext('2d').drawImage(canvas, sx, sy, sw, sh, 0, 0, sw, sh);
                    img.src = cropped.toDataURL('image/png');
                } else {
                    img.src = canvas.toDataURL('image/png');
                }
            } catch {
            } finally {
                img.classList.remove('opacity-0');
            }
        };

        if (img.complete && img.naturalWidth > 0) {
            stripBlackBackground();
        } else {
            img.addEventListener('load', stripBlackBackground, { once: true });
            img.addEventListener('error', () => img.classList.remove('opacity-0'), { once: true });
        }
    })();
</script>

@if ($autoRefresh)
    <script>
        (() => {
            const box = document.getElementById('auto-refresh-box');
            const counter = document.getElementById('refresh-countdown');
            if (!box || !counter) return;

            let remaining = parseInt(box.dataset.seconds || '30', 10);
            const timer = setInterval(() => {
                remaining -= 1;
                if (remaining <= 0) {
                    clearInterval(timer);
                    window.location.reload();
                } else {
                    counter.textContent = String(remaining);
                }
            }, 1000);
        })();
    </script>
@endif

</body>
</html>
