@props([
    'systemInfo' => [],
])

@php
    $userHost = ($systemInfo['user'] ?? 'www-data') . '@' . ($systemInfo['host'] ?? 'localhost');
@endphp

<section :class="[
             isExpanded ? 'fixed inset-2 sm:inset-4 z-50' : 'flex-1 min-h-0',
             activeTab === 'console' ? 'flex' : 'hidden lg:flex'
         ]"
         class="min-w-0 flex-col overflow-hidden rounded-2xl border border-white/12 bg-[#090d16]/90 shadow-2xl backdrop-blur-2xl sm:rounded-3xl">

    <div class="flex shrink-0 items-center justify-between gap-2 border-b border-white/10 bg-white/2 px-3 py-2 sm:px-4">
        <div class="flex min-w-0 items-center gap-2.5">
            <div class="hidden items-center gap-1.5 sm:flex">
                <button type="button" @click="clearScreen()" title="Clear (Ctrl+L)" class="size-2.5 rounded-full bg-rose-500/80 hover:bg-rose-400 cursor-pointer"></button>
                <button type="button" @click="resetZoom()" title="Reset Zoom" class="size-2.5 rounded-full bg-amber-500/80 hover:bg-amber-400 cursor-pointer"></button>
                <button type="button" @click="isExpanded = !isExpanded" title="Expand" class="size-2.5 rounded-full bg-emerald-500/80 hover:bg-emerald-400 cursor-pointer"></button>
            </div>

            <div class="flex min-w-0 items-center gap-1.5 rounded-lg border border-white/10 bg-black/40 px-2.5 py-1 text-[11px]">
                <span class="text-emerald-400">➜</span>
                <span class="truncate font-semibold text-sky-400" x-text="formattedCwd()">~</span>
            </div>
        </div>

        <div class="flex shrink-0 items-center gap-1">
            <div class="flex items-center rounded-lg border border-white/10 bg-black/40 p-0.5">
                <button type="button"
                        @click="zoomOut()"
                        title="Zoom Out"
                        class="flex size-6 items-center justify-center rounded text-slate-400 hover:bg-white/10 hover:text-white cursor-pointer">
                    <x-ui.icon name="minus" class="size-3"/>
                </button>
                <button type="button"
                        @click="resetZoom()"
                        class="px-1.5 text-[10px] font-bold tabular-nums text-slate-300 hover:text-white cursor-pointer"
                        x-text="fontSize"></button>
                <button type="button"
                        @click="zoomIn()"
                        title="Zoom In"
                        class="flex size-6 items-center justify-center rounded text-slate-400 hover:bg-white/10 hover:text-white cursor-pointer">
                    <x-ui.icon name="plus" class="size-3"/>
                </button>
            </div>

            <button type="button"
                    @click="copyAllOutput()"
                    title="Copy Output"
                    class="flex size-7 items-center justify-center rounded-lg border border-white/10 bg-black/40 text-slate-300 transition hover:bg-white/10 hover:text-white cursor-pointer">
                <x-ui.icon name="copy" x-show="!copiedAll" class="size-3.5"/>
                <x-ui.icon name="check" x-show="copiedAll" x-cloak class="size-3.5 text-emerald-400"/>
            </button>

            <button type="button"
                    @click="clearScreen()"
                    title="Clear Terminal (Ctrl+L)"
                    class="flex size-7 items-center justify-center rounded-lg border border-white/10 bg-black/40 text-slate-300 transition hover:bg-white/10 hover:text-white cursor-pointer">
                <x-ui.icon name="trash" class="size-3.5"/>
            </button>

            <button type="button"
                    @click="isExpanded = !isExpanded"
                    title="Toggle Fullscreen"
                    class="hidden size-7 items-center justify-center rounded-lg border border-white/10 bg-black/40 text-slate-300 transition hover:bg-white/10 hover:text-white sm:flex cursor-pointer">
                <x-ui.icon name="maximize" x-show="!isExpanded" class="size-3.5"/>
                <x-ui.icon name="minimize" x-show="isExpanded" x-cloak class="size-3.5"/>
            </button>
        </div>
    </div>

    <div x-ref="viewport"
         @click="focusInput($event)"
         :style="`font-size: ${fontSize}px; line-height: 1.6;`"
         class="custom-scrollbar min-h-0 flex-1 space-y-3.5 overflow-y-auto p-3 sm:p-4">

        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-white/8 pb-2.5 text-[11px] text-slate-400">
            <span>Connected to <strong class="text-emerald-400">{{ $userHost }}</strong></span>
            <span>Type <code class="text-sky-400">help</code> • <kbd class="rounded border border-white/15 px-1 text-[10px]">Tab</kbd> Complete • <kbd class="rounded border border-white/15 px-1 text-[10px]">↑/↓</kbd> History</span>
        </div>

        <template x-for="entry in entries" :key="entry.id">
            <div class="group space-y-1.5">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex min-w-0 flex-wrap items-center gap-1.5">
                        <span class="font-bold text-sky-400" x-text="entry.cwd ? '~/' + entry.cwd : '~'"></span>
                        <span class="font-bold text-primary">$</span>
                        <span class="break-all font-bold text-white" x-text="entry.command"></span>
                    </div>

                    <div class="flex shrink-0 items-center gap-1.5 text-[10px] text-slate-500">
                        <template x-if="entry.duration_ms !== null">
                            <span class="tabular-nums" x-text="entry.duration_ms + 'ms'"></span>
                        </template>
                        <template x-if="entry.exit_code !== null">
                            <span class="rounded px-1.5 py-0.5 font-bold"
                                  :class="entry.exit_code === 0 ? 'bg-emerald-500/15 text-emerald-400' : 'bg-rose-500/15 text-rose-400'"
                                  x-text="entry.exit_code === 0 ? 'OK' : 'ERR ' + entry.exit_code"></span>
                        </template>
                    </div>
                </div>

                <template x-if="entry.output">
                    <pre class="overflow-x-auto whitespace-pre-wrap wrap-break-word rounded-xl border px-3 py-2 font-mono select-text"
                         :class="entry.exit_code === 0
                             ? 'border-white/8 bg-black/40 text-slate-200'
                             : 'border-rose-500/30 bg-rose-950/20 text-rose-300'"
                         x-text="entry.output"></pre>
                </template>
            </div>
        </template>

        <div x-show="running" x-cloak class="flex items-center gap-2 text-xs text-amber-400">
            <x-ui.icon name="loader" class="size-3.5 animate-spin"/>
            <span>Running...</span>
        </div>
    </div>

    <div x-show="suggestions.length > 0"
         x-cloak
         class="shrink-0 border-t border-white/10 bg-black/75 px-3 py-2">
        <div class="custom-scrollbar flex max-h-20 flex-wrap gap-1.5 overflow-y-auto">
            <template x-for="(item, idx) in suggestions" :key="item.value">
                <button type="button"
                        @click="applySuggestion(item.value)"
                        :class="idx === suggestionIndex ? 'border-primary bg-primary/20 text-white' : 'border-white/10 bg-white/4 text-slate-300 hover:bg-white/10'"
                        class="inline-flex items-center gap-1.5 rounded-lg border px-2 py-0.5 text-[11px] transition cursor-pointer">
                    <span class="font-bold" x-text="item.value"></span>
                </button>
            </template>
        </div>
    </div>

    <form @submit.prevent="submitCommand()"
          class="flex shrink-0 items-center gap-2 border-t border-white/10 bg-black/60 px-3 py-2.5 sm:px-4">
        <div class="flex shrink-0 items-center gap-1 text-xs">
            <span class="font-bold text-sky-400" x-text="formattedCwd()">~</span>
            <span class="font-bold text-primary">$</span>
        </div>

        <input x-ref="cmdInput"
               type="text"
               x-model="input"
               :disabled="running"
               @keydown="handleInputKeydown($event)"
               @input="resetTabCycle()"
               autocomplete="off"
               autocapitalize="off"
               autocorrect="off"
               spellcheck="false"
               placeholder="optimize:clear, migrate:status, ls -la..."
               class="min-w-0 flex-1 bg-transparent font-mono text-xs text-white placeholder:text-slate-500 focus:outline-none disabled:opacity-50 sm:text-sm"/>

        <button type="submit"
                :disabled="running || !input.trim()"
                class="inline-flex h-8 shrink-0 items-center gap-1.5 rounded-xl bg-primary px-3 text-xs font-bold text-white transition hover:bg-primary/90 disabled:opacity-40 cursor-pointer">
            <span>Run</span>
            <x-ui.icon name="player-play" class="size-3"/>
        </button>
    </form>
</section>
