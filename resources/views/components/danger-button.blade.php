<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 rounded-lg border border-danger/40 bg-danger/10 px-4 py-2.5 font-mono text-xs font-bold uppercase tracking-widest text-danger transition hover:bg-danger/20 focus:outline-none focus:ring-2 focus:ring-danger/50 focus:ring-offset-2 focus:ring-offset-ink active:scale-[0.98]']) }}>
    {{ $slot }}
</button>
