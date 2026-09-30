<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    /**
     * Send an email verification notification to the user.
     */
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <div class="mb-4 text-sm text-ink-600">
        ¡Gracias por registrarte! Antes de empezar, ¿puedes verificar tu correo haciendo clic en el enlace que te enviamos? Si no lo recibiste, con gusto te enviamos otro.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 text-sm font-medium text-emerald-600">
            Se envió un nuevo enlace de verificación al correo que registraste.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <x-primary-button wire:click="sendVerification">
            Reenviar correo de verificación
        </x-primary-button>

        <button wire:click="logout" type="submit" class="rounded-md text-sm text-ink-500 underline hover:text-ink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500">
            Cerrar sesión
        </button>
    </div>
</div>
