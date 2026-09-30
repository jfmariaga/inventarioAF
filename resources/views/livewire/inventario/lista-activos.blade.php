<?php

use App\Models\CentroCostos;
use App\Models\Inventario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public CentroCostos $centroCostos;

    public function mount(CentroCostos $centroCostos): void
    {
        $this->centroCostos = $centroCostos;
    }

    public function getInventarioAbiertoProperty(): ?Inventario
    {
        return Inventario::where('estado', 'abierto')->latest('fecha_apertura')->first();
    }

    public function getActivosProperty(): LengthAwarePaginator
    {
        $inventario = $this->inventarioAbierto;

        return $this->centroCostos->activos()
            ->with(['inventarioDetalles' => function ($query) use ($inventario) {
                if ($inventario) {
                    $query->where('inventario_id', $inventario->id);
                }
            }])
            ->orderBy('numero_activo')
            ->paginate(15);
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-ink-900">
            {{ $centroCostos->codigo }} — {{ $centroCostos->descripcion }}
        </h2>
    </x-slot>

    @if (! $this->inventarioAbierto)
        <div class="card card-pad border-amber-200 bg-amber-50 text-amber-800">
            No hay un periodo de inventario abierto actualmente. Contacta a un Administrador.
        </div>
    @else
        <div class="table-wrap card">
            <table class="dtable">
                <thead>
                    <tr>
                        <th>Activo</th>
                        <th>Denominación</th>
                        <th>Estado</th>
                        <th>Evidencia</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->activos as $activo)
                        @php($detalle = $activo->inventarioDetalles->first())
                        <tr>
                            <td class="font-medium text-ink-900">{{ $activo->numero_activo }}</td>
                            <td>{{ $activo->denominacion }}</td>
                            <td>
                                @php($estado = $detalle?->estado ?? 'pendiente')
                                <span @class([
                                    'badge',
                                    'badge-gray' => $estado === 'pendiente',
                                    'badge-green' => $estado === 'verificado',
                                    'badge-red' => $estado === 'no_encontrado',
                                ])>
                                    {{ ucfirst(str_replace('_', ' ', $estado)) }}
                                </span>
                            </td>
                            <td>
                                <div class="flex gap-1.5">
                                    @if ($detalle?->foto_equipo_path)
                                        <img
                                            src="{{ route('fotos.mostrar', [$detalle, 'equipo']) }}"
                                            alt="Foto del equipo {{ $activo->numero_activo }}"
                                            class="foto-thumb"
                                            onclick="verFoto('{{ route('fotos.mostrar', [$detalle, 'equipo']) }}', 'Equipo — {{ $activo->numero_activo }}')"
                                        />
                                    @endif
                                    @if ($detalle?->foto_placa_path)
                                        <img
                                            src="{{ route('fotos.mostrar', [$detalle, 'placa']) }}"
                                            alt="Foto de la placa {{ $activo->numero_activo }}"
                                            class="foto-thumb"
                                            onclick="verFoto('{{ route('fotos.mostrar', [$detalle, 'placa']) }}', 'Placa — {{ $activo->numero_activo }}')"
                                        />
                                    @endif
                                </div>
                            </td>
                            <td class="text-right">
                                <a href="{{ route('inventario.capturar', $activo) }}" wire:navigate class="btn btn-secondary btn-xs">
                                    Capturar
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $this->activos->links() }}
        </div>
    @endif
</div>
