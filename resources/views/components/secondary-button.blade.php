<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center justify-center gap-2 rounded-lg border border-edge bg-transparent px-4 py-2.5 font-mono text-xs font-medium uppercase tracking-widest text-slate-300 transition hover:border-slate-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-signal/40 focus:ring-offset-2 focus:ring-offset-ink active:scale-[0.98] disabled:opacity-40']) }}>
    {{ $slot }}
</button>
