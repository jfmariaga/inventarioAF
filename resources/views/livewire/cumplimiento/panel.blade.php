<?php

use App\Models\Activo;
use App\Models\Inventario;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public ?int $centroCostosExpandido = null;

    public function getInventarioAbiertoProperty(): ?Inventario
    {
        return Inventario::where('estado', 'abierto')->latest('fecha_apertura')->first();
    }

    private function consultaPorCentro()
    {
        $inventario = $this->inventarioAbierto;

        return DB::table('centros_costos')
            ->join('empresas', 'empresas.id', '=', 'centros_costos.empresa_id')
            ->leftJoin('activos', 'activos.centro_costos_id', '=', 'centros_costos.id')
            ->leftJoin('inventario_detalles', function ($join) use ($inventario) {
                $join->on('inventario_detalles.activo_id', '=', 'activos.id');

                if ($inventario) {
                    $join->where('inventario_detalles.inventario_id', $inventario->id);
                } else {
                    $join->whereRaw('1 = 0');
                }
            })
            ->select(
                'centros_costos.id as centro_costos_id',
                'centros_costos.codigo',
                'centros_costos.descripcion',
                'empresas.id as empresa_id',
                'empresas.nombre as empresa_nombre'
            )
            ->selectRaw('COUNT(DISTINCT activos.id) as total_activos')
            ->selectRaw("COUNT(DISTINCT CASE WHEN inventario_detalles.estado = 'verificado' THEN activos.id END) as verificados")
            ->selectRaw("COUNT(DISTINCT CASE WHEN inventario_detalles.estado = 'no_encontrado' THEN activos.id END) as no_encontrados")
            ->selectRaw("COUNT(DISTINCT CASE WHEN inventario_detalles.estado IN ('verificado','no_encontrado') THEN activos.id END) as activos_inventariados")
            ->groupBy('centros_costos.id', 'centros_costos.codigo', 'centros_costos.descripcion', 'empresas.id', 'empresas.nombre')
            ->orderBy('empresas.nombre')
            ->orderBy('centros_costos.codigo');
    }

    /** Todas las filas (sin paginar), usadas para KPIs y gráficas. */
    public function getTodasLasFilasProperty(): Collection
    {
        return collect($this->consultaPorCentro()->get());
    }

    public function getFilasPorCentroProperty(): LengthAwarePaginator
    {
        return $this->consultaPorCentro()->paginate(20);
    }

    public function getFilasPorEmpresaProperty(): Collection
    {
        return $this->todasLasFilas
            ->groupBy('empresa_nombre')
            ->map(function (Collection $filas, string $nombreEmpresa) {
                return (object) [
                    'empresa_nombre' => $nombreEmpresa,
                    'total_activos' => $filas->sum('total_activos'),
                    'activos_inventariados' => $filas->sum('activos_inventariados'),
                ];
            })
            ->values();
    }

    public function getKpisProperty(): array
    {
        $filas = $this->todasLasFilas;

        $totalActivos = (int) $filas->sum('total_activos');
        $verificados = (int) $filas->sum('verificados');
        $noEncontrados = (int) $filas->sum('no_encontrados');
        $inventariados = $verificados + $noEncontrados;
        $pendientes = $totalActivos - $inventariados;
        $centrosSinIniciar = $filas->filter(fn ($f) => $f->total_activos > 0 && $f->activos_inventariados === 0)->count();

        return [
            'total_activos' => $totalActivos,
            'verificados' => $verificados,
            'no_encontrados' => $noEncontrados,
            'pendientes' => max($pendientes, 0),
            'porcentaje_global' => $this->porcentaje($inventariados, $totalActivos),
            'centros_sin_iniciar' => $centrosSinIniciar,
        ];
    }

    /** Payload para Chart.js: distribución de estados (dona). */
    public function getGraficaEstadosProperty(): array
    {
        $kpis = $this->kpis;

        return [
            'data' => [
                'labels' => ['Verificados', 'No encontrados', 'Pendientes'],
                'datasets' => [[
                    'data' => [$kpis['verificados'], $kpis['no_encontrados'], $kpis['pendientes']],
                    'backgroundColor' => ['#059669', '#e11d48', '#cbd5e1'],
                ]],
            ],
            'options' => [
                'plugins' => ['legend' => ['position' => 'bottom']],
            ],
        ];
    }

    /** Payload para Chart.js: % de cumplimiento por empresa. */
    public function getGraficaEmpresasProperty(): array
    {
        $filas = $this->filasPorEmpresa;

        return [
            'data' => [
                'labels' => $filas->pluck('empresa_nombre')->all(),
                'datasets' => [[
                    'label' => '% cumplimiento',
                    'data' => $filas->map(fn ($f) => $this->porcentaje($f->activos_inventariados, $f->total_activos))->all(),
                    'backgroundColor' => '#0f766e',
                    'borderRadius' => 6,
                ]],
            ],
            'options' => [
                'indexAxis' => 'y',
                'scales' => ['x' => ['min' => 0, 'max' => 100]],
                'plugins' => ['legend' => ['display' => false]],
            ],
        ];
    }

    /** Ranking de los 10 centros de costos con menor cumplimiento. */
    public function getRankingPeoresCentrosProperty(): Collection
    {
        return $this->todasLasFilas
            ->filter(fn ($f) => $f->total_activos > 0)
            ->map(fn ($f) => (object) [
                'codigo' => $f->codigo,
                'descripcion' => $f->descripcion,
                'empresa_nombre' => $f->empresa_nombre,
                'inventariados' => $f->activos_inventariados,
                'total' => $f->total_activos,
                'porcentaje' => $this->porcentaje($f->activos_inventariados, $f->total_activos),
            ])
            ->sortBy('porcentaje')
            ->take(10)
            ->values();
    }

    public function porcentaje(int $inventariados, int $total): int
    {
        return $total > 0 ? (int) round(($inventariados / $total) * 100) : 0;
    }

    public function claseSemaforo(int $porcentaje): string
    {
        return match (true) {
            $porcentaje >= 80 => 'badge-green',
            $porcentaje >= 50 => 'badge-amber',
            default => 'badge-red',
        };
    }

    public function alternarPendientes(int $centroCostosId): void
    {
        $this->centroCostosExpandido = $this->centroCostosExpandido === $centroCostosId ? null : $centroCostosId;
    }

    public function getActivosPendientesProperty(): Collection
    {
        if (! $this->centroCostosExpandido) {
            return collect();
        }

        $inventario = $this->inventarioAbierto;

        return Activo::where('centro_costos_id', $this->centroCostosExpandido)
            ->whereDoesntHave('inventarioDetalles', function ($query) use ($inventario) {
                $query->where('inventario_id', $inventario?->id)
                    ->whereIn('estado', ['verificado', 'no_encontrado']);
            })
            ->orderBy('numero_activo')
            ->limit(50)
            ->get();
    }
}; ?>

