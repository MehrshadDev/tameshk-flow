@props([
    'template',
    'definitions' => [],
])

@php
    use Illuminate\Support\Str;

    $paletteStyles = [
        'emerald' => 'bg-emerald-500/15 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-400 border-emerald-500/30',
        'teal'    => 'bg-teal-500/15 text-teal-600 dark:bg-teal-500/15 dark:text-teal-400 border-teal-500/30',
        'cyan'    => 'bg-cyan-500/15 text-cyan-600 dark:bg-cyan-500/15 dark:text-cyan-400 border-cyan-500/30',
        'sky'     => 'bg-sky-500/15 text-sky-600 dark:bg-sky-500/15 dark:text-sky-400 border-sky-500/30',
        'blue'    => 'bg-blue-500/15 text-blue-600 dark:bg-blue-500/15 dark:text-blue-400 border-blue-500/30',
        'indigo'  => 'bg-indigo-500/15 text-indigo-600 dark:bg-indigo-500/15 dark:text-indigo-400 border-indigo-500/30',
        'violet'  => 'bg-violet-500/15 text-violet-600 dark:bg-violet-500/15 dark:text-violet-400 border-violet-500/30',
        'fuchsia' => 'bg-fuchsia-500/15 text-fuchsia-600 dark:bg-fuchsia-500/15 dark:text-fuchsia-400 border-fuchsia-500/30',
        'pink'    => 'bg-pink-500/15 text-pink-600 dark:bg-pink-500/15 dark:text-pink-400 border-pink-500/30',
        'rose'    => 'bg-rose-500/15 text-rose-600 dark:bg-rose-500/15 dark:text-rose-400 border-rose-500/30',
        'orange'  => 'bg-orange-500/15 text-orange-600 dark:bg-orange-500/15 dark:text-orange-400 border-orange-500/30',
        'amber'   => 'bg-amber-500/15 text-amber-600 dark:bg-amber-500/15 dark:text-amber-400 border-amber-500/30',
        'yellow'  => 'bg-yellow-500/15 text-yellow-600 dark:bg-yellow-500/15 dark:text-yellow-400 border-yellow-500/30',
        'lime'    => 'bg-lime-500/15 text-lime-600 dark:bg-lime-500/15 dark:text-lime-400 border-lime-500/30',
    ];

    $specialHandles = ['chat_model', 'memory', 'tool'];

    $flowNodes = [];
    $supplyNodes = [];

    foreach ($template->nodes as $node) {
        $type = (string) ($node['type'] ?? '');
        if ($type === 'sticky_note') {
            continue;
        }

        $key = (string) ($node['key'] ?? '');
        $def = $definitions[$type] ?? null;

        $isSupply = $def !== null
            && $def->inputs === []
            && $def->outputs !== []
            && count(array_diff($def->outputs, $specialHandles)) === 0;

        $meta = [
            'key' => $key,
            'type' => $type,
            'name' => (string) ($node['name'] ?? ($def?->name ?? $type)),
            'icon' => $def?->icon ?? 'box',
            'isTrigger' => str_starts_with($type, 'trigger.'),
            'style' => $paletteStyles[$def?->color ?? ''] ?? 'bg-primary/15 text-primary border-primary/30',
            'supplies' => [],
        ];

        if ($isSupply) {
            $supplyNodes[$key] = $meta;
        } else {
            $flowNodes[$key] = $meta;
        }
    }

    $outgoing = [];
    $incomingCount = array_fill_keys(array_keys($flowNodes), 0);
    $outgoingCount = array_fill_keys(array_keys($flowNodes), 0);
    $inDegree = array_fill_keys(array_keys($flowNodes), 0);
    $flowEdges = [];

    foreach ($template->edges as $edge) {
        $src = (string) ($edge['source'] ?? '');
        $tgt = (string) ($edge['target'] ?? '');

        if (isset($supplyNodes[$src], $flowNodes[$tgt])) {
            $flowNodes[$tgt]['supplies'][] = $supplyNodes[$src];
            continue;
        }

        if (isset($flowNodes[$src], $flowNodes[$tgt])) {
            $outgoing[$src][] = $tgt;
            $inDegree[$tgt] = ($inDegree[$tgt] ?? 0) + 1;
            $incomingCount[$tgt] = ($incomingCount[$tgt] ?? 0) + 1;
            $outgoingCount[$src] = ($outgoingCount[$src] ?? 0) + 1;
            $flowEdges[] = ['source' => $src, 'target' => $tgt];
        }
    }

    $layers = [];
    $depthMap = [];
    $queue = [];

    foreach ($inDegree as $k => $deg) {
        if ($deg === 0) {
            $depthMap[$k] = 0;
            $queue[] = $k;
        }
    }

    if ($queue === [] && $flowNodes !== []) {
        $firstKey = array_key_first($flowNodes);
        $depthMap[$firstKey] = 0;
        $queue[] = $firstKey;
    }

    $safety = 0;
    while ($queue !== [] && ++$safety < 200) {
        $curr = array_shift($queue);
        $currDepth = $depthMap[$curr] ?? 0;

        foreach ($outgoing[$curr] ?? [] as $next) {
            $depthMap[$next] = max($depthMap[$next] ?? 0, $currDepth + 1);
            $inDegree[$next]--;

            if ($inDegree[$next] <= 0) {
                $queue[] = $next;
            }
        }
    }

    foreach ($flowNodes as $k => $nodeMeta) {
        $layerIdx = $depthMap[$k] ?? 0;
        $layers[$layerIdx][] = $nodeMeta;
    }

    ksort($layers);
    $layers = array_values($layers);

    $nodeW = 212;
    $nodeH = 68;
    $colGap = 56;
    $rowGap = 20;
    $supplyExtraH = 52;
    $padX = 22;
    $padY = 26;

    $colFlowHeights = [];
    foreach ($layers as $li => $layerNodes) {
        $count = count($layerNodes);
        $colFlowHeights[$li] = ($count * $nodeH) + (max(0, $count - 1) * $rowGap);
    }

    $maxFlowH = $colFlowHeights !== [] ? max($colFlowHeights) : $nodeH;
    $canvasW = ($padX * 2) + (count($layers) * $nodeW) + (max(0, count($layers) - 1) * $colGap);

    $positions = [];
    $maxBottomY = $padY + $maxFlowH;

    foreach ($layers as $li => $layerNodes) {
        $x = $padX + ($li * ($nodeW + $colGap));
        $y = $padY + (int) round(($maxFlowH - $colFlowHeights[$li]) / 2);

        foreach ($layerNodes as $n) {
            $positions[$n['key']] = [
                'x' => $x,
                'y' => $y,
                'w' => $nodeW,
                'h' => $nodeH,
            ];

            $nodeBottom = $y + $nodeH + ($n['supplies'] !== [] ? $supplyExtraH : 0);
            if ($nodeBottom > $maxBottomY) {
                $maxBottomY = $nodeBottom;
            }

            $y += $nodeH + ($n['supplies'] !== [] ? $supplyExtraH : 0) + $rowGap;
        }
    }

    $canvasH = $maxBottomY + $padY;

    $svgPaths = [];
    foreach ($flowEdges as $edge) {
        $srcPos = $positions[$edge['source']] ?? null;
        $tgtPos = $positions[$edge['target']] ?? null;

        if (! $srcPos || ! $tgtPos) {
            continue;
        }

        $x1 = $srcPos['x'] + $srcPos['w'] + 5;
        $y1 = $srcPos['y'] + (int) round($srcPos['h'] / 2);
        $x2 = $tgtPos['x'] - 13.5;
        $y2 = $tgtPos['y'] + (int) round($tgtPos['h'] / 2);

        if ($x2 > $x1 + 12) {
            $dx = max(22, (int) round(($x2 - $x1) * 0.5));
            $svgPaths[] = "M {$x1} {$y1} C " . ($x1 + $dx) . " {$y1}, " . ($x2 - $dx) . " {$y2}, {$x2} {$y2}";
        } else {
            $loopY = max($y1, $y2) + 52;
            $canvasH = max($canvasH, $loopY + 24);
            $midX = (int) round(($x1 + $x2) / 2);
            $svgPaths[] = "M {$x1} {$y1} C " . ($x1 + 32) . " {$y1}, " . ($x1 + 32) . " {$loopY}, {$midX} {$loopY} S " . ($x2 - 32) . " {$y2}, {$x2} {$y2}";
        }
    }

    $uid = Str::slug($template->slug ?: 'flow') . '-' . substr(md5($template->slug), 0, 6);
