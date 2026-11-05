@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-2 border-signal bg-signal/10 text-start font-mono text-sm uppercase tracking-widest text-white focus:outline-none transition'
            : 'block w-full ps-3 pe-4 py-2 border-l-2 border-transparent text-start font-mono text-sm uppercase tracking-widest text-muted hover:text-white hover:bg-white/5 hover:border-edge focus:outline-none focus:text-white transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
