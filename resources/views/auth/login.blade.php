<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-bold tracking-tight text-white">Iniciar sesión</h1>
        <p class="mt-1 font-mono text-xs text-muted">Ingresá con tu cuenta para ver el panel.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="usuario@ejemplo.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <div class="flex items-baseline justify-between">
                <x-input-label for="password" value="Contraseña" />
                @if (Route::has('password.request'))
                    <a class="font-mono text-[11px] text-muted underline decoration-edge underline-offset-4 transition hover:text-signal hover:decoration-signal focus:outline-none focus:text-signal" href="{{ route('password.request') }}">
                        ¿La olvidaste?
                    </a>
                @endif
            </div>

            <x-text-input id="password" class="mt-2 block w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" placeholder="••••••••" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2">
            <input id="remember_me" type="checkbox" class="size-4 rounded border-edge bg-ink text-signal focus:ring-signal/40 focus:ring-offset-ink" name="remember">
            <span class="font-mono text-xs text-muted">Recordarme</span>
        </label>

        <x-primary-button class="w-full">
            Ingresar
        </x-primary-button>

        @if (Route::has('register'))
            <p class="border-t border-edge pt-5 text-center font-mono text-xs text-muted">
                ¿No tenés cuenta?
                <a href="{{ route('register') }}" class="text-slate-300 underline decoration-edge underline-offset-4 transition hover:text-signal hover:decoration-signal">Registrate</a>
            </p>
        @endif
    </form>
</x-guest-layout>
