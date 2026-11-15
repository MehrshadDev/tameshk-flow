@props([
    'allSeries',
    'usersMetrics',
])

@php
    $width = 640;
    $height = 210;
    $padX = 24;
    $padTop = 18;
    $padBottom = 32;
    $usableW = $width - ($padX * 2);
    $usableH = $height - $padTop - $padBottom;

    $prepared = [];

    foreach (['7d', '30d', '12m'] as $rKey) {
        $series = $allSeries[$rKey] ?? ['points' => [], 'total_count' => 0, 'max_count' => 1];
        $points = $series['points'] ?? [];
        $count = count($points);
        $maxCount = max(1, (int) ($series['max_count'] ?? 1));
        $slotW = $count > 0 ? ($usableW / $count) : $usableW;
        $barW = max(6, min(28, round($slotW * 0.62)));

        $bars = [];
        foreach ($points as $i => $pt) {
            $centerX = $padX + ($slotW * $i) + ($slotW / 2);
            $val = (int) $pt['count'];
            $ratio = $maxCount > 0 ? ($val / $maxCount) : 0;
            $barH = $val > 0 ? max(8, round($ratio * $usableH, 1)) : 3;
            $y = round($padTop + $usableH - $barH, 1);

            $showTick = false;
            if ($count > 0) {
                $step = max(1, (int) floor($count / 5));
                $showTick = ($i % $step === 0) || ($i === $count - 1);
            }

            $bars[] = [
                'x' => round($centerX - ($barW / 2), 1),
                'center_x' => round($centerX, 1),
                'hit_x' => round($centerX - ($slotW / 2), 1),
                'hit_w' => round($slotW, 1),
                'y' => $y,
                'w' => $barW,
                'h' => $barH,
                'label' => $pt['label'],
                'short_label' => $pt['short_label'],
                'count' => $val,
                'google_count' => (int) ($pt['google_count'] ?? 0),
                'show_tick' => $showTick,
            ];
        }

        $prepared[$rKey] = [
            'bars' => $bars,
            'total_count' => number_format((int) ($series['total_count'] ?? 0)),
        ];
    }

    $totalUsers = max(1, (int) ($usersMetrics['total'] ?? 1));
    $googlePercent = (int) round(((int) ($usersMetrics['google_count'] ?? 0) / $totalUsers) * 100);
@endphp

