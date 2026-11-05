@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-lg border-edge bg-ink px-3 py-2.5 font-mono text-sm text-white placeholder:text-muted/70 shadow-none transition focus:border-signal focus:ring-2 focus:ring-signal/25 disabled:opacity-50']) }}>
