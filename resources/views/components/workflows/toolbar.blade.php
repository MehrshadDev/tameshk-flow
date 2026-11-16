@props([
    'search' => '',
    'status' => 'all',
    'atLimit' => false,
])

<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div class="flex flex-1 flex-col gap-2.5 sm:flex-row sm:items-center">
        <div class="relative flex-1">
            <x-ui.icon name="search"
                       class="pointer-events-none absolute inset-s-3.5 top-1/2 size-4.5 -translate-y-1/2 text-content-muted"/>
            <input type="text"
                   maxlength="100"
                   wire:model.live.debounce.300ms="search"
                   placeholder="جستجو در نام و توضیحات ورکفلو..."
                   class="h-10 w-full rounded-xl border border-line-border bg-surface-card pe-9 ps-10 text-xs font-medium text-content-main shadow-2xs transition placeholder:text-content-muted focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30">
            @if ($search !== '')
                <button type="button"
                        wire:click="$set('search', '')"
                        aria-label="پاک کردن جستجو"
                        class="absolute inset-e-2.5 top-1/2 flex size-6 -translate-y-1/2 items-center justify-center rounded-lg text-content-muted transition hover:bg-line-border/60 hover:text-content-main cursor-pointer">
                    <x-ui.icon name="x" class="size-3.5"/>
                </button>
            @endif
        </div>

        <x-ui.segmented
            model="status"
            action="setStatus"
            variant="card"
            cols="3"
            class="h-10"
            :options="[
                'all' => 'همه',
                'active' => 'فعال',
                'inactive' => 'غیرفعال',
            ]"
        />
    </div>

    <x-ui.button type="button"
                 wire:click="create"
                 wire:loading.attr="disabled"
                 wire:target="create"
                 :disabled="$atLimit"
                 class="h-10 cursor-pointer shrink-0">
        <x-ui.icon name="plus" class="size-4"/>
        <span wire:loading.remove wire:target="create">ورکفلو جدید</span>
        <span wire:loading wire:target="create">در حال ساخت...</span>
    </x-ui.button>
</div>
