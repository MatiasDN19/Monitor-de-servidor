<x-guest-layout>
    <div class="mb-4 text-sm text-muted">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-mono text-xs text-ok">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="font-mono text-xs text-muted underline decoration-edge underline-offset-4 transition hover:text-signal hover:decoration-signal rounded focus:outline-none focus:ring-2 focus:ring-signal/40">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
