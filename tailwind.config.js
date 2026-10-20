import colors from 'tailwindcss/colors';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './app/**/*.php',
    ],
    theme: {
        extend: {
            // Brand shades deepened slightly so white or tinted text keeps WCAG AA contrast.
            colors: {
                sky: { ...colors.sky, 500: '#0274b3', 600: '#0369a1', 700: '#075985' },
                pink: { ...colors.pink, 600: '#be185d', 700: '#9d174d' },
                amber: { ...colors.amber, 600: '#b45309', 700: '#92400e' },
                emerald: { ...colors.emerald, 600: '#047857', 700: '#065f46' },
            },
        },
    },
    plugins: [],
};
