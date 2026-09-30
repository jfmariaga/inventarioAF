<?php

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public function getUsuariosProperty(): LengthAwarePaginator
    {
        return User::with('roles')->orderBy('name')->paginate(15);
    }

    public function getRolesDisponiblesProperty(): Collection
    {
        return Role::orderBy('name')->pluck('name');
    }

    public function cambiarRol(int $usuarioId, string $rol): void
    {
        $usuario = User::findOrFail($usuarioId);
        $usuario->syncRoles([$rol]);

        $this->dispatch('toast', icon: 'success', title: "Rol de {$usuario->name} actualizado a {$rol}.");
    }

    public function alternarActivo(int $usuarioId): void
    {
        $usuario = User::findOrFail($usuarioId);

        if ($usuario->id === auth()->id()) {
            return;
        }

        $usuario->update(['activo' => ! $usuario->activo]);

        $this->dispatch('toast', icon: 'success', title: $usuario->activo ? 'Usuario activado.' : 'Usuario desactivado.');
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-ink-900">
            {{ __('Gestión de usuarios') }}
        </h2>
    </x-slot>

    <div class="table-wrap card">
        <table class="dtable">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($this->usuarios as $usuario)
                    <tr>
                        <td class="font-medium text-ink-900">{{ $usuario->name }}</td>
                        <td class="text-ink-500">{{ $usuario->email }}</td>
                        <td>
                            <select
                                wire:change="cambiarRol({{ $usuario->id }}, $event.target.value)"
                                class="select w-40 py-1 text-xs"
                            >
                                @foreach ($this->rolesDisponibles as $rol)
                                    <option value="{{ $rol }}" @selected($usuario->roles->pluck('name')->contains($rol))>{{ $rol }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <button
                                type="button"
                                wire:click="alternarActivo({{ $usuario->id }})"
                                @disabled($usuario->id === auth()->id())
                                class="badge {{ $usuario->activo ? 'badge-green' : 'badge-red' }}"
                            >
                                {{ $usuario->activo ? 'Activo' : 'Desactivado' }}
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $this->usuarios->links() }}
    </div>
</div>
