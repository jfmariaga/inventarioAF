@props(['class' => ''])

<button
    type="button"
    x-data
    @click="
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('theme', isDark ? 'dark' : 'light');
    "
    {{ $attributes->merge(['class' => 'inline-flex h-9 w-9 items-center justify-center rounded-md text-ink-secondary hover:bg-surface-alt focus:outline-none focus:ring-2 focus:ring-brand-500 dark:text-gray-400 dark:hover:bg-gray-700 '.$class]) }}
>
    <span class="sr-only">Cambiar tema</span>

    {{-- Luna: visible en modo claro --}}
    <svg class="h-5 w-5 block dark:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />
    </svg>

    {{-- Sol: visible en modo oscuro --}}
    <svg class="h-5 w-5 hidden dark:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="4" />
        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" />
    </svg>
</button>
