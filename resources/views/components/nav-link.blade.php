@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center px-1 pt-1 border-b-2 border-signal font-mono text-xs uppercase tracking-widest text-white focus:outline-none transition'
            : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent font-mono text-xs uppercase tracking-widest text-muted hover:text-white hover:border-edge focus:outline-none focus:text-white transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
