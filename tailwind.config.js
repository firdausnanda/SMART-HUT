import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Plus Jakarta Sans', ...defaultTheme.fontFamily.sans],
                display: ['Outfit', 'sans-serif'],
            },
            colors: {
                primary: {
                    50: '#f2fcf5',
                    100: '#e1f8e8',
                    200: '#c3efd2',
                    300: '#94e0b0',
                    400: '#5cc788',
                    500: '#34aa6b',
                    600: '#258a55',
                    700: '#1f6e46', // Dark Green
                    800: '#1b573a',
                    900: '#174731',
                    950: '#0c281c',
                },
            },
        },
    },

    plugins: [forms],
};
