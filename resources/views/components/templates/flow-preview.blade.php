@props([
    'template',
    'definitions' => [],
])

@php
    $paletteStyles = [
        'emerald' => 'bg-emerald-500/15 text-emerald-600 dark:bg-emerald-500/12 dark:text-emerald-400 border-emerald-500/25',
        'teal'    => 'bg-teal-500/15 text-teal-600 dark:bg-teal-500/12 dark:text-teal-400 border-teal-500/25',
        'cyan'    => 'bg-cyan-500/15 text-cyan-600 dark:bg-cyan-500/12 dark:text-cyan-400 border-cyan-500/25',
        'sky'     => 'bg-sky-500/15 text-sky-600 dark:bg-sky-500/12 dark:text-sky-400 border-sky-500/25',
        'blue'    => 'bg-blue-500/15 text-blue-600 dark:bg-blue-500/12 dark:text-blue-400 border-blue-500/25',
        'indigo'  => 'bg-indigo-500/15 text-indigo-600 dark:bg-indigo-500/12 dark:text-indigo-400 border-indigo-500/25',
        'violet'  => 'bg-violet-500/15 text-violet-600 dark:bg-violet-500/12 dark:text-violet-400 border-violet-500/25',
        'fuchsia' => 'bg-fuchsia-500/15 text-fuchsia-600 dark:bg-fuchsia-500/12 dark:text-fuchsia-400 border-fuchsia-500/25',
        'pink'    => 'bg-pink-500/15 text-pink-600 dark:bg-pink-500/12 dark:text-pink-400 border-pink-500/25',
        'rose'    => 'bg-rose-500/15 text-rose-600 dark:bg-rose-500/12 dark:text-rose-400 border-rose-500/25',
        'orange'  => 'bg-orange-500/15 text-orange-600 dark:bg-orange-500/12 dark:text-orange-400 border-orange-500/25',
        'amber'   => 'bg-amber-500/15 text-amber-600 dark:bg-amber-500/12 dark:text-amber-400 border-amber-500/25',
        'yellow'  => 'bg-yellow-500/15 text-yellow-600 dark:bg-yellow-500/12 dark:text-yellow-400 border-yellow-500/25',
        'lime'    => 'bg-lime-500/15 text-lime-600 dark:bg-lime-500/12 dark:text-lime-400 border-lime-500/25',
    ];

    $specialHandles = ['chat_model', 'memory', 'tool'];

    $nodesByKey = [];
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
            'defName' => (string) ($def?->name ?? $type),
            'icon' => $def?->icon ?? 'box',
            'isTrigger' => str_starts_with($type, 'trigger.'),
            'style' => $paletteStyles[$def?->color ?? ''] ?? 'bg-primary/12 text-primary border-primary/25',
            'supplies' => [],
        ];

        $nodesByKey[$key] = $meta;

        if ($isSupply) {
            $supplyNodes[$key] = $meta;
        } else {
            $flowNodes[$key] = $meta;
        }
    }

    $outgoing = [];
    $inDegree = array_fill_keys(array_keys($flowNodes), 0);

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
@endphp

<div class="overflow-hidden rounded-3xl border border-line-border bg-surface-card shadow-2xs">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-line-border/70 bg-surface-raised/70 px-4 py-3 sm:px-5">
        <div class="flex items-center gap-2">
            <span class="flex size-7 items-center justify-center rounded-lg bg-primary/10 text-primary">
                <x-ui.icon name="hierarchy" class="size-4"/>
            </span>
            <span class="text-xs font-extrabold text-content-main">ساختار اجرا روی بوم تمشک فلو</span>
        </div>

        <span class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-500/25 bg-emerald-500/10 px-2.5 py-0.5 text-[11px] font-bold text-emerald-600 dark:text-emerald-400">
            <span class="size-1.5 rounded-full bg-emerald-500"></span>
            <span>{{ $template->nodeCount() }} نود متصل و آماده</span>
        </span>
    </div>

    <div class="template-canvas-dots relative overflow-x-auto px-4 py-7 sm:px-8 sm:py-9">
        <div dir="ltr" class="mx-auto flex w-fit flex-col items-center gap-3 sm:flex-row sm:items-center sm:gap-0">
            @foreach ($layers as $layerIndex => $layerNodes)
                @if ($layerIndex > 0)
                    <div class="flex shrink-0 items-center justify-center sm:px-3" aria-hidden="true">
                        <div class="flex flex-col items-center sm:hidden">
                            <span class="h-4 w-0.5 bg-line-border"></span>
                            <span class="flex size-6 items-center justify-center rounded-full border border-line-border bg-surface-raised text-primary shadow-2xs">
                                <x-ui.icon name="chevron-down" class="size-3.5"/>
                            </span>
                            <span class="h-4 w-0.5 bg-line-border"></span>
                        </div>

                        <div class="hidden items-center sm:flex">
                            <span class="h-0.5 w-6 bg-line-border"></span>
                            <span class="flex size-6 items-center justify-center rounded-full border border-line-border bg-surface-raised text-primary shadow-2xs">
                                <x-ui.icon name="chevron-right" class="size-3.5"/>
                            </span>
                            <span class="h-0.5 w-6 bg-line-border"></span>
                        </div>
                    </div>
                @endif

                <div class="flex flex-col items-center gap-3">
                    @foreach ($layerNodes as $node)
                        <div class="w-60 sm:w-56">
                            <div class="relative flex items-center gap-3 rounded-2xl border border-line-border bg-surface-card p-3 shadow-sm transition hover:border-primary/40">
                                <span class="absolute -top-1.5 -right-1.5 flex size-4.5 items-center justify-center rounded-full bg-emerald-500 text-white shadow-xs ring-2 ring-surface-card">
                                    <x-ui.icon name="check" class="size-2.5"/>
                                </span>

                                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl border {{ $node['style'] }}">
                                    <x-ui.icon :name="$node['icon']" class="size-5"/>
                                </div>

                                <div class="min-w-0 flex-1 text-left">
                                    <p dir="auto" class="truncate text-xs font-extrabold text-content-main">
                                        {{ $node['name'] }}
                                    </p>
                                    <div class="mt-0.5 flex items-center gap-1.5">
                                        @if ($node['isTrigger'])
                                            <x-ui.icon name="bolt" class="size-3 shrink-0 text-amber-500"/>
                                        @endif
                                        <span class="truncate font-mono text-[10px] text-content-muted">
                                            {{ $node['defName'] }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            @if ($node['supplies'] !== [])
                                <div class="mt-2 flex flex-wrap items-center justify-center gap-1.5">
                                    @foreach ($node['supplies'] as $supply)
                                        <span class="inline-flex items-center gap-1.5 rounded-lg border border-line-border bg-surface-raised px-2 py-1 text-[10px] font-bold text-content-muted shadow-2xs">
                                            <x-ui.icon :name="$supply['icon']" class="size-3.5"/>
                                            <span class="truncate">{{ $supply['name'] }}</span>
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>

@once
    <style>
        .template-canvas-dots {
            background-image: radial-gradient(color-mix(in oklab, var(--color-content-muted) 22%, transparent) 1.25px, transparent 1.25px);
            background-size: 18px 18px;
        }
    </style>
@endonce
