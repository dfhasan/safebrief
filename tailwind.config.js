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
            // Safety-signage palette (ISO 7010): navy ink, safety yellow for "do this next",
            // signal green for done, safety red only for errors and destructive actions.
            colors: {
                ink: { DEFAULT: '#15233B', soft: '#3A4A63', muted: '#66748A' },
                paper: '#EEF1F5',
                line: '#D3D9E2',
                hazard: { DEFAULT: '#FFC20E', deep: '#E0A800', wash: '#FFF5D1' },
                signal: { DEFAULT: '#00805A', wash: '#E2F3EC' },
                alarm: { DEFAULT: '#C8102E', wash: '#FBE7EA' },
            },
            fontFamily: {
                sans: ['"IBM Plex Sans"', '"IBM Plex Sans Arabic"', ...defaultTheme.fontFamily.sans],
                arabic: ['"IBM Plex Sans Arabic"', '"IBM Plex Sans"', ...defaultTheme.fontFamily.sans],
            },
            backgroundImage: {
                hazard: 'repeating-linear-gradient(-45deg, #FFC20E 0 10px, #15233B 10px 20px)',
            },
        },
    },

    plugins: [forms],
};
