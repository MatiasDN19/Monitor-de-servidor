<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-lg bg-signal px-4 py-2.5 font-mono text-xs font-bold uppercase tracking-widest text-ink transition hover:bg-signal/85 focus:outline-none focus:ring-2 focus:ring-signal/50 focus:ring-offset-2 focus:ring-offset-ink active:scale-[0.98] disabled:opacity-40']) }}>
    {{ $slot }}
</button>
