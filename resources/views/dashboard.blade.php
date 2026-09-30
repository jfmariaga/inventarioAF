<x-app-layout>
    <x-slot name="header">
        <h2 class="text-lg font-semibold text-ink-900">
            {{ __('Inicio') }}
        </h2>
    </x-slot>

    @php
        $acciones = [];

        if (auth()->user()->can('inventario.ver')) {
            $acciones[] = 'inventariar activos';
        }

        if (auth()->user()->can('cumplimiento.ver')) {
            $acciones[] = 'consultar el cumplimiento';
        }

        if (auth()->user()->canAny(['admin.usuarios', 'admin.roles', 'admin.periodos', 'admin.catalogos'])) {
            $acciones[] = 'administrar el sistema';
        }

        $listaAcciones = match (count($acciones)) {
            0 => null,
            1 => $acciones[0],
            default => implode(', ', array_slice($acciones, 0, -1)).' o '.end($acciones),
        };
    @endphp

    <div class="space-y-6">
        <div class="card card-pad">
            <p class="text-sm text-ink-600">
                Hola, <span class="font-medium text-ink-900">{{ auth()->user()->name }}</span>.
                @if ($listaAcciones)
                    Usa el menú de la izquierda para {{ $listaAcciones }}.
                @else
                    Aún no tienes accesos asignados; contacta a un Administrador.
                @endif
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @can('inventario.ver')
                <a href="{{ route('inventario.index') }}" wire:navigate class="card card-pad transition-shadow hover:shadow-card-lg">
                    <div class="section-num mb-3">1</div>
                    <div class="font-semibold text-ink-900">Inventariar</div>
                    <div class="mt-1 text-sm text-ink-500">Captura activos por centro de costos.</div>
                </a>
            @endcan
            @can('cumplimiento.ver')
                <a href="{{ route('cumplimiento.index') }}" wire:navigate class="card card-pad transition-shadow hover:shadow-card-lg">
                    <div class="section-num mb-3 bg-sky-700">2</div>
                    <div class="font-semibold text-ink-900">Cumplimiento</div>
                    <div class="mt-1 text-sm text-ink-500">% inventariado por empresa y centro de costos.</div>
                </a>
            @endcan
            @can('admin.periodos')
                <a href="{{ route('admin.periodos') }}" wire:navigate class="card card-pad transition-shadow hover:shadow-card-lg">
                    <div class="section-num mb-3 bg-amber-600">3</div>
                    <div class="font-semibold text-ink-900">Periodos</div>
                    <div class="mt-1 text-sm text-ink-500">Abrir, cerrar o reabrir un periodo de inventario.</div>
                </a>
            @endcan
            @can('inventario.exportar')
                <a href="{{ route('admin.exportaciones') }}" wire:navigate class="card card-pad transition-shadow hover:shadow-card-lg">
                    <div class="section-num mb-3 bg-violet-700">4</div>
                    <div class="font-semibold text-ink-900">Exportar</div>
                    <div class="mt-1 text-sm text-ink-500">Genera el Excel del inventario con fotos.</div>
                </a>
            @endcan
        </div>
    </div>
</x-app-layout>
