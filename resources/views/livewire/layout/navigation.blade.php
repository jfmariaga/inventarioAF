<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-ink-200 bg-white
              transition-transform duration-200
              lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:self-start">
    <div class="flex h-16 flex-none items-center gap-2.5 border-b border-ink-200 px-5">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2.5">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-brand-700 text-white">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
            </span>
            <div class="leading-tight">
                <div class="text-sm font-bold text-ink-900">Inventario AF</div>
                <div class="text-[11px] text-ink-400">Activos Fijos</div>
            </div>
        </a>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto p-3 text-sm">
        <a href="{{ route('dashboard') }}" wire:navigate
           class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors
                  {{ request()->routeIs('dashboard') ? 'bg-brand-50 text-brand-800' : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900' }}">
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12l9-9 9 9M5 10v10h14V10"/></svg>
            Inicio
        </a>

        @can('inventario.ver')
            <a href="{{ route('inventario.index') }}" wire:navigate
               class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors
                      {{ request()->routeIs('inventario.*') ? 'bg-brand-50 text-brand-800' : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6M9 16h6M9 8h6M5 4h14v16H5z"/></svg>
                Inventariar
            </a>
        @endcan

        @can('cumplimiento.ver')
            <a href="{{ route('cumplimiento.index') }}" wire:navigate
               class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors
                      {{ request()->routeIs('cumplimiento.*') ? 'bg-brand-50 text-brand-800' : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900' }}">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18M7 15l4-4 4 4 5-6"/></svg>
                Cumplimiento
            </a>
        @endcan

        @canany(['admin.usuarios', 'admin.periodos', 'admin.catalogos', 'inventario.exportar'])
            <div class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wide text-ink-400">Administración</div>

            @can('admin.usuarios')
                <a href="{{ route('admin.usuarios') }}" wire:navigate
                   class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors
                          {{ request()->routeIs('admin.usuarios') ? 'bg-brand-50 text-brand-800' : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    Usuarios
                </a>
            @endcan
            @can('admin.periodos')
                <a href="{{ route('admin.periodos') }}" wire:navigate
                   class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors
                          {{ request()->routeIs('admin.periodos') ? 'bg-brand-50 text-brand-800' : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    Periodos de inventario
                </a>
            @endcan
            @can('admin.catalogos')
                <a href="{{ route('admin.catalogos') }}" wire:navigate
                   class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors
                          {{ request()->routeIs('admin.catalogos') ? 'bg-brand-50 text-brand-800' : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    Catálogos
                </a>
            @endcan
            @can('inventario.exportar')
                <a href="{{ route('admin.exportaciones') }}" wire:navigate
                   class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors
                          {{ request()->routeIs('admin.exportaciones') ? 'bg-brand-50 text-brand-800' : 'text-ink-600 hover:bg-ink-100 hover:text-ink-900' }}">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    Exportar inventario
                </a>
            @endcan
        @endcanany
    </nav>

    <div class="flex-none border-t border-ink-200 p-3">
        <div class="flex items-center gap-3 rounded-lg px-2 py-2">
            <div class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-ink-200 text-sm font-semibold text-ink-600">
                {{ Illuminate\Support\Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
            </div>
            <div class="min-w-0 flex-1 leading-tight">
                <div class="truncate text-sm font-medium text-ink-800">{{ auth()->user()->name }}</div>
                <div class="truncate text-[11px] text-ink-400">
                    {{ auth()->user()->roles->pluck('name')->join(', ') ?: 'Sin rol' }}
                </div>
            </div>
        </div>
        <div class="mt-1 flex gap-1">
            <a href="{{ route('profile') }}" wire:navigate class="btn btn-ghost btn-sm flex-1">Perfil</a>
            <button wire:click="logout" class="btn btn-ghost btn-sm flex-1 text-rose-600 hover:bg-rose-50">Salir</button>
        </div>
    </div>
</aside>
