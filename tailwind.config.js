import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    // Admin pages build status colours dynamically (e.g. bg-{{ $color }}-100).
    safelist: [
        { pattern: /^(bg|text)-(slate|violet|emerald|rose|amber|blue)-(100|500|600)$/, variants: ['dark'] },
        { pattern: /^(bg|text)-(slate|violet|emerald|rose|amber|blue)-(400|900\/30)$/, variants: ['dark'] },
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50:  '#f5f3ff',
                    100: '#ede9fe',
                    200: '#ddd6fe',
                    300: '#c4b5fd',
                    400: '#a78bfa',
                    500: '#8b5cf6',
                    600: '#7c3aed',
                    700: '#6d28d9',
                    800: '#5b21b6',
                    900: '#4c1d95',
                    950: '#2e1065',
                },
            },
            boxShadow: {
                'glow-violet': '0 0 0 3px rgba(139, 92, 246, 0.15)',
                'card': '0 1px 3px rgba(0,0,0,.08), 0 1px 2px rgba(0,0,0,.04)',
                'card-md': '0 4px 12px rgba(0,0,0,.10), 0 2px 4px rgba(0,0,0,.04)',
                'dropdown': '0 10px 40px rgba(0,0,0,.12), 0 2px 8px rgba(0,0,0,.06)',
            },
            animation: {
                'fade-in': 'fadeIn 0.2s ease-out',
                'slide-in': 'slideIn 0.25s ease-out',
                'pulse-dot': 'pulseDot 2s cubic-bezier(0.4,0,0.6,1) infinite',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0', transform: 'translateY(-4px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideIn: {
                    '0%': { opacity: '0', transform: 'translateX(-8px)' },
                    '100%': { opacity: '1', transform: 'translateX(0)' },
                },
                pulseDot: {
                    '0%,100%': { opacity: '1' },
                    '50%': { opacity: '.5' },
                },
            },
        },
    },

    plugins: [
        forms({strategy: 'class'}),
    ],
};
