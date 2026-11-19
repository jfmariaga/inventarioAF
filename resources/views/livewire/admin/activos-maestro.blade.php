<?php

use App\Models\Activo;
use App\Models\CentroCostos;
use App\Models\Empresa;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public string $busqueda = '';

    public ?int $filtroEmpresaId = null;

    public ?int $filtroCentroCostosId = null;

    public bool $mostrandoFormulario = false;

    public ?int $editandoId = null;

    public string $numeroActivo = '';

    public string $denominacion = '';

    public ?int $centroCostosId = null;

    public ?string $fechaCapitalizacion = null;

    public ?string $placa = null;

    public ?string $ubicacionOriginal = null;

    public function updatingBusqueda(): void
    {
        $this->resetPage();
    }

    public function updatingFiltroEmpresaId(): void
    {
        $this->filtroCentroCostosId = null;
        $this->resetPage();
    }

    public function updatingFiltroCentroCostosId(): void
    {
        $this->resetPage();
    }

    public function limpiarFiltros(): void
    {
        $this->reset(['busqueda', 'filtroEmpresaId', 'filtroCentroCostosId']);
        $this->resetPage();
    }

    public function getEmpresasProperty(): Collection
    {
        return Empresa::orderBy('nombre')->get();
    }

    public function getCentrosCostosProperty(): Collection
    {
        return CentroCostos::with('empresa')->orderBy('codigo')->get();
    }

    public function getCentrosCostosFiltroProperty(): Collection
    {
        return CentroCostos::query()
            ->when($this->filtroEmpresaId, fn ($query) => $query->where('empresa_id', $this->filtroEmpresaId))
            ->orderBy('codigo')
            ->get();
    }

    public function getActivosProperty(): LengthAwarePaginator
    {
        $termino = trim($this->busqueda);

        return Activo::with('centroCostos.empresa')
            ->when($termino !== '', function ($query) use ($termino) {
                $query->where(function ($query) use ($termino) {
                    $query->where('numero_activo', 'like', "%{$termino}%")
                        ->orWhere('denominacion', 'like', "%{$termino}%")
                        ->orWhere('placa', 'like', "%{$termino}%");
                });
            })
            ->when($this->filtroCentroCostosId, function ($query) {
                $query->where('centro_costos_id', $this->filtroCentroCostosId);
            })
            ->when(! $this->filtroCentroCostosId && $this->filtroEmpresaId, function ($query) {
                $query->whereHas('centroCostos', fn ($query) => $query->where('empresa_id', $this->filtroEmpresaId));
            })
            ->orderBy('numero_activo')
            ->paginate(15);
    }

    public function nuevo(): void
    {
        $this->resetFormulario();
        $this->mostrandoFormulario = true;
    }

    public function editar(int $activoId): void
    {
        $activo = Activo::findOrFail($activoId);

        $this->editandoId = $activo->id;
        $this->numeroActivo = $activo->numero_activo;
        $this->denominacion = $activo->denominacion;
        $this->centroCostosId = $activo->centro_costos_id;
        $this->fechaCapitalizacion = $activo->fecha_capitalizacion?->toDateString();
        $this->placa = $activo->placa;
        $this->ubicacionOriginal = $activo->ubicacion_original;
        $this->mostrandoFormulario = true;
    }

    public function guardar(): void
    {
        $validated = $this->validate([
            'numeroActivo' => [
                'required', 'string', 'max:30',
                Rule::unique('activos', 'numero_activo')->ignore($this->editandoId),
            ],
            'denominacion' => ['required', 'string', 'max:255'],
            'centroCostosId' => ['required', 'integer', 'exists:centros_costos,id'],
            'fechaCapitalizacion' => ['nullable', 'date'],
            'placa' => ['nullable', 'string', 'max:30'],
            'ubicacionOriginal' => ['nullable', 'string', 'max:100'],
        ]);

        $datos = [
            'numero_activo' => $validated['numeroActivo'],
            'denominacion' => $validated['denominacion'],
            'centro_costos_id' => $validated['centroCostosId'],
            'fecha_capitalizacion' => $validated['fechaCapitalizacion'],
            'placa' => $validated['placa'],
            'ubicacion_original' => $validated['ubicacionOriginal'],
        ];

        if ($this->editandoId) {
            Activo::findOrFail($this->editandoId)->update($datos);
            $this->dispatch('toast', icon: 'success', title: 'Activo actualizado correctamente.');
        } else {
            Activo::create($datos + [
                'origen' => 'sobrante',
                'creado_por' => auth()->id(),
            ]);
            $this->dispatch('toast', icon: 'success', title: 'Activo creado correctamente.');
        }

        $this->cancelar();
    }

    public function eliminar(int $activoId): void
    {
        Activo::findOrFail($activoId)->delete();

        $this->dispatch('toast', icon: 'success', title: 'Activo eliminado correctamente.');
    }

    public function cancelar(): void
    {
        $this->resetFormulario();
        $this->mostrandoFormulario = false;
    }

    private function resetFormulario(): void
    {
        $this->reset([
            'editandoId', 'numeroActivo', 'denominacion', 'centroCostosId',
            'fechaCapitalizacion', 'placa', 'ubicacionOriginal',
        ]);
        $this->resetErrorBag();
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-ink-900">
            {{ __('Maestro de activos fijos') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        @if ($mostrandoFormulario)
            <div class="card card-pad">
                <h3 class="mb-4 text-base font-semibold text-ink-900">
                    {{ $editandoId ? 'Editar activo' : 'Nuevo activo' }}
                </h3>

                <form wire:submit="guardar" class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="field-label">Número de activo</label>
                        <input type="text" wire:model="numeroActivo" class="input" />
                        <x-input-error :messages="$errors->get('numeroActivo')" class="mt-2" />
                    </div>

                    <div>
                        <label class="field-label">Placa</label>
                        <input type="text" wire:model="placa" class="input" />
                        <x-input-error :messages="$errors->get('placa')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="field-label">Denominación</label>
                        <input type="text" wire:model="denominacion" class="input" />
                        <x-input-error :messages="$errors->get('denominacion')" class="mt-2" />
                    </div>

                    <div>
                        <label class="field-label">Centro de costos</label>
                        <div wire:ignore wire:key="centro-costos-select-{{ $editandoId ?? 'nuevo' }}">
                            <select wire:model="centroCostosId" class="select" data-tom-select>
                                <option value="">Selecciona un centro de costos...</option>
                                @foreach ($this->centrosCostos as $centroCostos)
                                    <option value="{{ $centroCostos->id }}">
                                        {{ $centroCostos->codigo }} - {{ $centroCostos->descripcion }} ({{ $centroCostos->empresa->nombre }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <x-input-error :messages="$errors->get('centroCostosId')" class="mt-2" />
                    </div>

                    <div>
                        <label class="field-label">Fecha de capitalización</label>
                        <input type="date" wire:model="fechaCapitalizacion" class="input" />
                        <x-input-error :messages="$errors->get('fechaCapitalizacion')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2">
                        <label class="field-label">Ubicación original</label>
                        <input type="text" wire:model="ubicacionOriginal" class="input" />
                        <x-input-error :messages="$errors->get('ubicacionOriginal')" class="mt-2" />
                    </div>

                    <div class="sm:col-span-2 flex justify-end gap-3 pt-2">
                        <x-secondary-button type="button" wire:click="cancelar">Cancelar</x-secondary-button>
                        <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="guardar">Guardar</x-primary-button>
                    </div>
                </form>
            </div>
        @else
            <div class="card card-pad">
                <div class="flex flex-wrap items-end gap-4">
                    <div class="w-64">
                        <label class="field-label">Buscar</label>
                        <input
                            wire:model.live.debounce.300ms="busqueda"
                            type="text"
                            placeholder="Activo, denominación o placa..."
                            class="input"
                        />
                    </div>

                    <div class="w-48">
                        <label class="field-label">Empresa</label>
                        <select wire:model.live="filtroEmpresaId" class="select">
                            <option value="">Todas</option>
                            @foreach ($this->empresas as $empresa)
                                <option value="{{ $empresa->id }}">{{ $empresa->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="min-w-[16rem] flex-1">
                        <label class="field-label">Centro de costos</label>
                        <div wire:ignore wire:key="centro-costos-filtro-{{ $filtroEmpresaId ?? 'todas' }}">
                            <select wire:model="filtroCentroCostosId" class="select" data-tom-select>
                                <option value="">Todos</option>
                                @foreach ($this->centrosCostosFiltro as $centroCostos)
                                    <option value="{{ $centroCostos->id }}">{{ $centroCostos->codigo }} - {{ $centroCostos->descripcion }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <span class="field-label invisible block">Acciones</span>
                        <div class="flex gap-2">
                            <x-secondary-button type="button" wire:click="limpiarFiltros" class="btn-sm">
                                Limpiar
                            </x-secondary-button>

                            <x-primary-button type="button" wire:click="nuevo" class="btn-sm">
                                Nuevo activo
                            </x-primary-button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <div class="table-wrap card">
            <table class="dtable">
                <thead>
                    <tr>
                        <th>Número de activo</th>
                        <th>Denominación</th>
                        <th>Placa</th>
                        <th>Centro de costos</th>
                        <th>Capitalización</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->activos as $activo)
                        <tr>
                            <td class="font-medium text-ink-900">{{ $activo->numero_activo }}</td>
                            <td>{{ $activo->denominacion }}</td>
                            <td>{{ $activo->placa ?? '—' }}</td>
                            <td>{{ $activo->centroCostos->codigo }} - {{ $activo->centroCostos->empresa->nombre }}</td>
                            <td>{{ $activo->fecha_capitalizacion?->format('Y-m-d') ?? '—' }}</td>
                            <td class="flex justify-end gap-2 text-right">
                                <button type="button" wire:click="editar({{ $activo->id }})" class="btn btn-secondary btn-xs">
                                    Editar
                                </button>
                                <button
                                    type="button"
                                    wire:click="eliminar({{ $activo->id }})"
                                    wire:confirm="¿Eliminar este activo? También se eliminará su historial de inventario. Esta acción no se puede deshacer."
                                    class="btn btn-danger btn-xs"
                                >
                                    Eliminar
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div>
            {{ $this->activos->links() }}
        </div>
    </div>
</div>
