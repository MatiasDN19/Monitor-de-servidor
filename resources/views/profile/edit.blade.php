<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-3xl font-bold tracking-tight text-white">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-6xl space-y-4 px-5 sm:px-8">
            <div class="rounded-2xl border border-edge bg-panel/80 p-6 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="rounded-2xl border border-edge bg-panel/80 p-6 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="rounded-2xl border border-edge bg-panel/80 p-6 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
