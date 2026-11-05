import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Poppins', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Rojo/vino institucional Levapan (docs/guia-de-estilos.md §1.1)
                brand: {
                    50: '#FBEAEA',
                    100: '#F6D4D4',
                    200: '#EDAAAA',
                    300: '#E27E7E',
                    400: '#DA5E5E',
                    500: '#E24545',
                    600: '#CF1D1D',
                    700: '#A81616',
                    800: '#8E1212',
                    900: '#6E0E0E',
                    950: '#4A0909',
                },
                // Escala neutra (antes slate); ancla en ink-900=#111111 (ink), ink-700=#3C3C3C
                // (ink-muted) e ink-500=#616161 (ink-secondary) de la guía.
                ink: {
                    DEFAULT: '#111111',
                    secondary: '#616161',
                    muted: '#3C3C3C',
                    50: '#F7F4F4',
                    100: '#EDE8EC',
                    200: '#DCD5D8',
                    300: '#C2B9BC',
                    400: '#948C8F',
                    500: '#616161',
                    600: '#4E4749',
                    700: '#3C3C3C',
                    800: '#262222',
                    900: '#111111',
                    950: '#080707',
                },
                canvas: {
                    DEFAULT: '#FFFFFF',
                    dark: '#121212',
                },
                surface: {
                    DEFAULT: '#FFFFFF',
                    alt: '#F7F4F4',
                    dark: '#1C1C1C',
                },
                line: {
                    DEFAULT: '#EDE8EC',
                },
                accent: {
                    DEFAULT: '#E58A45',
                    soft: '#FFD7A7',
                },
                cold: {
                    DEFAULT: '#D5DFF7',
                },
                success: {
                    DEFAULT: '#1A7A42',
                    soft: '#E6F4EC',
                    dark: '#2FAE68',
                },
                warning: {
                    DEFAULT: '#E58A45',
                    soft: '#FDF0E6',
                    text: '#9C5319',
                },
                danger: {
                    DEFAULT: '#7A1E1E',
                    soft: '#F6E9E9',
                    dark: '#D96E6E',
                    hover: '#5E1717',
                    active: '#431010',
                },
            },
            boxShadow: {
                card: '0 1px 2px 0 rgb(15 23 42 / 0.04), 0 1px 3px 0 rgb(15 23 42 / 0.06)',
                'card-lg': '0 4px 12px -2px rgb(15 23 42 / 0.08), 0 2px 6px -2px rgb(15 23 42 / 0.05)',
            },
            borderRadius: {
                xl: '0.875rem',
            },
        },
    },

    plugins: [forms],
};
