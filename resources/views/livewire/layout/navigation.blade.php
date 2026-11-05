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

<aside x-data
       :class="[sidebar ? 'translate-x-0' : '-translate-x-full', $store.sidebar.collapsed ? 'lg:w-20' : 'lg:w-64']"
       class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-black
              transition-all duration-200
              lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 lg:self-start">
    <div class="flex h-16 flex-none items-center gap-2.5 bg-white px-4">
        <a href="{{ route('dashboard') }}" wire:navigate class="flex min-w-0 items-center gap-2.5">
            <span class="flex h-9 w-9 flex-none items-center justify-center rounded-lg bg-brand-600 text-white">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="7" width="18" height="13" rx="2"/><path d="M8 7V5a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                </svg>
            </span>
            <div x-show="!$store.sidebar.collapsed || sidebar" class="min-w-0 leading-tight">
                <div class="truncate text-sm font-bold text-ink">Inventario AF</div>
                <div class="truncate text-[11px] text-ink-secondary">Activos Fijos</div>
            </div>
        </a>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto overflow-x-hidden p-3 text-sm">
        <a href="{{ route('dashboard') }}" wire:navigate
           :title="$store.sidebar.collapsed ? 'Inicio' : null"
           class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors
                  {{ request()->routeIs('dashboard') ? 'bg-brand-600 text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
            <svg class="h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12l9-9 9 9M5 10v10h14V10"/></svg>
            <span x-show="!$store.sidebar.collapsed || sidebar">Inicio</span>
        </a>

        @can('inventario.ver')
            <a href="{{ route('inventario.index') }}" wire:navigate
               :title="$store.sidebar.collapsed ? 'Inventariar' : null"
               class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors
                      {{ request()->routeIs('inventario.*') ? 'bg-brand-600 text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                <svg class="h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 12h6M9 16h6M9 8h6M5 4h14v16H5z"/></svg>
                <span x-show="!$store.sidebar.collapsed || sidebar">Inventariar</span>
            </a>
        @endcan

        @can('cumplimiento.ver')
            <a href="{{ route('cumplimiento.index') }}" wire:navigate
               :title="$store.sidebar.collapsed ? 'Cumplimiento' : null"
               class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors
                      {{ request()->routeIs('cumplimiento.*') ? 'bg-brand-600 text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                <svg class="h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18M7 15l4-4 4 4 5-6"/></svg>
                <span x-show="!$store.sidebar.collapsed || sidebar">Cumplimiento</span>
            </a>
        @endcan

        @canany(['admin.usuarios', 'admin.periodos', 'admin.catalogos', 'inventario.exportar'])
            <div x-show="!$store.sidebar.collapsed || sidebar" class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Administración</div>

            @can('admin.usuarios')
                <a href="{{ route('admin.usuarios') }}" wire:navigate
                   :title="$store.sidebar.collapsed ? 'Usuarios' : null"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors
                          {{ request()->routeIs('admin.usuarios') ? 'bg-brand-600 text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span x-show="!$store.sidebar.collapsed || sidebar">Usuarios</span>
                </a>
            @endcan
            @can('admin.periodos')
                <a href="{{ route('admin.periodos') }}" wire:navigate
                   :title="$store.sidebar.collapsed ? 'Periodos de inventario' : null"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors
                          {{ request()->routeIs('admin.periodos') ? 'bg-brand-600 text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                    <span x-show="!$store.sidebar.collapsed || sidebar">Periodos de inventario</span>
                </a>
            @endcan
            @can('admin.catalogos')
                <a href="{{ route('admin.catalogos') }}" wire:navigate
                   :title="$store.sidebar.collapsed ? 'Catálogos' : null"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors
                          {{ request()->routeIs('admin.catalogos') ? 'bg-brand-600 text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                    <span x-show="!$store.sidebar.collapsed || sidebar">Catálogos</span>
                </a>
            @endcan
            @can('inventario.exportar')
                <a href="{{ route('admin.exportaciones') }}" wire:navigate
                   :title="$store.sidebar.collapsed ? 'Exportar inventario' : null"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 font-medium transition-colors
                          {{ request()->routeIs('admin.exportaciones') ? 'bg-brand-600 text-white' : 'text-gray-300 hover:bg-white/10 hover:text-white' }}">
                    <svg class="h-4 w-4 flex-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4M7 10l5 5 5-5M12 15V3"/></svg>
                    <span x-show="!$store.sidebar.collapsed || sidebar">Exportar inventario</span>
                </a>
            @endcan
        @endcanany
    </nav>

    <div class="flex-none border-t border-white/10 p-3">
        <div class="flex items-center gap-3 rounded-lg px-2 py-2">
            <div class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-white/10 text-sm font-semibold text-white">
                {{ Illuminate\Support\Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
            </div>
            <div x-show="!$store.sidebar.collapsed || sidebar" class="min-w-0 flex-1 leading-tight">
                <div class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</div>
                <div class="truncate text-[11px] text-gray-400">
                    {{ auth()->user()->roles->pluck('name')->join(', ') ?: 'Sin rol' }}
                </div>
            </div>
        </div>
        <div x-show="!$store.sidebar.collapsed || sidebar" class="mt-1 flex gap-1">
            <a href="{{ route('profile') }}" wire:navigate class="flex-1 rounded-lg px-3 py-1.5 text-center text-xs font-medium text-gray-300 hover:bg-white/10 hover:text-white">Perfil</a>
            <button wire:click="logout" class="flex-1 rounded-lg px-3 py-1.5 text-center text-xs font-medium text-gray-300 hover:bg-white/10 hover:text-white">Salir</button>
        </div>

        <button type="button" @click="$store.sidebar.toggle()" title="Colapsar/expandir menú"
                class="mt-2 hidden w-full items-center justify-center rounded-lg py-1.5 text-gray-400 hover:bg-white/10 hover:text-white lg:flex">
            <svg class="h-4 w-4 transition-transform" :class="{ 'rotate-180': $store.sidebar.collapsed }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6" />
            </svg>
        </button>
    </div>
</aside>
