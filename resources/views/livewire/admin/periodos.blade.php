<?php

use App\Models\Inventario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public string $nombre = '';

    public string $fechaApertura;

    public function mount(): void
    {
        $this->fechaApertura = now()->toDateString();
    }

    public function getPeriodosProperty(): LengthAwarePaginator
    {
        return Inventario::with(['abiertoPor', 'cerradoPor', 'reabiertoPor'])
            ->withCount('detalles')
            ->latest('fecha_apertura')
            ->paginate(10);
    }

    public function getHayPeriodoAbiertoProperty(): bool
    {
        return Inventario::where('estado', 'abierto')->exists();
    }

    public function abrir(): void
    {
        $this->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'fechaApertura' => ['required', 'date'],
        ]);

        if ($this->hayPeriodoAbierto) {
            $this->addError('nombre', 'Ya hay un periodo de inventario abierto. Ciérralo antes de abrir uno nuevo.');

            return;
        }

        Inventario::create([
            'nombre' => $this->nombre,
            'fecha_apertura' => $this->fechaApertura,
            'estado' => 'abierto',
            'abierto_por' => auth()->id(),
        ]);

        $this->reset('nombre');
        $this->dispatch('toast', icon: 'success', title: 'Periodo de inventario abierto correctamente.');
    }

    public function cerrar(int $inventarioId): void
    {
        $inventario = Inventario::findOrFail($inventarioId);

        $inventario->update([
            'estado' => 'cerrado',
            'fecha_cierre' => now(),
            'cerrado_por' => auth()->id(),
        ]);

        $this->dispatch('toast', icon: 'success', title: 'Periodo cerrado correctamente.');
    }

    public function reabrir(int $inventarioId): void
    {
        $inventario = Inventario::findOrFail($inventarioId);

        $inventario->update([
            'estado' => 'abierto',
            'reabierto_por' => auth()->id(),
            'reabierto_en' => now(),
        ]);

        $this->dispatch('toast', icon: 'success', title: 'Periodo reabierto correctamente.');
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-ink-900">
            {{ __('Periodos de inventario') }}
        </h2>
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-8">
        <div class="card card-pad">
            <h3 class="mb-4 text-base font-semibold text-ink-900">Abrir nuevo periodo</h3>

            @if ($this->hayPeriodoAbierto)
                <p class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">
                    Ya hay un periodo abierto. Debes cerrarlo antes de abrir uno nuevo.
                </p>
            @else
                <form wire:submit="abrir" class="space-y-4">
                    <div>
                        <label class="field-label">Nombre del periodo</label>
                        <input type="text" wire:model="nombre" placeholder="Ej: Inventario 2026" class="input" />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>
                    <div>
                        <label class="field-label">Fecha de apertura</label>
                        <input type="date" wire:model="fechaApertura" class="input" />
                    </div>
                    <div class="flex justify-end">
                        <x-primary-button type="submit">Abrir periodo</x-primary-button>
                    </div>
                </form>
            @endif
        </div>

        <div class="table-wrap card">
            <table class="dtable">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Apertura</th>
                        <th>Estado</th>
                        <th>Registros</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->periodos as $periodo)
                        <tr>
                            <td class="font-medium text-ink-900">{{ $periodo->nombre }}</td>
                            <td>{{ $periodo->fecha_apertura->format('Y-m-d') }}</td>
                            <td>
                                <span class="badge {{ $periodo->estado === 'abierto' ? 'badge-green' : 'badge-gray' }}">
                                    {{ ucfirst($periodo->estado) }}
                                </span>
                            </td>
                            <td>{{ $periodo->detalles_count }}</td>
                            <td class="text-right">
                                @if ($periodo->estado === 'abierto')
                                    <button type="button" wire:click="cerrar({{ $periodo->id }})" wire:confirm="¿Cerrar este periodo? Los usuarios ya no podrán capturar sobre él." class="btn btn-danger btn-xs">
                                        Cerrar
                                    </button>
                                @else
                                    <button type="button" wire:click="reabrir({{ $periodo->id }})" class="btn btn-secondary btn-xs">
                                        Reabrir
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div>
            {{ $this->periodos->links() }}
        </div>
    </div>
</div>
