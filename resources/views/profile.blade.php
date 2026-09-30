<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-ink-900">
            {{ __('Perfil') }}
        </h2>
    </x-slot>

    <div class="mx-auto max-w-2xl space-y-6">
        <div class="card card-pad">
            <livewire:profile.update-profile-information-form />
        </div>

        <div class="card card-pad">
            <livewire:profile.update-password-form />
        </div>
    </div>
</x-app-layout>
