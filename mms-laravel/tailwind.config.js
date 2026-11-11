/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/Livewire/**/*.php',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                gray: {
                    950: '#0d0f14',
                    900: '#13161d',
                    800: '#1a1e28',
                    700: '#21263300',
                },
            },
            animation: {
                'fade-in': 'fadeIn 0.3s ease forwards',
                'slide-up': 'slideUp 0.4s ease forwards',
                'pulse-slow': 'pulse 2s cubic-bezier(0.4,0,0.6,1) infinite',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0', transform: 'translateY(6px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
                slideUp: {
                    '0%': { opacity: '0', transform: 'translateY(12px)' },
                    '100%': { opacity: '1', transform: 'translateY(0)' },
                },
            },
        },
    },
    plugins: [],
};
