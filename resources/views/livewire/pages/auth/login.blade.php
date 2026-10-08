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
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <x-input-error :messages="$errors->get('sso')" class="mb-4" />

    @if (config('services.sso.activo'))
        <a href="{{ route('sso.redirect') }}" class="btn-microsoft">
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
            <div class="my-4 flex items-center gap-3 text-xs text-ink-400">
                <span class="h-px flex-1 bg-ink-200"></span>
                o con tu correo
                <span class="h-px flex-1 bg-ink-200"></span>
            </div>
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

            <x-text-input wire:model="form.password" id="password"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <label for="remember" class="flex items-center gap-2 text-sm text-ink-600">
            <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-ink-300 text-brand-700 shadow-sm focus:ring-brand-500" name="remember">
            Recordarme
        </label>

        <div class="flex items-center justify-between pt-2">
            @if (Route::has('password.request'))
                <a class="text-sm text-ink-500 underline hover:text-ink-700" href="{{ route('password.request') }}" wire:navigate>
                    ¿Olvidaste tu contraseña?
                </a>
            @endif

            <x-primary-button>
                Iniciar sesión
            </x-primary-button>
        </div>
    </form>

    <p class="mt-6 text-center text-sm text-ink-500">
        ¿No tienes cuenta?
        <a href="{{ route('register') }}" wire:navigate class="font-medium text-brand-700 underline hover:text-brand-800">
            Regístrate
        </a>
    </p>
    @endif
</div>
