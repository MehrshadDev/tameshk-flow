@props(['variant' => 'primary', 'size' => 'md', 'href' => null])

@php
    $variants = [
        'primary' => 'bg-primary text-white hover:bg-primary/90',
        'secondary' => 'border border-line-border bg-surface-card text-content-main hover:bg-line-border/50',
        'ghost' => 'text-content-muted hover:bg-line-border/50 hover:text-content-main',
    ];

    $hasCustomRadius = str_contains((string) $attributes->get('class'), 'rounded-');

    $sizes = [
        'md' => ($hasCustomRadius ? '' : 'rounded-xl ') . 'px-4 py-2 text-sm',
        'lg' => ($hasCustomRadius ? '' : 'rounded-xl ') . 'px-7 py-3 text-base',
    ];

    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }} {{ $attributes->merge(['href' => $href, 'class' => 'inline-flex items-center justify-center gap-2 font-medium transition disabled:cursor-not-allowed disabled:opacity-50 ' . $variants[$variant] . ' ' . $sizes[$size]]) }}>
{{ $slot }}
</{{ $tag }}>
