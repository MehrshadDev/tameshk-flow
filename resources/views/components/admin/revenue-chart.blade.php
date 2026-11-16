@props([
    'allSeries',
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
        $series = $allSeries[$rKey] ?? ['points' => [], 'total_amount' => 0, 'total_count' => 0, 'max_amount' => 1];
        $points = $series['points'] ?? [];
        $count = count($points);
        $maxAmount = max(1, (int) ($series['max_amount'] ?? 1));

        $coords = [];
        foreach ($points as $i => $pt) {
            $x = $count > 1 ? $padX + (($usableW / ($count - 1)) * $i) : $width / 2;
            $ratio = $maxAmount > 0 ? ((int) $pt['amount']) / $maxAmount : 0;
            $y = $padTop + $usableH - ($ratio * $usableH);

            $showTick = false;
            if ($count > 0) {
                $step = max(1, (int) floor($count / 5));
                $showTick = ($i % $step === 0) || ($i === $count - 1);
            }

            $coords[] = [
                'x' => round($x, 1),
                'y' => round($y, 1),
                'hit_x' => round($x - ($usableW / max(2, $count) / 2), 1),
                'hit_w' => round(max(14, $usableW / max(1, $count)), 1),
                'label' => $pt['label'],
                'short_label' => $pt['short_label'],
                'amount' => (int) $pt['amount'],
                'formatted_amount' => number_format((int) $pt['amount']),
                'count' => (int) $pt['count'],
                'show_tick' => $showTick,
            ];
        }

        $linePath = '';
        $areaPath = '';

        if ($coords !== []) {
            $first = $coords[0];
            $last = $coords[count($coords) - 1];
            $baselineY = $padTop + $usableH;
            $segments = array_map(fn ($c) => "{$c['x']},{$c['y']}", $coords);
            $linePath = 'M ' . implode(' L ', $segments);
            $areaPath = $linePath . " L {$last['x']},$baselineY L {$first['x']},$baselineY Z";
        }

        $prepared[$rKey] = [
            'coords' => $coords,
            'linePath' => $linePath,
            'areaPath' => $areaPath,
            'total_amount' => number_format((int) ($series['total_amount'] ?? 0)),
            'total_count' => number_format((int) ($series['total_count'] ?? 0)),
        ];
    }
@endphp

<div x-data="{
         activeIdx: null,
         meta: {
             '7d': {
                 total_amount: @js($prepared['7d']['total_amount']),
                 total_count: @js($prepared['7d']['total_count']),
                 points: @js($prepared['7d']['coords'])
             },
             '30d': {
                 total_amount: @js($prepared['30d']['total_amount']),
                 total_count: @js($prepared['30d']['total_count']),
                 points: @js($prepared['30d']['coords'])
             },
             '12m': {
                 total_amount: @js($prepared['12m']['total_amount']),
                 total_count: @js($prepared['12m']['total_count']),
                 points: @js($prepared['12m']['coords'])
             }
         },
         get currentMeta() {
             return this.meta[this.chartRange] || this.meta['30d'];
         },
         get activePoint() {
             if (this.activeIdx === null) return null;
             return this.currentMeta.points[this.activeIdx] || null;
         }
     }"
     class="flex flex-col justify-between rounded-2xl border border-line-border bg-surface-card p-4 sm:p-5 shadow-2xs">

    <div class="flex flex-col gap-3 border-b border-line-border/60 pb-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-2.5">
            <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-500">
                <x-ui.icon name="chart-area-line" class="size-5"/>
            </span>
            <div>
                <h3 class="text-xs sm:text-sm font-extrabold text-content-main">نمودار روند درآمد و خریدها</h3>
                <p class="mt-0.5 text-[11px] text-content-muted">
                    مجموع دوره:
                    <strong class="font-mono font-bold text-emerald-500"><span x-text="currentMeta.total_amount"></span> تومان</strong>
                    (<span x-text="currentMeta.total_count"></span> خرید موفق)
                </p>
            </div>
        </div>

        <x-ui.segmented
            alpine="chartRange"
            @click="activeIdx = null"
            cols="3"
            size="sm"
            class="w-full sm:w-auto"
            :options="[
                '7d' => '۷ روز',
                '30d' => '۳۰ روز',
                '12m' => '۱۲ ماه شمسی',
            ]"
        />
    </div>

    <div class="mt-3 flex h-8 items-center justify-between rounded-xl border border-line-border/60 bg-surface-base/70 px-3 text-[11px]">
        <template x-if="activePoint">
            <div class="flex w-full items-center justify-between gap-2">
                <span class="font-medium text-content-muted">
                    تاریخ: <strong dir="ltr" class="inline-block font-mono text-content-main" x-text="activePoint.label"></strong>
                </span>
                <div class="flex items-center gap-2">
                    <span class="font-mono font-extrabold text-emerald-500">
                        <span x-text="activePoint.formatted_amount"></span> تومان
                    </span>
                    <span class="rounded-md bg-emerald-500/15 px-1.5 py-0.5 font-mono text-[10px] font-bold text-emerald-500"
                          x-text="activePoint.count + ' خرید'"></span>
                </div>
            </div>
        </template>
        <template x-if="!activePoint">
            <span class="text-content-muted">برای مشاهده جزئیات هر روز یا ماه، نشانگر را روی نقاط نمودار ببرید</span>
        </template>
    </div>

    <div class="relative mt-2" dir="ltr">
        @foreach ($prepared as $rKey => $dataset)
            <div x-show="chartRange === '{{ $rKey }}'" @if ($rKey !== '30d') x-cloak @endif class="w-full overflow-hidden">
                <svg viewBox="0 0 {{ $width }} {{ $height }}" class="h-44 sm:h-52 w-full overflow-visible select-none">
                    <defs>
                        <linearGradient id="adminRevenueGrad_{{ $rKey }}" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#10b981" stop-opacity="0.32"/>
                            <stop offset="100%" stop-color="#10b981" stop-opacity="0.0"/>
                        </linearGradient>
                    </defs>

                    @for ($g = 0; $g <= 3; $g++)
                        @php
                            $gy = round($padTop + (($usableH / 3) * $g), 1);
                        @endphp
                        <line x1="{{ $padX }}" y1="{{ $gy }}" x2="{{ $width - $padX }}" y2="{{ $gy }}"
                              class="stroke-line-border/60" stroke-dasharray="4 4" stroke-width="1"/>
                    @endfor

                    @if ($dataset['areaPath'] !== '')
                        <path d="{{ $dataset['areaPath'] }}" fill="url(#adminRevenueGrad_{{ $rKey }})"/>
                        <path d="{{ $dataset['linePath'] }}" fill="none" stroke="#10b981" stroke-width="2.75" stroke-linecap="round" stroke-linejoin="round"/>
                    @endif

                    @foreach ($dataset['coords'] as $idx => $c)
                        <g class="cursor-pointer"
                           @mouseenter="activeIdx = {{ $idx }}"
                           @touchstart.passive="activeIdx = {{ $idx }}"
                           @mouseleave="activeIdx = null">
                            <rect x="{{ $c['hit_x'] }}"
                                  y="{{ $padTop }}"
                                  width="{{ $c['hit_w'] }}"
                                  height="{{ $usableH }}"
                                  fill="transparent"/>

                            <line x-show="activeIdx === {{ $idx }}"
                                  x1="{{ $c['x'] }}" y1="{{ $padTop }}"
                                  x2="{{ $c['x'] }}" y2="{{ $padTop + $usableH }}"
                                  stroke="#10b981" stroke-width="1.2" stroke-dasharray="3 3"/>

                            <circle cx="{{ $c['x'] }}" cy="{{ $c['y'] }}"
                                    r="{{ $c['amount'] > 0 ? '4' : '2.2' }}"
                                    :class="activeIdx === {{ $idx }} && 'scale-125'"
                                    class="fill-emerald-500 stroke-surface-card transition-transform duration-150"
                                    style="transform-box: fill-box; transform-origin: center;"
                                    stroke-width="2"/>

                            @if ($c['show_tick'])
                                <text x="{{ $c['x'] }}"
                                      y="{{ $height - 6 }}"
                                      text-anchor="middle"
                                      class="fill-content-muted font-mono text-[11px]">
                                    {{ $c['short_label'] }}
                                </text>
                            @endif
                        </g>
                    @endforeach
                </svg>
            </div>
        @endforeach
    </div>
</div>