@endphp

<section aria-labelledby="flow-preview-title-{{ $uid }}"
         class="overflow-hidden rounded-3xl border border-line-border bg-surface-card shadow-2xs">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line-border bg-surface-raised px-4 py-3.5 sm:px-5">
        <div class="flex items-center gap-2.5">
            <span class="flex size-8 items-center justify-center rounded-xl bg-primary/12 text-primary">
                <x-ui.icon name="hierarchy" class="size-4.5"/>
            </span>
            <h2 id="flow-preview-title-{{ $uid }}" class="text-xs font-extrabold text-content-main sm:text-sm">
                ساختار اجرا روی بوم تمشک فلو
            </h2>
        </div>

        <span class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-500/30 bg-emerald-500/12 px-3 py-1 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
            <span class="size-1.5 rounded-full bg-emerald-500"></span>
            <span>{{ $template->nodeCount() }} نود متصل</span>
        </span>
    </div>

    <div class="template-canvas-dots relative bg-surface-base">
        <div x-data="{
                 canvasW: {{ $canvasW }},
                 canvasH: {{ $canvasH }},
                 minScale: 0.76,
                 scale: 1,
                 canPan: false,
                 isPanning: false,
                 startX: 0,
                 scrollLeft: 0,
                 updateScale() {
                     const available = this.$refs.viewport?.clientWidth || this.canvasW;
                     const raw = available < this.canvasW ? available / this.canvasW : 1;
                     this.scale = Number(Math.max(this.minScale, Math.min(1, raw)).toFixed(4));
                     this.canPan = Math.round(this.canvasW * this.scale) > available + 4;
                 },
                 startPan(e) {
                     if (!this.canPan) return;
                     this.isPanning = true;
                     this.startX = e.pageX;
                     this.scrollLeft = this.$refs.viewport.scrollLeft;
                 },
                 movePan(e) {
                     if (!this.isPanning) return;
                     this.$refs.viewport.scrollLeft = this.scrollLeft - (e.pageX - this.startX);
                 },
                 endPan() {
                     this.isPanning = false;
                 }
             }"
             x-init="
                 updateScale();
                 if (typeof ResizeObserver !== 'undefined') {
                     const ro = new ResizeObserver(() => updateScale());
                     ro.observe($refs.viewport);
                 } else {
                     window.addEventListener('resize', () => updateScale(), { passive: true });
                 }
             "
             x-ref="viewport"
             dir="ltr"
             @mousedown="startPan($event)"
             @mousemove="movePan($event)"
             @mouseup="endPan()"
             @mouseleave="endPan()"
             :class="canPan ? (isPanning ? 'cursor-grabbing select-none' : 'cursor-grab') : ''"
             class="custom-scrollbar hidden w-full overflow-x-auto overflow-y-hidden sm:block">
            <div class="relative mx-auto overflow-hidden"
                 :style="`width: ${Math.round(canvasW * scale)}px; height: ${Math.round(canvasH * scale)}px;`">
                <div dir="ltr"
                     class="absolute top-0 left-0 origin-top-left"
                     :style="`width: ${canvasW}px; height: ${canvasH}px; transform: scale(${scale});`">
                    <svg aria-hidden="true"
                         width="{{ $canvasW }}"
                         height="{{ $canvasH }}"
                         viewBox="0 0 {{ $canvasW }} {{ $canvasH }}"
                         class="pointer-events-none absolute inset-0 text-edge">
                        <defs>
                            <marker id="fp-arrow-{{ $uid }}"
                                    markerUnits="userSpaceOnUse"
                                    viewBox="0 0 10 10"
                                    refX="1"
                                    refY="5"
                                    markerWidth="10"
                                    markerHeight="10"
                                    orient="auto">
                                <path d="M 0 1.5 L 8.5 5 L 0 8.5 z" fill="currentColor"/>
                            </marker>
                        </defs>

                        @foreach ($svgPaths as $path)
                            <path d="{{ $path }}"
                                  fill="none"
                                  stroke="currentColor"
                                  stroke-width="2"
                                  stroke-linecap="butt"
                                  marker-end="url(#fp-arrow-{{ $uid }})"/>
                        @endforeach
                    </svg>

                    <ol aria-label="مراحل اجرای ورکفلو" class="contents">
                        @foreach ($layers as $layerNodes)
                            @foreach ($layerNodes as $node)
                                @php
                                    $pos = $positions[$node['key']];
                                    $hasIn = ($incomingCount[$node['key']] ?? 0) > 0;
                                    $hasOut = ($outgoingCount[$node['key']] ?? 0) > 0;
                                @endphp

                                <li class="absolute list-none"
                                    style="left: {{ $pos['x'] }}px; top: {{ $pos['y'] }}px; width: {{ $pos['w'] }}px;">
                                    <div dir="rtl"
                                         class="relative flex h-[68px] items-center gap-2.5 rounded-2xl border border-content-muted/25 bg-surface-raised px-3 shadow-md shadow-black/10 transition hover:border-primary/60 dark:border-white/15 dark:shadow-black/45">
                                        @if ($node['isTrigger'])
                                            <span title="شروع‌کننده ورکفلو"
                                                  class="absolute -top-2 right-3 inline-flex items-center gap-0.5 rounded-full border border-amber-500/35 bg-surface-raised px-2 py-0.5 text-[9px] font-extrabold text-amber-600 shadow-2xs dark:text-amber-400">
                                                <x-ui.icon name="bolt" class="size-2.5"/>
                                                <span>شروع</span>
                                            </span>
                                        @endif

                                        @if ($hasIn)
                                            <span aria-hidden="true"
                                                  class="absolute -left-1.5 top-1/2 size-3 -translate-y-1/2 rounded-full border-2 border-surface-raised bg-content-muted"></span>
                                        @endif

                                        @if ($hasOut)
                                            <span aria-hidden="true"
                                                  class="absolute -right-1.5 top-1/2 size-3 -translate-y-1/2 rounded-full border-2 border-surface-raised bg-primary"></span>
                                        @endif

                                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl border {{ $node['style'] }}">
                                            <x-ui.icon :name="$node['icon']" class="size-5"/>
                                        </div>

                                        <div class="min-w-0 flex-1 text-right">
                                            <p class="line-clamp-2 text-xs font-extrabold leading-5 text-content-main">
                                                {{ $node['name'] }}
                                            </p>
                                        </div>
                                    </div>

                                    @if ($node['supplies'] !== [])
                                        <div class="-mx-10 flex flex-col items-center">
                                            <span aria-hidden="true" class="h-3.5 w-px border-r-2 border-dashed border-purple-500/55"></span>
                                            <div dir="rtl" class="flex items-center justify-center gap-1.5">
                                                @foreach ($node['supplies'] as $supply)
                                                    <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-xl border border-purple-500/35 bg-surface-raised px-2.5 py-1 text-[11px] font-bold text-content-main shadow-2xs">
                                                        <x-ui.icon :name="$supply['icon']" class="size-3.5 shrink-0 text-purple-500"/>
                                                        <span>{{ $supply['name'] }}</span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        @endforeach
                    </ol>
                </div>
            </div>
        </div>

        <ol aria-label="مراحل اجرای ورکفلو" class="space-y-3 p-4 sm:hidden">
            @foreach ($layers as $layerIndex => $layerNodes)
                @if ($layerIndex > 0)
                    <li aria-hidden="true" class="flex flex-col items-center">
                        <span class="h-3.5 w-0.5 bg-content-muted/40"></span>
                        <span class="flex size-6 items-center justify-center rounded-full border border-content-muted/30 bg-surface-raised text-primary">
                            <x-ui.icon name="chevron-down" class="size-3.5"/>
                        </span>
                        <span class="h-3.5 w-0.5 bg-content-muted/40"></span>
                    </li>
                @endif

                <li class="space-y-2.5">
                    @if (count($layerNodes) > 1)
                        <p class="text-center text-[11px] font-bold text-content-muted">
                            اجرای هم‌زمان ({{ count($layerNodes) }} مسیر)
                        </p>
                    @endif

                    @foreach ($layerNodes as $node)
                        <div class="rounded-2xl border border-content-muted/25 bg-surface-raised p-3.5 shadow-sm dark:border-white/15">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl border {{ $node['style'] }}">
                                        <x-ui.icon :name="$node['icon']" class="size-5"/>
                                    </div>

                                    <p class="text-xs font-extrabold leading-5 text-content-main">
                                        {{ $node['name'] }}
                                    </p>
                                </div>

                                @if ($node['isTrigger'])
                                    <span class="inline-flex shrink-0 items-center gap-0.5 rounded-full bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400">
                                        <x-ui.icon name="bolt" class="size-2.5"/>
                                        <span>شروع</span>
                                    </span>
                                @endif
                            </div>

                            @if ($node['supplies'] !== [])
                                <div class="mt-3 flex flex-wrap items-center gap-1.5 border-t border-line-border/70 pt-2.5">
                                    @foreach ($node['supplies'] as $supply)
                                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-purple-500/30 bg-surface-base px-2 py-1 text-[11px] font-bold text-content-main">
                                            <x-ui.icon :name="$supply['icon']" class="size-3.5 text-purple-500"/>
                                            <span>{{ $supply['name'] }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </li>
            @endforeach
        </ol>
    </div>
</section>

@once
    <style>
        .template-canvas-dots {
            background-image: radial-gradient(color-mix(in oklab, var(--color-content-muted) 26%, transparent) 1.25px, transparent 1.25px);
            background-size: 20px 20px;
        }
    </style>
@endonce
