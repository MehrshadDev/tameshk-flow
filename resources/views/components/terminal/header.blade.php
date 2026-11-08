@props([
    'systemInfo' => [],
])

<header x-show="!isExpanded"
        class="flex shrink-0 flex-col gap-2.5 rounded-2xl border border-white/10 bg-white/3 px-3.5 py-2.5 shadow-lg backdrop-blur-2xl sm:flex-row sm:items-center sm:justify-between sm:px-4">
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-xl border border-primary/30 bg-primary/15 text-primary">
                <x-ui.icon name="terminal-2" class="size-4.5"/>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-sm font-bold tracking-tight text-white">Tameshk CLI</h1>
                    <span @class([
                        'rounded-md px-1.5 py-0.5 text-[10px] font-bold uppercase',
                        'bg-amber-500/15 text-amber-400' => ($systemInfo['env'] ?? '') === 'production',
                        'bg-emerald-500/15 text-emerald-400' => ($systemInfo['env'] ?? '') !== 'production',
                    ])>{{ $systemInfo['env'] ?? 'local' }}</span>
                </div>
                <p class="text-[11px] text-slate-400">
                    PHP {{ $systemInfo['php'] ?? PHP_VERSION }} • Laravel {{ $systemInfo['laravel'] ?? app()->version() }}
                </p>
            </div>
        </div>

        <a href="{{ route('app.workflows.index') }}"
           class="inline-flex items-center gap-1.5 rounded-xl border border-white/10 bg-white/4 px-3 py-1.5 text-xs font-semibold text-slate-200 transition hover:bg-white/8 sm:hidden">
            <span>Exit</span>
            <x-ui.icon name="external-link" class="size-3.5"/>
        </a>
    </div>

    <div class="flex items-center justify-between gap-2 sm:justify-end">
        <div class="grid w-full grid-cols-2 rounded-xl border border-white/10 bg-black/40 p-1 lg:hidden">
            <button type="button"
                    @click="activeTab = 'console'"
                    :class="activeTab === 'console' ? 'bg-primary text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white'"
                    class="rounded-lg py-1.5 text-xs transition cursor-pointer">
                Terminal
            </button>
            <button type="button"
                    @click="activeTab = 'quick'"
                    :class="activeTab === 'quick' ? 'bg-primary text-white font-bold shadow-xs' : 'text-slate-400 hover:text-white'"
                    class="rounded-lg py-1.5 text-xs transition cursor-pointer">
                Quick Actions
            </button>
        </div>

        <a href="{{ route('app.workflows.index') }}"
           class="hidden items-center gap-2 rounded-xl border border-white/10 bg-white/4 px-3.5 py-1.5 text-xs font-semibold text-slate-200 transition hover:border-white/20 hoverhover:bg-white/8nline-flex">
            <span>Dashboard</span>
            <x-ui.icon name="external-link" class="size-3.5"/>
        </a>
    </div>
</header>
