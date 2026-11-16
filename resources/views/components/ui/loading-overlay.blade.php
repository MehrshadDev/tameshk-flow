@props([
    'target',
    'label' => 'در حال بارگذاری...',
])

<div wire:loading.flex
     wire:target="{{ $target }}"
    {{ $attributes->class(['absolute inset-0 z-20 m-0! hidden items-center justify-center rounded-2xl bg-surface-base/65 backdrop-blur-[2px] transition-opacity']) }}>
    <div class="inline-flex items-center gap-2.5 rounded-2xl border border-line-border bg-surface-raised px-4 py-2.5 text-xs font-bold text-content-main shadow-xl">
        <x-ui.icon name="loader" class="size-4 animate-spin text-primary"/>
        <span>{{ $label }}</span>
    </div>
</div>
