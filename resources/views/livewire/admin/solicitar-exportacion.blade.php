<?php

use App\Jobs\GenerarExportacionInventario;
use App\Models\CentroCostos;
use App\Models\Empresa;
use App\Models\Exportacion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public ?int $empresaId = null;

    public ?int $centroCostosId = null;

    public function getEmpresasProperty(): Collection
    {
        return Empresa::orderBy('nombre')->get();
    }

    public function getCentrosCostosProperty(): Collection
    {
        return CentroCostos::when($this->empresaId, fn ($query) => $query->where('empresa_id', $this->empresaId))
            ->orderBy('codigo')
            ->get();
    }

    public function getExportacionesProperty(): LengthAwarePaginator
    {
        return Exportacion::where('solicitado_por', auth()->id())
            ->latest()
            ->paginate(10);
    }

    public function solicitar(): void
    {
        $exportacion = Exportacion::create([
            'solicitado_por' => auth()->id(),
            'empresa_id' => $this->empresaId,
            'centro_costos_id' => $this->centroCostosId,
            'estado' => 'en_proceso',
        ]);

        GenerarExportacionInventario::dispatch($exportacion->id);

        $this->dispatch('toast', icon: 'success', title: 'Exportación solicitada. Te avisaremos cuando esté lista.');
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-ink-900">
            {{ __('Exportar inventario') }}
        </h2>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-8">
        <div class="card card-pad">
            <form wire:submit="solicitar" class="space-y-4">
                <div>
                    <label class="field-label">Empresa (opcional)</label>
                    <select wire:model.live="empresaId" class="select">
                        <option value="">Todas</option>
                        @foreach ($this->empresas as $empresa)
                            <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="field-label">Centro de costos (opcional)</label>
                    <select wire:model="centroCostosId" class="select">
                        <option value="">Todos</option>
                        @foreach ($this->centrosCostos as $centroCostos)
                            <option value="{{ $centroCostos->id }}">{{ $centroCostos->codigo }} — {{ $centroCostos->descripcion }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end">
                    <x-primary-button type="submit">Solicitar exportación</x-primary-button>
                </div>
            </form>
        </div>

        <div>
            <h3 class="mb-2 text-base font-semibold text-ink-900">Historial</h3>
            <div class="table-wrap card">
                <table class="dtable">
                    <thead>
                        <tr>
                            <th>Solicitada</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($this->exportaciones as $exportacion)
                            <tr wire:poll.5s>
                                <td class="text-ink-900">{{ $exportacion->created_at->format('Y-m-d H:i') }}</td>
                                <td>
                                    <span @class([
                                        'badge',
                                        'badge-amber' => $exportacion->estado === 'en_proceso',
                                        'badge-green' => $exportacion->estado === 'lista',
                                        'badge-red' => $exportacion->estado === 'fallida',
                                    ])>
                                        {{ ucfirst(str_replace('_', ' ', $exportacion->estado)) }}
                                    </span>
                                </td>
                                <td class="text-right">
                                    @if ($exportacion->estaLista())
                                        <a href="{{ route('exportaciones.descargar', $exportacion) }}" class="btn btn-secondary btn-xs">
                                            Descargar
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                {{ $this->exportaciones->links() }}
            </div>
        </div>
    </div>
</div>
