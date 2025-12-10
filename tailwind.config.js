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

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            screens: {
                'xs': '661px',    // 660px以下でタイトル切り替え
                'xxs': '681px',   // 680px以下で副題非表示
                'mobile-sm': {'max': '345px'}, // 345px以下でモバイル最適化
                'xl-': {'min': '1024px', 'max': '1133px'}, // 1024px～1133pxで中間表示
            },
        },
    },

    plugins: [forms],
};
