<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <h2 class="text-xl font-bold text-ink dark:text-white">Iniciar sesión</h2>
    <p class="mt-1 text-sm text-ink-secondary dark:text-gray-400">Ingresa con tu cuenta para continuar.</p>

    <!-- Session Status -->
    <x-auth-session-status class="mt-4" :status="session('status')" />

    <x-input-error :messages="$errors->get('sso')" class="mt-4" />

    @if (config('services.sso.activo'))
        <a href="{{ route('sso.redirect') }}" class="btn-microsoft mt-6">
            <svg width="20" height="20" viewBox="0 0 21 21" aria-hidden="true">
                <rect x="1" y="1" width="9" height="9" fill="#f25022"/>
                <rect x="11" y="1" width="9" height="9" fill="#7fba00"/>
                <rect x="1" y="11" width="9" height="9" fill="#00a4ef"/>
                <rect x="11" y="11" width="9" height="9" fill="#ffb900"/>
            </svg>
            <span>Continuar con Microsoft</span>
        </a>
    @endif

    @if (! config('services.sso.activo') || ! config('services.sso.solo'))
        @if (config('services.sso.activo'))
            <div class="my-5 flex items-center gap-3 text-xs uppercase tracking-wide text-ink-secondary dark:text-gray-400">
                <span class="h-px flex-1 bg-line dark:bg-white/10"></span>
                o con tu correo
                <span class="h-px flex-1 bg-line dark:bg-white/10"></span>
            </div>
        @else
            <div class="mt-6"></div>
        @endif

    <form wire:submit="login" class="space-y-4">
        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input wire:model="form.email" id="email" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Contraseña" />

            <div class="relative" x-data="{ mostrarPassword: false }">
                <x-text-input wire:model="form.password" id="password"
                                type="password"
                                x-bind:type="mostrarPassword ? 'text' : 'password'"
                                name="password"
                                required autocomplete="current-password"
                                class="pr-10" />

                <button
                    type="button"
                    x-on:click="mostrarPassword = ! mostrarPassword"
                    tabindex="-1"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-ink-secondary hover:text-ink dark:text-gray-400"
                >
                    <svg x-show="! mostrarPassword" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" /><circle cx="12" cy="12" r="3" />
                    </svg>
                    <svg x-show="mostrarPassword" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a21.8 21.8 0 0 1 5.06-6.06M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a21.8 21.8 0 0 1-3.22 4.44M14.12 14.12a3 3 0 1 1-4.24-4.24" /><line x1="1" y1="1" x2="23" y2="23" />
                    </svg>
                </button>
            </div>

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember" class="flex items-center gap-2 text-sm text-ink dark:text-gray-300">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-line text-brand-600 shadow-sm focus:ring-brand-500" name="remember">
                Recordarme
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-brand-600 hover:underline" href="{{ route('password.request') }}" wire:navigate>
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center py-2.5">
            Iniciar sesión
        </x-primary-button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-secondary dark:text-gray-400">
        El registro a la aplicación es únicamente a través del inicio de sesión corporativo (SSO).
    </p>
    @endif
</div>
