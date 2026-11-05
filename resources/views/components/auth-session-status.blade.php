@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-lg border border-ok/30 bg-ok/10 px-3 py-2 font-mono text-xs text-ok']) }}>
        {{ $status }}
    </div>
@endif
