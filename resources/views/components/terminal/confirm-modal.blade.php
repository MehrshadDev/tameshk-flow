<div x-show="confirmModal.open"
     x-cloak
     class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div x-show="confirmModal.open"
         x-transition.opacity
         @click="cancelConfirm()"
         class="fixed inset-0 bg-black/75 backdrop-blur-md"></div>

    <div x-show="confirmModal.open"
         x-transition
         class="relative z-10 w-full max-w-md overflow-hidden rounded-3xl border border-rose-500/40 bg-[#0d111d] p-6 shadow-2xl">
        <div class="flex items-start gap-3.5">
            <div class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-rose-500/15 text-rose-400 ring-1 ring-rose-500/30">
                <x-ui.icon name="alert-triangle" class="size-6"/>
            </div>
            <div class="min-w-0 flex-1">
                <h3 class="text-base font-bold text-white">High-Risk Command Confirmation</h3>
                <p class="mt-1 text-xs leading-5 text-slate-300" x-text="confirmModal.warning"></p>
            </div>
        </div>

        <div class="mt-4 rounded-2xl border border-white/10 bg-black/50 p-3.5">
            <p class="text-[10px] uppercase tracking-wider text-slate-400">Command to execute:</p>
            <code class="mt-1 block break-all text-xs font-bold text-rose-400" x-text="confirmModal.command"></code>
        </div>

        <div class="mt-6 flex items-center justify-end gap-2.5">
            <button type="button"
                    @click="cancelConfirm()"
                    class="rounded-xl border border-white/10 bg-white/5 px-4 py-2 text-xs font-semibold text-slate-300 transition hover:bg-white/10 cursor-pointer">
                Cancel
            </button>
            <button type="button"
                    @click="confirmAndExecute()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2 text-xs font-bold text-white shadow-lg shadow-rose-600/30 transition hover:bg-rose-500 cursor-pointer">
                <x-ui.icon name="bolt" class="size-4"/>
                <span>Yes, Execute Anyway</span>
            </button>
        </div>
    </div>
</div>