<div x-data="{
         activeIdx: null,
         meta: {
             '7d': {
                 total_count: @js($prepared['7d']['total_count']),
                 bars: @js($prepared['7d']['bars'])
             },
             '30d': {
                 total_count: @js($prepared['30d']['total_count']),
                 bars: @js($prepared['30d']['bars'])
             },
             '12m': {
                 total_count: @js($prepared['12m']['total_count']),
                 bars: @js($prepared['12m']['bars'])
             }
         },
         get currentMeta() {
             return this.meta[this.chartRange] || this.meta['30d'];
         },
         get activeBar() {
             if (this.activeIdx === null) return null;
             return this.currentMeta.bars[this.activeIdx] || null;
         }
     }"
     class="flex flex-col justify-between rounded-2xl border border-line-border bg-surface-card p-4 sm:p-5 shadow-2xs">

    <div class="flex flex-col gap-3 border-b border-line-border/60 pb-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2.5">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-500">
                <x-ui.icon name="users" class="size-5"/>
            </span>
            <div>
                <h3 class="text-xs sm:text-sm font-extrabold text-content-main">روند ثبت‌نام کاربران جدید</h3>
                <p class="mt-0.5 text-[11px] text-content-muted">
                    ثبت‌نام در این بازه:
                    <strong class="font-mono font-bold text-sky-500"><span x-text="currentMeta.total_count"></span> کاربر</strong>
                    • گوگل OAuth: <strong class="font-mono text-content-main">%{{ $googlePercent }}</strong>
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 text-[11px]">
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-line-border/80 bg-surface-base px-2.5 py-1 text-content-muted">
                <span class="size-2 rounded-full bg-sky-500"></span>
                <span>گوگل: <strong class="font-mono text-content-main">{{ number_format($usersMetrics['google_count']) }}</strong></span>
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-lg border border-line-border/80 bg-surface-base px-2.5 py-1 text-content-muted">
                <span class="size-2 rounded-full bg-purple-500"></span>
                <span>ایمیل: <strong class="font-mono text-content-main">{{ number_format($usersMetrics['email_count']) }}</strong></span>
            </span>
        </div>
    </div>

    <div class="mt-3 flex h-8 items-center justify-between rounded-xl border border-line-border/60 bg-surface-base/70 px-3 text-[11px]">
        <template x-if="activeBar">
            <div class="flex w-full items-center justify-between gap-2">
                <span class="font-medium text-content-muted">
                    تاریخ: <strong dir="ltr" class="inline-block font-mono text-content-main" x-text="activeBar.label"></strong>
                </span>
                <div class="flex items-center gap-2">
                    <span class="font-mono font-extrabold text-sky-500">
                        <span x-text="activeBar.count"></span> ثبت‌نام
                    </span>
                    <span class="rounded-md bg-sky-500/15 px-1.5 py-0.5 font-mono text-[10px] font-bold text-sky-500"
                          x-text="activeBar.google_count + ' با گوگل'"></span>
                </div>
            </div>
        </template>
        <template x-if="!activeBar">
            <span class="text-content-muted">برای مشاهده آمار دقیق ثبت‌نام، روی ستون‌های نمودار بروید</span>
        </template>
    </div>

    <div class="relative mt-2" dir="ltr">
        @foreach ($prepared as $rKey => $dataset)
            <div x-show="chartRange === '{{ $rKey }}'" @if ($rKey !== '30d') x-cloak @endif class="w-full overflow-hidden">
                <svg viewBox="0 0 {{ $width }} {{ $height }}" class="h-44 sm:h-52 w-full overflow-visible select-none">
                    <defs>
                        <linearGradient id="adminUsersBarGrad_{{ $rKey }}" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#0ea5e9" stop-opacity="0.95"/>
                            <stop offset="100%" stop-color="#6366f1" stop-opacity="0.65"/>
                        </linearGradient>
                    </defs>

                    @for ($g = 0; $g <= 3; $g++)
                        @php
                            $gy = round($padTop + (($usableH / 3) * $g), 1);
                        @endphp
                        <line x1="{{ $padX }}" y1="{{ $gy }}" x2="{{ $width - $padX }}" y2="{{ $gy }}"
                              class="stroke-line-border/60" stroke-dasharray="4 4" stroke-width="1"/>
                    @endfor

                    @foreach ($dataset['bars'] as $idx => $b)
                        <g class="cursor-pointer"
                           @mouseenter="activeIdx = {{ $idx }}"
                           @touchstart.passive="activeIdx = {{ $idx }}"
                           @mouseleave="activeIdx = null">
                            <rect x="{{ $b['hit_x'] }}"
                                  y="{{ $padTop }}"
                                  width="{{ $b['hit_w'] }}"
                                  height="{{ $usableH }}"
                                  fill="transparent"/>

                            <rect x="{{ $b['x'] }}"
                                  y="{{ $b['y'] }}"
                                  width="{{ $b['w'] }}"
                                  height="{{ $b['h'] }}"
                                  rx="4"
                                  fill="{{ $b['count'] > 0 ? "url(#adminUsersBarGrad_$rKey)" : 'currentColor' }}"
                                  :class="activeIdx === {{ $idx }} ? 'opacity-100 brightness-110' : '{{ $b['count'] > 0 ? 'opacity-85' : 'text-line-border opacity-60' }}'"
                                  class="transition-opacity duration-150"/>

                            @if ($b['show_tick'])
                                <text x="{{ $b['center_x'] }}"
                                      y="{{ $height - 6 }}"
                                      text-anchor="middle"
                                      class="fill-content-muted font-mono text-[11px]">
                                    {{ $b['short_label'] }}
                                </text>
                            @endif
                        </g>
                    @endforeach
                </svg>
            </div>
        @endforeach
    </div>
</div>
