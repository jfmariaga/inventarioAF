<?php

use App\Models\Activo;
use App\Models\Empresa;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public string $busqueda = '';

    public function getEmpresasProperty(): Collection
    {
        return Empresa::withCount('centrosCostos')
            ->orderBy('nombre')
            ->get()
            ->map(function (Empresa $empresa) {
                $empresa->activos_count = Activo::whereHas(
                    'centroCostos',
                    fn ($query) => $query->where('empresa_id', $empresa->id)
                )->count();

                return $empresa;
            });
    }

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function getCentrosCostosProperty(): mixed
    {
        return \App\Models\CentroCostos::with('empresa')
            ->withCount('activos')
            ->when(trim($this->busqueda) !== '', function ($query) {
                $termino = trim($this->busqueda);

                $query->where(function ($query) use ($termino) {
                    $query->where('codigo', 'like', "%{$termino}%")
                        ->orWhere('descripcion', 'like', "%{$termino}%")
                        ->orWhereHas('activos', function ($query) use ($termino) {
                            $query->where('numero_activo', 'like', "%{$termino}%");
                        });
                });
            })
            ->orderBy('codigo')
            ->paginate(20);
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-ink-900">
            {{ __('Catálogos') }}
        </h2>
    </x-slot>

    <div class="space-y-8">
        <div>
            <h3 class="mb-2 text-base font-semibold text-ink-900">Empresas</h3>
            <div class="table-wrap card">
                <table class="dtable">
                    <thead>
                        <tr>
                            <th>Empresa</th>
                            <th>Centros de costos</th>
                            <th>Activos</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->empresas as $empresa)
                            <tr>
                                <td class="font-medium text-ink-900">{{ $empresa->nombre }}</td>
                                <td>{{ $empresa->centros_costos_count }}</td>
                                <td>{{ $empresa->activos_count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <h3 class="mb-2 text-base font-semibold text-ink-900">Centros de costos</h3>
            <input
                wire:model.live.debounce.300ms="busqueda"
                type="text"
                placeholder="Buscar por código, nombre o número de activo..."
                class="input mb-3 max-w-sm"
            />
            <div class="table-wrap card">
                <table class="dtable">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Empresa</th>
                            <th>Activos</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->centrosCostos as $centroCostos)
                            <tr>
                                <td class="text-ink-900">{{ $centroCostos->codigo }}</td>
                                <td>{{ $centroCostos->descripcion }}</td>
                                <td>{{ $centroCostos->empresa->nombre }}</td>
                                <td>{{ $centroCostos->activos_count }}</td>
                                <td class="text-right">
                                    <a href="{{ route('inventario.centro-costos', $centroCostos) }}" wire:navigate class="btn btn-secondary btn-xs">
                                        Inventariar
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $this->centrosCostos->links() }}
            </div>
        </div>
    </div>
</div>
