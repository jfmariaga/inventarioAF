<?php

use App\Livewire\Forms\CapturaActivoForm;
use App\Models\Activo;
use App\Models\Inventario;
use App\Models\InventarioDetalle;
use App\Services\ProcesadorFotoActivo;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('layouts.app')] class extends Component
{
    use WithFileUploads;

    public Activo $activo;

    public ?InventarioDetalle $detalle = null;

    public bool $reservaAdquirida = false;

    public ?string $reservadoPorNombre = null;

    public CapturaActivoForm $form;

    public function mount(Activo $activo): void
    {
        $this->activo = $activo;

        $inventario = Inventario::where('estado', 'abierto')->latest('fecha_apertura')->first();

        abort_unless($inventario, 404, 'No hay un periodo de inventario abierto.');

        $this->detalle = InventarioDetalle::firstOrCreate(
            ['inventario_id' => $inventario->id, 'activo_id' => $this->activo->id],
            ['estado' => 'pendiente']
        );

        $minutosExpiracion = (int) config('inventario.reserva_activo_minutos');

        if ($this->detalle->reservadaPorOtro(auth()->id(), $minutosExpiracion)) {
            $this->reservaAdquirida = false;
            $this->reservadoPorNombre = $this->detalle->reservadoPor?->name;

            return;
        }

        $this->reservaAdquirida = $this->detalle->adquirirReserva(auth()->id(), $minutosExpiracion);

        if (! $this->reservaAdquirida) {
            $this->reservadoPorNombre = $this->detalle->reservadoPor?->name;

            return;
        }

        $this->form->estado = in_array($this->detalle->estado, ['verificado', 'no_encontrado'], true)
            ? $this->detalle->estado
            : 'verificado';
        $this->form->ubicacion = $this->detalle->ubicacion;
        $this->form->observacion = $this->detalle->observacion;
        $this->form->yaTieneFotoEquipo = (bool) $this->detalle->foto_equipo_path;
        $this->form->yaTieneFotoPlaca = (bool) $this->detalle->foto_placa_path;
    }

    public function guardar(ProcesadorFotoActivo $procesador): void
    {
        $this->form->validate();

        $rutaFotoEquipo = $this->detalle->foto_equipo_path;
        $rutaFotoEquipoAnterior = $this->detalle->foto_equipo_path_anterior;
        $rutaFotoPlaca = $this->detalle->foto_placa_path;
        $rutaFotoPlacaAnterior = $this->detalle->foto_placa_path_anterior;
        $fotosReemplazadasEn = $this->detalle->fotos_reemplazadas_en;

        if ($this->form->fotoEquipo) {
            if ($rutaFotoEquipo) {
                [$rutaFotoEquipo, $rutaFotoEquipoAnterior] = $procesador->reemplazar(
                    $this->form->fotoEquipo, $this->activo->id, 'equipo', $rutaFotoEquipo
                );
                $fotosReemplazadasEn = now();
            } else {
                $rutaFotoEquipo = $procesador->guardar($this->form->fotoEquipo, $this->activo->id, 'equipo');
            }
        }

        if ($this->form->fotoPlaca) {
            if ($rutaFotoPlaca) {
                [$rutaFotoPlaca, $rutaFotoPlacaAnterior] = $procesador->reemplazar(
                    $this->form->fotoPlaca, $this->activo->id, 'placa', $rutaFotoPlaca
                );
                $fotosReemplazadasEn = now();
            } else {
                $rutaFotoPlaca = $procesador->guardar($this->form->fotoPlaca, $this->activo->id, 'placa');
            }
        }

        $this->detalle->update([
            'estado' => $this->form->estado,
            'ubicacion' => $this->form->ubicacion,
            'observacion' => $this->form->observacion,
            'foto_equipo_path' => $rutaFotoEquipo,
            'foto_equipo_path_anterior' => $rutaFotoEquipoAnterior,
            'foto_placa_path' => $rutaFotoPlaca,
            'foto_placa_path_anterior' => $rutaFotoPlacaAnterior,
            'fotos_reemplazadas_en' => $fotosReemplazadasEn,
            'actualizado_por' => auth()->id(),
            'reservado_por' => null,
            'reservado_en' => null,
        ]);

        session()->flash('status', 'Activo capturado correctamente.');

        $this->redirect(route('inventario.centro-costos', $this->activo->centro_costos_id), navigate: true);
    }

    public function cancelar(): void
    {
        if ($this->reservaAdquirida && $this->detalle) {
            $this->detalle->liberarReserva();
        }

        $this->redirect(route('inventario.centro-costos', $this->activo->centro_costos_id), navigate: true);
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-ink-900">
            Capturar activo {{ $activo->numero_activo }}
        </h2>
    </x-slot>

    <div class="mx-auto max-w-2xl">
        <div class="card card-pad">
            <p class="mb-4 text-sm text-ink-500">{{ $activo->denominacion }}</p>

            @if (! $reservaAdquirida)
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    Este activo está en gestión por <strong>{{ $reservadoPorNombre ?? 'otro usuario' }}</strong> en este momento. Intenta de nuevo en unos minutos.
                </div>
            @else
                <form wire:submit="guardar" class="space-y-4">
                    <div>
                        <label class="field-label">Estado</label>
                        <select wire:model.live="form.estado" class="select">
                            <option value="verificado">Verificado</option>
                            <option value="no_encontrado">No encontrado</option>
                        </select>
                    </div>

                    @if ($form->estado === 'verificado')
                        <div>
                            <label class="field-label">Ubicación</label>
                            <input type="text" wire:model="form.ubicacion" class="input" />
                            <x-input-error :messages="$errors->get('form.ubicacion')" class="mt-2" />
                        </div>

                        <div>
                            <label class="field-label" for="fotoEquipo">Foto del equipo (obligatoria)</label>

                            <div class="flex items-center gap-2">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="17 8 12 3 7 8" /><line x1="12" y1="3" x2="12" y2="15" />
                                    </svg>
                                </span>
                                <input id="fotoEquipo" type="file" accept="image/*" wire:model="form.fotoEquipo" class="block w-full text-sm text-ink-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100" />
                            </div>

                            <x-input-error :messages="$errors->get('form.fotoEquipo')" class="mt-2" />

                            <div wire:loading wire:target="form.fotoEquipo" class="mt-2 flex items-center gap-1.5 text-xs text-ink-400">
                                <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" /><path class="opacity-75" d="M22 12a10 10 0 0 0-10-10" />
                                </svg>
                                Cargando imagen…
                            </div>

                            @if ($form->fotoEquipo)
                                <img src="{{ $form->fotoEquipo->temporaryUrl() }}" class="mt-2 h-32 w-32 rounded-lg border border-ink-200 object-cover" alt="Previsualización foto del equipo" />
                            @elseif ($form->yaTieneFotoEquipo)
                                <img src="{{ route('fotos.mostrar', [$detalle, 'equipo']) }}" class="mt-2 h-32 w-32 rounded-lg border border-ink-200 object-cover cursor-zoom-in" alt="Foto del equipo guardada" onclick="verFoto('{{ route('fotos.mostrar', [$detalle, 'equipo']) }}', 'Foto del equipo')" />
                                <p class="mt-1 text-xs text-ink-400">Ya hay una foto guardada; sube una nueva solo si quieres reemplazarla.</p>
                            @endif
                        </div>

                        <div>
                            <label class="field-label" for="fotoPlaca">Foto de la placa</label>

                            <div class="flex items-center gap-2">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-700">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" /><polyline points="17 8 12 3 7 8" /><line x1="12" y1="3" x2="12" y2="15" />
                                    </svg>
                                </span>
                                <input id="fotoPlaca" type="file" accept="image/*" wire:model="form.fotoPlaca" class="block w-full text-sm text-ink-600 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100" />
                            </div>

                            <x-input-error :messages="$errors->get('form.fotoPlaca')" class="mt-2" />

                            <div wire:loading wire:target="form.fotoPlaca" class="mt-2 flex items-center gap-1.5 text-xs text-ink-400">
                                <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" /><path class="opacity-75" d="M22 12a10 10 0 0 0-10-10" />
                                </svg>
                                Cargando imagen…
                            </div>

                            @if ($form->fotoPlaca)
                                <img src="{{ $form->fotoPlaca->temporaryUrl() }}" class="mt-2 h-32 w-32 rounded-lg border border-ink-200 object-cover" alt="Previsualización foto de la placa" />
                            @elseif ($form->yaTieneFotoPlaca)
                                <img src="{{ route('fotos.mostrar', [$detalle, 'placa']) }}" class="mt-2 h-32 w-32 rounded-lg border border-ink-200 object-cover cursor-zoom-in" alt="Foto de la placa guardada" onclick="verFoto('{{ route('fotos.mostrar', [$detalle, 'placa']) }}', 'Foto de la placa')" />
                                <p class="mt-1 text-xs text-ink-400">Ya hay una foto guardada; sube una nueva solo si quieres reemplazarla.</p>
                            @endif
                        </div>
                    @endif

                    <div>
                        <label class="field-label">
                            Observación @if ($form->estado === 'no_encontrado') (obligatoria) @else (opcional) @endif
                        </label>
                        <textarea wire:model="form.observacion" rows="3" class="textarea"></textarea>
                        <x-input-error :messages="$errors->get('form.observacion')" class="mt-2" />
                    </div>

                    <div class="flex justify-end gap-3 pt-2">
                        <x-secondary-button type="button" wire:click="cancelar">Cancelar</x-secondary-button>
                        <x-primary-button type="submit" wire:loading.attr="disabled" wire:target="guardar">Guardar</x-primary-button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
