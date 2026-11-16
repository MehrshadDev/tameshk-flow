@props([
    'options' => [],
    'model' => null,
    'action' => null,
    'alpine' => null,
    'variant' => 'surface',
    'cols' => null,
    'size' => 'md',
])

@php
    $normalized = [];

    foreach ($options as $key => $item) {
        if (is_array($item)) {
            $val = (string) ($item['value'] ?? $key);
            $normalized[] = [
                'value' => $val,
                'label' => (string) ($item['label'] ?? $val),
                'icon' => $item['icon'] ?? null,
                'count' => $item['count'] ?? null,
                'badge' => $item['badge'] ?? null,
            ];
        } else {
            $normalized[] = [
                'value' => (string) $key,
                'label' => (string) $item,
                'icon' => null,
                'count' => null,
                'badge' => null,
            ];
        }
    }

    $containerBg = $variant === 'card'
        ? 'border-line-border bg-surface-card shadow-2xs'
        : 'border-line-border bg-surface-base';

    $layoutClass = match ((string) $cols) {
        '2' => 'grid grid-cols-2 gap-1 sm:flex sm:shrink-0',
        '3' => 'grid grid-cols-3 gap-1 sm:flex sm:shrink-0',
        '4' => 'grid grid-cols-4 gap-1 sm:flex sm:shrink-0',
        default => 'flex shrink-0 items-center gap-1',
    };

    $buttonSize = match ($size) {
        'sm' => 'px-2.5 py-1.5 text-[11px]',
        'lg' => 'px-3.5 py-2 text-xs',
        default => 'px-3 py-1.5 text-xs',
    };
@endphp

<div data-model="{{ $model ?? '' }}"
     data-action="{{ $action ?? '' }}"
     data-alpine="{{ $alpine ?? '' }}"
     x-data="{
         modelName: $el.dataset.model || '',
         actionName: $el.dataset.action || '',
         alpineKey: $el.dataset.alpine || '',
         active: $el.dataset.model ? $wire.entangle($el.dataset.model) : null,
         isActive(val) {
             return (this.alpineKey ? this[this.alpineKey] : this.active) === val;
         },
         select(val) {
             if (this.isActive(val)) return;

             if (this.alpineKey) {
                 this[this.alpineKey] = val;
                 return;
             }

             this.active = val;

             if (this.actionName) {
                 this.$wire[this.actionName](val);
             } else if (this.modelName) {
                 this.$wire.set(this.modelName, val);
             }
         }
     }"
    {{ $attributes->class(["rounded-xl border p-1 $containerBg $layoutClass"]) }}>
    @foreach ($normalized as $opt)
        @php
            $val = $opt['value'];
        @endphp

        <button type="button"
                data-value="{{ $val }}"
                @click="select($el.dataset.value)"
                :class="isActive($el.dataset.value)
                    ? 'cursor-default bg-primary text-white font-bold shadow-2xs'
                    : 'cursor-pointer font-medium text-content-muted hover:text-content-main'"
                class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg text-center whitespace-nowrap transition sm:flex-none {{ $buttonSize }}">
            @if ($opt['icon'])
                <x-ui.icon :name="$opt['icon']" class="size-4 shrink-0"/>
            @endif

            <span>
                {{ $opt['label'] }}
                @if ($opt['count'] !== null)
                    ({{ is_numeric($opt['count']) ? number_format((int) $opt['count']) : $opt['count'] }})
                @endif
            </span>

            @if ($opt['badge'])
                <span data-value="{{ $val }}"
                      :class="isActive($el.dataset.value)
                          ? 'bg-white/20 text-white'
                          : 'bg-emerald-500/15 text-emerald-500'"
                      class="rounded-md px-1.5 py-0.5 text-[10px] font-bold transition-colors">
                    {{ $opt['badge'] }}
                </span>
            @endif
        </button>
    @endforeach
</div>
