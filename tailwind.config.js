import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            keyframes: {
                'scan-line': {
                    '0%, 100%': { top: '0%', opacity: '1' },
                    '50%':      { top: '100%', opacity: '0.6' },
                },
            },
            animation: {
                'scan-line': 'scan-line 2s ease-in-out infinite',
            },
        },
    },

    plugins: [forms],
};