<div>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-ink-900">
            {{ __('Cumplimiento del inventario') }}
        </h2>
    </x-slot>

    @if (! $this->inventarioAbierto)
        <div class="card card-pad border-amber-200 bg-amber-50 text-amber-800">
            No hay un periodo de inventario abierto actualmente.
        </div>
    @else
        @php($kpis = $this->kpis)

        <div class="space-y-8">
            {{-- KPIs --}}
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <div class="card card-pad">
                    <div class="text-xs font-medium uppercase tracking-wide text-ink-400">Cumplimiento global</div>
                    <div class="mt-1 text-2xl font-bold text-brand-700">{{ $kpis['porcentaje_global'] }}%</div>
                </div>
                <div class="card card-pad">
                    <div class="text-xs font-medium uppercase tracking-wide text-ink-400">Total activos</div>
                    <div class="mt-1 text-2xl font-bold text-ink-900">{{ number_format($kpis['total_activos']) }}</div>
                </div>
                <div class="card card-pad">
                    <div class="text-xs font-medium uppercase tracking-wide text-ink-400">Verificados</div>
                    <div class="mt-1 text-2xl font-bold text-emerald-600">{{ number_format($kpis['verificados']) }}</div>
                </div>
                <div class="card card-pad">
                    <div class="text-xs font-medium uppercase tracking-wide text-ink-400">No encontrados</div>
                    <div class="mt-1 text-2xl font-bold text-rose-600">{{ number_format($kpis['no_encontrados']) }}</div>
                </div>
                <div class="card card-pad">
                    <div class="text-xs font-medium uppercase tracking-wide text-ink-400">Centros sin iniciar</div>
                    <div class="mt-1 text-2xl font-bold text-amber-600">{{ $kpis['centros_sin_iniciar'] }}</div>
                </div>
            </div>

            {{-- Gráficas --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="card card-pad lg:col-span-1">
                    <h3 class="mb-3 text-sm font-semibold text-ink-900">Distribución de estados</h3>
                    <canvas
                        data-chart
                        data-chart-type="doughnut"
                        data-chart-payload="{{ json_encode($this->graficaEstados) }}"
                        height="220"
                    ></canvas>
                </div>
                <div class="card card-pad lg:col-span-2">
                    <h3 class="mb-3 text-sm font-semibold text-ink-900">% cumplimiento por empresa</h3>
                    <canvas
                        data-chart
                        data-chart-type="bar"
                        data-chart-payload="{{ json_encode($this->graficaEmpresas) }}"
                        height="220"
                    ></canvas>
                </div>
            </div>

            <div class="card card-pad">
                <h3 class="mb-3 text-sm font-semibold text-ink-900">Centros de costos que más necesitan atención (menor % de cumplimiento)</h3>
                <ol class="divide-y divide-ink-100">
                    @forelse ($this->rankingPeoresCentros as $i => $fila)
                        <li class="flex items-center gap-3 py-2.5">
                            <span @class([
                                'flex h-7 w-7 flex-none items-center justify-center rounded-full text-xs font-bold',
                                'bg-rose-600 text-white' => $i < 3,
                                'bg-ink-200 text-ink-700' => $i >= 3,
                            ])>
                                {{ $i + 1 }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-ink-900">{{ $fila->codigo }} — {{ $fila->descripcion }}</div>
                                <div class="text-xs text-ink-400">{{ $fila->empresa_nombre }} · {{ $fila->inventariados }} / {{ $fila->total }} activos</div>
                                <div class="mt-1 h-1.5 w-full overflow-hidden rounded-full bg-ink-100">
                                    <div class="h-full rounded-full bg-rose-500" style="width: {{ $fila->porcentaje }}%"></div>
                                </div>
                            </div>
                            <span class="badge {{ $this->claseSemaforo($fila->porcentaje) }} flex-none">{{ $fila->porcentaje }}%</span>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-ink-500">No hay datos suficientes todavía.</li>
                    @endforelse
                </ol>
            </div>

            {{-- Detalle por empresa --}}
            <div>
                <h3 class="mb-2 text-base font-semibold text-ink-900">Detalle por empresa</h3>
                <div class="table-wrap card">
                    <table class="dtable">
                        <thead>
                            <tr>
                                <th>Empresa</th>
                                <th>Inventariados</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->filasPorEmpresa as $fila)
                                @php($porcentaje = $this->porcentaje($fila->activos_inventariados, $fila->total_activos))
                                <tr>
                                    <td class="font-medium text-ink-900">{{ $fila->empresa_nombre }}</td>
                                    <td>{{ $fila->activos_inventariados }} / {{ $fila->total_activos }}</td>
                                    <td>
                                        <span class="badge {{ $this->claseSemaforo($porcentaje) }}">{{ $porcentaje }}%</span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Detalle por centro de costos --}}
            <div>
                <h3 class="mb-2 text-base font-semibold text-ink-900">Detalle por centro de costos</h3>
                <div class="table-wrap card">
                    <table class="dtable">
                        <thead>
                            <tr>
                                <th>Centro de costos</th>
                                <th>Empresa</th>
                                <th>Inventariados</th>
                                <th>%</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($this->filasPorCentro as $fila)
                                @php($porcentaje = $this->porcentaje($fila->activos_inventariados, $fila->total_activos))
                                <tr>
                                    <td class="text-ink-900">{{ $fila->codigo }} — {{ $fila->descripcion }}</td>
                                    <td>{{ $fila->empresa_nombre }}</td>
                                    <td>{{ $fila->activos_inventariados }} / {{ $fila->total_activos }}</td>
                                    <td>
                                        <span class="badge {{ $this->claseSemaforo($porcentaje) }}">{{ $porcentaje }}%</span>
                                    </td>
                                    <td class="text-right">
                                        <button type="button" wire:click="alternarPendientes({{ $fila->centro_costos_id }})" class="btn btn-ghost btn-xs">
                                            {{ $centroCostosExpandido === $fila->centro_costos_id ? 'Ocultar' : 'Ver pendientes' }}
                                        </button>
                                    </td>
                                </tr>
                                @if ($centroCostosExpandido === $fila->centro_costos_id)
                                    <tr>
                                        <td colspan="5" class="bg-ink-50">
                                            <ul class="list-inside list-disc text-sm text-ink-600">
                                                @forelse ($this->activosPendientes as $activo)
                                                    <li>{{ $activo->numero_activo }} — {{ $activo->denominacion }}</li>
                                                @empty
                                                    <li>Sin pendientes.</li>
                                                @endforelse
                                            </ul>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $this->filasPorCentro->links() }}
                </div>
            </div>
        </div>
    @endif
</div>
