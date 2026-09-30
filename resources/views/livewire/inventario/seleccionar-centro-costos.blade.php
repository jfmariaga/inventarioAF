<?php

use App\Models\CentroCostos;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $busqueda = '';

    public function getResultadosProperty(): Collection
    {
        if (trim($this->busqueda) === '') {
            return collect();
        }

        $termino = trim($this->busqueda);

        return CentroCostos::with('empresa')
            ->where(function ($query) use ($termino) {
                $query->where('codigo', 'like', "%{$termino}%")
                    ->orWhere('descripcion', 'like', "%{$termino}%")
                    ->orWhereHas('activos', function ($query) use ($termino) {
                        $query->where('numero_activo', 'like', "%{$termino}%");
                    });
            })
            ->orderBy('codigo')
            ->limit(20)
            ->get();
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-ink-900">
            {{ __('Inventariar') }}
        </h2>
    </x-slot>

    <div class="mx-auto max-w-2xl">
        <div class="card card-pad">
            <label for="busqueda" class="field-label">
                Digita o busca el centro de costos
            </label>
            <input
                wire:model.live.debounce.300ms="busqueda"
                id="busqueda"
                type="text"
                placeholder="Código o nombre del centro de costos, o número de activo..."
                class="input"
                autofocus
            />

            <ul class="mt-4 divide-y divide-ink-100">
                @forelse ($this->resultados as $centroCostos)
                    <li>
                        <a
                            href="{{ route('inventario.centro-costos', $centroCostos) }}"
                            wire:navigate
                            class="flex items-center justify-between rounded-lg px-2 py-2.5 hover:bg-ink-50"
                        >
                            <span>
                                <span class="font-medium text-ink-900">{{ $centroCostos->codigo }}</span>
                                <span class="text-ink-500">— {{ $centroCostos->descripcion }}</span>
                            </span>
                            <span class="badge badge-brand">{{ $centroCostos->empresa->nombre }}</span>
                        </a>
                    </li>
                @empty
                    @if (trim($busqueda) !== '')
                        <li class="px-2 py-3 text-sm text-ink-500">No se encontraron centros de costos.</li>
                    @endif
                @endforelse
            </ul>
        </div>
    </div>
</div>
