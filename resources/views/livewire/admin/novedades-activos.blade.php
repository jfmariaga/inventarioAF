<?php

use App\Models\Activo;
use App\Models\CentroCostos;
use App\Models\Inventario;
use App\Models\NovedadActivo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public string $filtroEstado = 'pendiente';

    public ?int $completandoId = null;

    public string $denominacion = '';

    public string $numeroActivo = '';

    public ?string $placa = null;

    public string $ubicacionOriginal = '';

    public ?int $centroCostosId = null;

    public ?string $fechaCapitalizacion = null;

    public ?NovedadActivo $novedadEnEdicion = null;

    public function updatingFiltroEstado(): void
    {
        $this->resetPage();
    }

    public function getCentrosCostosProperty(): Collection
    {
        return CentroCostos::with('empresa')->orderBy('codigo')->get();
    }

    public function getNovedadesProperty(): LengthAwarePaginator
    {
        return NovedadActivo::with(['centroCostos.empresa', 'creadoPor'])
            ->where('estado', $this->filtroEstado)
            ->latest()
            ->paginate(15);
    }

    public function completar(int $novedadId): void
    {
        $novedad = NovedadActivo::findOrFail($novedadId);

        $this->completandoId = $novedad->id;
        $this->novedadEnEdicion = $novedad;
        $this->denominacion = $novedad->denominacion;
        $this->numeroActivo = $novedad->numero_activo ?? '';
        $this->placa = $novedad->placa;
        $this->ubicacionOriginal = $novedad->ubicacion_original;
        $this->centroCostosId = $novedad->centro_costos_id;
        $this->fechaCapitalizacion = $novedad->fecha_capitalizacion?->toDateString();
        $this->resetErrorBag();
    }

    public function cancelar(): void
    {
        $this->reset([
            'completandoId', 'novedadEnEdicion', 'denominacion', 'numeroActivo', 'placa',
            'ubicacionOriginal', 'centroCostosId', 'fechaCapitalizacion',
        ]);
        $this->resetErrorBag();
    }

    public function guardarCompletado(): void
    {
        $novedad = NovedadActivo::findOrFail($this->completandoId);

        $validated = $this->validate([
            'denominacion' => ['required', 'string', 'max:255'],
            'numeroActivo' => [
                'required', 'string', 'max:30',
                Rule::unique('activos', 'numero_activo'),
            ],
            'placa' => ['nullable', 'string', 'max:30'],
            'ubicacionOriginal' => ['required', 'string', 'max:100'],
            'centroCostosId' => ['required', 'integer', 'exists:centros_costos,id'],
            'fechaCapitalizacion' => ['nullable', 'date'],
        ]);

        $activo = Activo::create([
            'numero_activo' => $validated['numeroActivo'],
            'denominacion' => $validated['denominacion'],
            'fecha_capitalizacion' => $validated['fechaCapitalizacion'],
            'placa' => $validated['placa'],
            'ubicacion_original' => $validated['ubicacionOriginal'],
            'centro_costos_id' => $validated['centroCostosId'],
            'origen' => 'sobrante',
            'creado_por' => $novedad->creado_por,
        ]);

        $inventarioAbierto = Inventario::where('estado', 'abierto')->latest('fecha_apertura')->first();

        if ($inventarioAbierto) {
            $activo->inventarioDetalles()->create([
                'inventario_id' => $inventarioAbierto->id,
                'estado' => 'sobrante',
                'ubicacion' => $validated['ubicacionOriginal'],
                'ubicacion_id' => $novedad->ubicacion_id,
                'condicion' => $novedad->condicion,
                'motivo_desuso' => $novedad->motivo_desuso,
                'foto_equipo_path' => $novedad->foto_equipo_path,
                'foto_placa_path' => $novedad->foto_placa_path,
                'observacion' => $novedad->observacion ?? 'Registrado como novedad de inventario.',
                'actualizado_por' => auth()->id(),
            ]);
        }

        $novedad->update([
            'estado' => 'completada',
            'activo_id' => $activo->id,
            'completada_por' => auth()->id(),
            'completada_en' => now(),
        ]);

        $this->dispatch('toast', icon: 'success', title: 'Novedad completada. El activo quedó inventariado en el centro de costos.');

        $this->cancelar();
    }

    public function eliminar(int $novedadId): void
    {
        NovedadActivo::findOrFail($novedadId)->delete();

        $this->dispatch('toast', icon: 'success', title: 'Novedad eliminada.');
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-ink-900">
            {{ __('Novedades de inventario') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        @if ($completandoId)
            <div class="card card-pad">
                <h3 class="mb-4 text-base font-semibold text-ink-900">Completar novedad</h3>

                <div class="mb-6 grid gap-4 rounded-lg border border-ink-100 bg-ink-50/50 p-4 sm:grid-cols-2">
                    <div>
                        <p class="field-label">Condición reportada</p>
                        <p class="text-sm text-ink-700">{{ $novedadEnEdicion?->condicion ? ucfirst($novedadEnEdicion->condicion) : '—' }}</p>
                        @if ($novedadEnEdicion?->motivo_desuso)
                            <p class="mt-1 text-xs text-ink-500">{{ $novedadEnEdicion->motivo_desuso }}</p>
                        @endif
                    </div>

                    <div>
                        <p class="field-label">Observación del inventariador</p>
                        <p class="text-sm text-ink-700">{{ $novedadEnEdicion?->observacion ?? '—' }}</p>
                    </div>

                    <div>
                        <p class="field-label">Foto del equipo</p>
                        @if ($novedadEnEdicion?->foto_equipo_path)
                            @if (\Illuminate\Support\Str::endsWith($novedadEnEdicion->foto_equipo_path, ['.jpg', '.jpeg', '.png']))
                                <img
                                    src="{{ route('fotos.novedad.mostrar', [$novedadEnEdicion, 'equipo']) }}"
                                    alt="Foto del equipo"
                                    class="foto-thumb mt-1"
                                    onclick="verFoto('{{ route('fotos.novedad.mostrar', [$novedadEnEdicion, 'equipo']) }}', 'Equipo — novedad')"
                                />
                            @else
                                <a href="{{ route('fotos.novedad.mostrar', [$novedadEnEdicion, 'equipo']) }}" target="_blank" class="mt-1 inline-block text-xs font-medium text-brand-700 underline">Ver archivo guardado</a>
                            @endif
                        @else
                            <p class="text-sm text-ink-400">Sin foto</p>
                        @endif
                    </div>

                    <div>
                        <p class="field-label">Foto de la placa</p>
                        @if ($novedadEnEdicion?->foto_placa_path)
                            @if (\Illuminate\Support\Str::endsWith($novedadEnEdicion->foto_placa_path, ['.jpg', '.jpeg', '.png']))
                                <img
                                    src="{{ route('fotos.novedad.mostrar', [$novedadEnEdicion, 'placa']) }}"
                                    alt="Foto de la placa"
                                    class="foto-thumb mt-1"
                                    onclick="verFoto('{{ route('fotos.novedad.mostrar', [$novedadEnEdicion, 'placa']) }}', 'Placa — novedad')"
                                />
                            @else
                                <a href="{{ route('fotos.novedad.mostrar', [$novedadEnEdicion, 'placa']) }}" target="_blank" class="mt-1 inline-block text-xs font-medium text-brand-700 underline">Ver archivo guardado</a>
                            @endif
                        @else
                            <p class="text-sm text-ink-400">Sin foto</p>
                        @endif
                    </div>
                </div>

                <form wire:submit="guardarCompletado" class="grid gap-4 sm:grid-cols-2">
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
                        <label class="field-label">Descripción</label>
                        <input type="text" wire:model="denominacion" class="input" />
                        <x-input-error :messages="$errors->get('denominacion')" class="mt-2" />
                    </div>

                    <div>
                        <label class="field-label">Centro de costos</label>
                        <div wire:ignore wire:key="centro-costos-select-{{ $completandoId }}">
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
                        <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="guardarCompletado">
                            Completar e inventariar
                        </x-primary-button>
                    </div>
                </form>
            </div>
        @else
            <div class="card card-pad">
                <div class="flex items-end gap-4">
                    <div class="w-64">
                        <label class="field-label">Estado</label>
                        <select wire:model.live="filtroEstado" class="select">
                            <option value="pendiente">Pendientes</option>
                            <option value="completada">Completadas</option>
                        </select>
                    </div>
                </div>
            </div>
        @endif

        <div class="table-wrap card">
            <table class="dtable">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th>Placa / Activo</th>
                        <th>Ubicación original</th>
                        <th>Centro de costos</th>
                        <th>Registrada por</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->novedades as $novedad)
                        <tr>
                            <td class="font-medium text-ink-900">{{ $novedad->denominacion }}</td>
                            <td>{{ $novedad->numero_activo ?? $novedad->placa ?? '—' }}</td>
                            <td>{{ $novedad->ubicacion_original }}</td>
                            <td>{{ $novedad->centroCostos ? "{$novedad->centroCostos->codigo} - {$novedad->centroCostos->empresa->nombre}" : '—' }}</td>
                            <td>{{ $novedad->creadoPor?->name ?? '—' }}</td>
                            <td class="flex justify-end gap-2 text-right">
                                @if ($novedad->estaPendiente())
                                    <button type="button" wire:click="completar({{ $novedad->id }})" class="btn btn-secondary btn-xs">
                                        Completar
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="eliminar({{ $novedad->id }})"
                                        wire:confirm="¿Eliminar esta novedad? Esta acción no se puede deshacer."
                                        class="btn btn-danger btn-xs"
                                    >
                                        Eliminar
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-6 text-center text-ink-400">
                                No hay novedades {{ $filtroEstado === 'pendiente' ? 'pendientes' : 'completadas' }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div>
            {{ $this->novedades->links() }}
        </div>
    </div>
</div>
