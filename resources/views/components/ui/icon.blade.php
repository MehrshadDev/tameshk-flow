@props(['name'])

@php
    $isBrand = str_starts_with($name, 'brand:');
    $brandName = $isBrand ? substr($name, 6) : null;
    $monoDarkBrands = ['chatgpt', 'wikipedia'];
    $invertClass = $isBrand && in_array($brandName, $monoDarkBrands, true) ? 'dark:invert' : '';
    $hasCustomSize = preg_match('/\b(size-|w-|h-)/', (string) $attributes->get('class')) === 1;
    $defaultSize = $hasCustomSize ? '' : 'size-5';
    $url = $isBrand
        ? asset('icons/brands/' . $brandName . '.svg')
        : asset('icons/tabler/' . $name . '.svg');
@endphp

@if ($isBrand)
    <img src="{{ $url }}"
         alt="{{ $brandName }}"
         loading="lazy"
        {{ $attributes->class(['inline-block shrink-0 object-contain pointer-events-none select-none', $defaultSize, $invertClass]) }}>
@else
    <span aria-hidden="true"
          {{ $attributes->class(['inline-block shrink-0 bg-current', $defaultSize]) }}
          style="-webkit-mask: url('{{ $url }}') center / contain no-repeat; mask: url('{{ $url }}') center / contain no-repeat;"></span>
@endif
