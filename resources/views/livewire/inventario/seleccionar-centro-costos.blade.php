<?php

use App\Models\Activo;
use App\Models\CentroCostos;
use App\Models\NovedadActivo;
use App\Models\Ubicacion;
use App\Services\ProcesadorFotoActivo;
use App\Support\Busqueda;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public string $busqueda = '';

    public string $busquedaActivo = '';

    public bool $mostrandoFormularioNovedad = false;

    public string $novedadDenominacion = '';

    public string $novedadIdentificador = '';

    public ?int $novedadUbicacionId = null;

    public ?string $novedadCondicion = null;

    public ?string $novedadMotivoDesuso = null;

    public $novedadFotoEquipo = null;

    public $novedadFotoPlaca = null;

    public ?string $novedadObservacion = null;

    public function getResultadosProperty(): Collection
    {
        if (trim($this->busqueda) === '') {
            return collect();
        }

        $termino = trim($this->busqueda);
        $terminoActivo = Busqueda::sinCerosIniciales($termino);

        return CentroCostos::with('empresa')
            ->where(function ($query) use ($termino, $terminoActivo) {
                $query->where('codigo', 'like', "%{$termino}%")
                    ->orWhere('descripcion', 'like', "%{$termino}%")
                    ->orWhereHas('activos', function ($query) use ($terminoActivo) {
                        $query->where('numero_activo', 'like', "%{$terminoActivo}%");
                    });
            })
            ->orderBy('codigo')
            ->limit(20)
            ->get();
    }

    public function getResultadosActivoProperty(): Collection
    {
        if (trim($this->busquedaActivo) === '') {
            return collect();
        }

        $termino = Busqueda::sinCerosIniciales(trim($this->busquedaActivo));

        return Activo::with('centroCostos')
            ->where(function ($query) use ($termino) {
                $query->where('numero_activo', 'like', "%{$termino}%")
                    ->orWhere('placa', 'like', "%{$termino}%");
            })
            ->orderBy('numero_activo')
            ->limit(20)
            ->get();
    }

    public function getOpcionesUbicacionProperty(): Collection
    {
        return Ubicacion::with('empresa')
            ->orderBy('empresa_id')
            ->orderBy('ubicacion')
            ->orderBy('detalle_ubicacion')
            ->get();
    }

    public function updatingBusquedaActivo(): void
    {
        $this->mostrandoFormularioNovedad = false;
    }

    public function updatedNovedadCondicion(): void
    {
        if ($this->novedadCondicion !== 'desuso') {
            $this->novedadMotivoDesuso = null;
        }
    }

    public function abrirFormularioNovedad(): void
    {
        $this->novedadDenominacion = '';
        $this->novedadIdentificador = trim($this->busquedaActivo);
        $this->novedadUbicacionId = null;
        $this->novedadCondicion = null;
        $this->novedadMotivoDesuso = null;
        $this->novedadFotoEquipo = null;
        $this->novedadFotoPlaca = null;
        $this->novedadObservacion = null;
        $this->resetErrorBag();
        $this->mostrandoFormularioNovedad = true;
    }

    public function cancelarNovedad(): void
    {
        $this->mostrandoFormularioNovedad = false;
    }

    public function guardarNovedad(ProcesadorFotoActivo $procesador): void
    {
        $validated = $this->validate([
            'novedadDenominacion' => ['required', 'string', 'max:255'],
            'novedadIdentificador' => ['required', 'string', 'max:30'],
            'novedadUbicacionId' => ['required', 'integer', 'exists:ubicaciones,id'],
            'novedadCondicion' => ['required', 'in:uso,desuso'],
            'novedadMotivoDesuso' => ['required_if:novedadCondicion,desuso', 'nullable', 'string', 'max:2000'],
            'novedadFotoEquipo' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx', 'max:10240'],
            'novedadFotoPlaca' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx', 'max:10240'],
            'novedadObservacion' => ['nullable', 'string', 'max:2000'],
        ]);

        $ubicacion = Ubicacion::findOrFail($validated['novedadUbicacionId']);

        $novedad = NovedadActivo::create([
            'denominacion' => $validated['novedadDenominacion'],
            'numero_activo' => $validated['novedadIdentificador'],
            'ubicacion_id' => $ubicacion->id,
            'ubicacion_original' => $ubicacion->etiqueta,
            'condicion' => $validated['novedadCondicion'],
            'motivo_desuso' => $validated['novedadCondicion'] === 'desuso' ? $validated['novedadMotivoDesuso'] : null,
            'observacion' => $validated['novedadObservacion'],
            'creado_por' => auth()->id(),
        ]);

        $carpeta = "novedades/{$novedad->id}";

        $novedad->update([
            'foto_equipo_path' => $procesador->guardar($this->novedadFotoEquipo, $carpeta, 'equipo'),
            'foto_placa_path' => $procesador->guardar($this->novedadFotoPlaca, $carpeta, 'placa'),
        ]);

        $this->mostrandoFormularioNovedad = false;
        $this->busquedaActivo = '';

        $this->dispatch('toast', icon: 'success', title: 'Novedad registrada. Un administrador completará el registro.');
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-ink-900">
            {{ __('Inventariar') }}
        </h2>
    </x-slot>

    <div class="mx-auto max-w-2xl">
        @unless ($mostrandoFormularioNovedad)
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
        @endunless

        <div class="card card-pad mt-4">
            <label for="busquedaActivo" class="field-label">
                O digita el número de activo o placa
            </label>
            <input
                wire:model.live.debounce.300ms="busquedaActivo"
                id="busquedaActivo"
                type="text"
                placeholder="Número de activo o placa..."
                class="input"
            />

            <ul class="mt-4 divide-y divide-ink-100">
                @forelse ($this->resultadosActivo as $activo)
                    <li>
                        <a
                            href="{{ route('inventario.capturar', $activo) }}"
                            wire:navigate
                            class="flex items-center justify-between rounded-lg px-2 py-2.5 hover:bg-ink-50"
                        >
                            <span>
                                <span class="font-medium text-ink-900">{{ $activo->numero_activo }}</span>
                                @if ($activo->placa)
                                    <span class="text-ink-500">— Placa {{ $activo->placa }}</span>
                                @endif
                                <span class="block text-sm text-ink-500">{{ $activo->denominacion }}</span>
                            </span>
                            <span class="badge badge-brand">{{ $activo->centroCostos?->codigo }}</span>
                        </a>
                    </li>
                @empty
                    @if (trim($busquedaActivo) !== '')
                        <li class="px-2 py-4">
                            <div class="flex flex-col items-center gap-3 text-center sm:flex-row sm:justify-between sm:text-left">
                                <span class="text-sm text-ink-500">No se encontraron activos para "{{ trim($busquedaActivo) }}".</span>
                                @if (! $mostrandoFormularioNovedad)
                                    <x-primary-button type="button" wire:click="abrirFormularioNovedad" class="w-full flex-none sm:w-auto">
                                        Registrar novedad
                                    </x-primary-button>
                                @endif
                            </div>
                        </li>
                    @endif
                @endforelse
            </ul>

            @if ($mostrandoFormularioNovedad)
                <form wire:submit="guardarNovedad" class="mt-4 space-y-4 rounded-lg border border-ink-100 p-4">
                    <p class="text-sm text-ink-500">
                        El activo no está registrado. Registra lo que conoces; un administrador completará el resto
                        (centro de costos, fecha de capitalización, etc.) y quedará inventariado.
                    </p>

                    <div>
                        <label class="field-label">Descripción</label>
                        <input type="text" wire:model="novedadDenominacion" class="input" autofocus />
                        <x-input-error :messages="$errors->get('novedadDenominacion')" class="mt-2" />
                    </div>

                    <div>
                        <label class="field-label">Placa o número de activo</label>
                        <input type="text" wire:model="novedadIdentificador" class="input" />
                        <x-input-error :messages="$errors->get('novedadIdentificador')" class="mt-2" />
                    </div>

                    <div>
                        <label class="field-label">Ubicación</label>
                        <div wire:ignore wire:key="novedad-ubicacion-select">
                            <select wire:model="novedadUbicacionId" class="select" data-tom-select>
                                <option value="">Selecciona una ubicación...</option>
                                @foreach ($this->opcionesUbicacion as $opcion)
                                    <option value="{{ $opcion->id }}">{{ $opcion->empresa->nombre }} — {{ $opcion->etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                        <x-input-error :messages="$errors->get('novedadUbicacionId')" class="mt-2" />
                    </div>

                    <div>
                        <label class="field-label">Condición</label>
                        <select wire:model.live="novedadCondicion" class="select">
                            <option value="">Selecciona una condición...</option>
                            <option value="uso">Uso</option>
                            <option value="desuso">Desuso</option>
                        </select>
                        <x-input-error :messages="$errors->get('novedadCondicion')" class="mt-2" />
                    </div>

                    @if ($novedadCondicion === 'desuso')
                        <div>
                            <label class="field-label">Motivo del desuso</label>
                            <textarea wire:model="novedadMotivoDesuso" rows="3" class="textarea"></textarea>
                            <x-input-error :messages="$errors->get('novedadMotivoDesuso')" class="mt-2" />
                        </div>
                    @endif

                    <div>
                        <label class="field-label" for="novedadFotoEquipo">Foto del equipo (obligatoria)</label>
                        <div class="flex items-center gap-2">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="17 8 12 3 7 8" /><line x1="12" y1="3" x2="12" y2="15" />
                                </svg>
                            </span>
                            <input id="novedadFotoEquipo" type="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" wire:model="novedadFotoEquipo" class="block w-full text-sm text-ink-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100" />
                        </div>
                        <x-input-error :messages="$errors->get('novedadFotoEquipo')" class="mt-2" />

                        <div wire:loading wire:target="novedadFotoEquipo" class="mt-2 flex items-center gap-1.5 text-xs text-ink-400">
                            <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <circle class="opacity-25" cx="12" cy="12" r="10" /><path class="opacity-75" d="M22 12a10 10 0 0 0-10-10" />
                            </svg>
                            Cargando imagen…
                        </div>

                        @if ($novedadFotoEquipo && $novedadFotoEquipo->isPreviewable())
                            <img src="{{ $novedadFotoEquipo->temporaryUrl() }}" class="mt-2 h-32 w-32 rounded-lg border border-ink-200 object-cover" alt="Previsualización foto del equipo" />
                        @elseif ($novedadFotoEquipo)
                            <p class="mt-2 text-xs text-ink-500">Archivo seleccionado: {{ $novedadFotoEquipo->getClientOriginalName() }}</p>
                        @endif
                    </div>

                    <div>
                        <label class="field-label" for="novedadFotoPlaca">Foto de la placa (obligatoria)</label>
                        <p class="mb-2 text-xs text-ink-500">Si el activo no tiene placa, tomar foto de la serie, modelo o marca.</p>

                        <div class="flex items-center gap-2">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="17 8 12 3 7 8" /><line x1="12" y1="3" x2="12" y2="15" />
                                </svg>
                            </span>
                            <input id="novedadFotoPlaca" type="file" accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx" wire:model="novedadFotoPlaca" class="block w-full text-sm text-ink-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100" />
                        </div>
                        <x-input-error :messages="$errors->get('novedadFotoPlaca')" class="mt-2" />

                        <div wire:loading wire:target="novedadFotoPlaca" class="mt-2 flex items-center gap-1.5 text-xs text-ink-400">
                            <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                <circle class="opacity-25" cx="12" cy="12" r="10" /><path class="opacity-75" d="M22 12a10 10 0 0 0-10-10" />
                            </svg>
                            Cargando imagen…
                        </div>

                        @if ($novedadFotoPlaca && $novedadFotoPlaca->isPreviewable())
                            <img src="{{ $novedadFotoPlaca->temporaryUrl() }}" class="mt-2 h-32 w-32 rounded-lg border border-ink-200 object-cover" alt="Previsualización foto de la placa" />
                        @elseif ($novedadFotoPlaca)
                            <p class="mt-2 text-xs text-ink-500">Archivo seleccionado: {{ $novedadFotoPlaca->getClientOriginalName() }}</p>
                        @endif
                    </div>

                    <div>
                        <label class="field-label">Observación (opcional)</label>
                        <textarea wire:model="novedadObservacion" rows="3" class="textarea"></textarea>
                        <x-input-error :messages="$errors->get('novedadObservacion')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3">
                        <x-secondary-button type="button" wire:click="cancelarNovedad">Cancelar</x-secondary-button>
                        <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="guardarNovedad">
                            Guardar novedad
                        </x-primary-button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
