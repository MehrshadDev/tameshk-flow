@props([
    'quickGroups' => [],
])

<aside x-show="!isExpanded"
       :class="activeTab === 'quick' ? 'flex flex-1 min-h-0' : 'hidden lg:flex'"
       class="w-full shrink-0 flex-col overflow-hidden rounded-2xl border border-white/12 bg-[#090d16]/90 shadow-2xl backdrop-blur-2xl sm:rounded-3xl lg:w-80 xl:w-88 lg:min-h-0">

    <div class="hidden shrink-0 items-center justify-between border-b border-white/10 bg-white/2 px-4 py-2.5 lg:flex">
        <div class="flex items-center gap-2">
            <x-ui.icon name="bolt" class="size-4 text-primary"/>
            <h2 class="text-xs font-bold uppercase tracking-wider text-white">Quick Actions</h2>
        </div>
        <span class="text-[10px] text-slate-400">Safe 1-Click</span>
    </div>

    <div class="custom-scrollbar flex-1 space-y-4 overflow-y-auto p-3.5">
        @foreach ($quickGroups as $group)
            <div class="space-y-1.5">
                <div class="flex items-center gap-1.5 px-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    <x-ui.icon :name="$group['icon']" class="size-3.5 text-primary"/>
                    <span>{{ $group['title'] }}</span>
                </div>

                <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2 lg:grid-cols-1">
                    @foreach ($group['items'] as $item)
                        <button type="button"
                                @click="runQuick(@js($item['command']))"
                                :disabled="running"
                                class="group flex items-center justify-between gap-2 rounded-xl border border-white/8 bg-white/2.5 px-3 py-2 text-left transition hover:border-primary/40 hovehover:bg-white/6abled:opacity-50 cursor-pointer">
                            <span class="truncate text-xs font-medium text-slate-200 group-hover:text-white">
                                {{ $item['label'] }}
                            </span>
                            <code class="shrink-0 rounded bg-black/50 px-1.5 py-0.5 text-[10px] font-bold text-sky-400">
                                {{ $item['command'] }}
                            </code>
                        </button>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div x-show="history.length > 0" x-cloak class="space-y-2 border-t border-white/10 pt-3">
            <div class="flex items-center justify-between px-1">
                <span class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                    <x-ui.icon name="history" class="size-3.5 text-sky-400"/>
                    <span>History</span>
                </span>
                <button type="button"
                        @click="clearHistory()"
                        class="text-[10px] text-rose-400 hover:underline cursor-pointer">
                    Clear
                </button>
            </div>

            <div class="flex flex-wrap gap-1.5">
                <template x-for="(hCmd, hIdx) in history.slice().reverse().slice(0, 10)" :key="hIdx">
                    <button type="button"
                            @click="selectHistoryPill(hCmd)"
                            class="max-w-full truncate rounded-lg border border-white/10 bg-black/40 px-2.5 py-1 text-[11px] text-slate-300 transition hover:border-sky-500/40 hover:text-white cursor-pointer"
                            x-text="hCmd"></button>
                </template>
            </div>
        </div>
    </div>
</aside>
