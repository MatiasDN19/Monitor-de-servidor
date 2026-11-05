@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-mono text-xs uppercase tracking-widest text-muted']) }}>
    {{ $value ?? $slot }}
</label>
