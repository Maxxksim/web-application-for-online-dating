/** @type {import('tailwindcss').Config} */
const plugin = require('tailwindcss/plugin')

export default {
  content: [
    './index.html',
    './src/**/*.{vue,js,ts,jsx,tsx}',
  ],
  theme: {
    extend: {
      fontFamily: {
        base: ['Manrope', 'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
        display: ['Sora', 'ui-sans-serif', 'system-ui', '-apple-system', 'Segoe UI', 'sans-serif'],
      },
      colors: {
        primary: {
          50: '#f5f3ff',
          100: '#ede9fe',
          200: '#ddd6fe',
          300: '#c4b5fd',
          400: '#a78bfa',
          500: '#8b5cf6',
          600: '#7c3aed',
          700: '#6d28d9',
          800: '#5b21b6',
          900: '#4c1d95',
        },
        accent: {
          DEFAULT: '#06b6d4',
          light: '#67e8f9',
          muted: '#ecfeff',
          50: '#f0f9ff',
          100: '#e0f2fe',
          200: '#bae6fd',
          300: '#7dd3fc',
          400: '#38bdf8',
          500: '#0ea5e9',
          600: '#0284c7',
          700: '#0369a1',
          800: '#075985',
          900: '#0c3d66',
        },
        surface: {
          DEFAULT: 'rgba(255, 255, 255, 0.72)',
          hover: 'rgba(255, 255, 255, 0.88)',
        },
        border: {
          DEFAULT: 'rgba(226, 232, 240, 0.75)',
          strong: 'rgba(203, 213, 225, 0.9)',
        }
      },
      borderRadius: {
        'sm': '12px',
        'md': '16px',
        'lg': '24px',
        'xl': '32px',
      },
      boxShadow: {
        'sm': '0 4px 16px rgba(15, 23, 42, 0.06), 0 1px 4px rgba(15, 23, 42, 0.04)',
        'md': '0 8px 32px rgba(15, 23, 42, 0.1), 0 2px 8px rgba(15, 23, 42, 0.06)',
        'lg': '0 20px 60px rgba(15, 23, 42, 0.14), 0 4px 16px rgba(15, 23, 42, 0.08)',
      },
      transitionTimingFunction: {
        'smooth': 'cubic-bezier(0.2, 0.8, 0.2, 1)',
        'spring': 'cubic-bezier(0.18, 0.9, 0.2, 1.15)',
      },
      transitionDuration: {
        'fast': '160ms',
      },
      backgroundImage: {
        'gradient-primary': 'linear-gradient(135deg, #06b6d4 0%, #22c55e 100%)',
        'gradient-primary-alt': 'linear-gradient(135deg, #7c3aed 0%, #06b6d4 100%)',
        'gradient-to-bottom': 'linear-gradient(180deg, rgba(139,92,246,0.1) 0%, rgba(14,165,233,0.1) 100%)',
      },
      keyframes: {
        'fade-in-up': {
          '0%': { opacity: '0', transform: 'translateY(12px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        'float-slow': {
          '0%, 100%': { transform: 'translateY(0px) scale(1)' },
          '50%': { transform: 'translateY(-18px) scale(1.03)' },
        },
        'float-medium': {
          '0%, 100%': { transform: 'translateY(0px) scale(1)' },
          '50%': { transform: 'translateY(-12px) scale(1.02)' },
        },
        'float-fast': {
          '0%, 100%': { transform: 'translateY(0px)' },
          '50%': { transform: 'translateY(-8px)' },
        },
      },
      animation: {
        'fade-in-up': 'fade-in-up 400ms cubic-bezier(0.2, 0.8, 0.2, 1) both',
        'float-slow': 'float-slow 9s ease-in-out infinite',
        'float-medium': 'float-medium 7s ease-in-out infinite',
        'float-fast': 'float-fast 5s ease-in-out infinite',
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
    plugin(function ({ addBase }) {
      addBase({
        '.preview-pop-enter-active, .preview-pop-leave-active': {
          transition: 'opacity 180ms cubic-bezier(0.2, 0.8, 0.2, 1)',
        },
        '.preview-pop-enter-from, .preview-pop-leave-to': {
          opacity: '0',
        },
        '.preview-pop-enter-active .preview-shell, .preview-pop-leave-active .preview-shell': {
          transition: 'transform 180ms cubic-bezier(0.18, 0.9, 0.2, 1.15), opacity 180ms cubic-bezier(0.2, 0.8, 0.2, 1)',
        },
        '.preview-pop-enter-from .preview-shell, .preview-pop-leave-to .preview-shell': {
          transform: 'translateY(8px) scale(0.97)',
          opacity: '0',
        },

        '.dropdown-fade-enter-active, .dropdown-fade-leave-active': {
          transition:
            'opacity 0.2s cubic-bezier(0.18, 0.89, 0.32, 1.28), transform 0.2s cubic-bezier(0.18, 0.89, 0.32, 1.28)',
        },
        '.dropdown-fade-enter-from, .dropdown-fade-leave-to': {
          opacity: '0',
          transform: 'scaleY(0.95) translateY(-4px)',
          transformOrigin: 'top',
        },

        '.dual-range-input::-webkit-slider-thumb': {
          '-webkit-appearance': 'none',
          appearance: 'none',
          width: '20px',
          height: '20px',
          'border-radius': '50%',
          background: '#fff',
          border: '2.5px solid #67e8f9',
          'box-shadow': '0 2px 8px rgba(0,0,0,0.12)',
          cursor: 'pointer',
          'pointer-events': 'all',
          position: 'relative',
          'z-index': '2',
          transition: 'box-shadow 0.15s ease, transform 0.15s ease',
        },
        '.dual-range-input::-webkit-slider-thumb:hover': {
          'box-shadow': '0 2px 12px rgba(103, 232, 249, 0.4)',
          transform: 'scale(1.12)',
        },
        '.dual-range-input::-moz-range-thumb': {
          width: '20px',
          height: '20px',
          'border-radius': '50%',
          background: '#fff',
          border: '2.5px solid #67e8f9',
          'box-shadow': '0 2px 8px rgba(0,0,0,0.12)',
          cursor: 'pointer',
          'pointer-events': 'all',
        },

        '.dropdown-scroll::-webkit-scrollbar': {
          width: '4px',
        },
        '.dropdown-scroll::-webkit-scrollbar-track': {
          background: 'transparent',
        },
        '.dropdown-scroll::-webkit-scrollbar-thumb': {
          background: 'rgba(148, 163, 184, 0.4)',
          'border-radius': '4px',
        },
        '.dropdown-scroll::-webkit-scrollbar-thumb:hover': {
          background: 'rgba(148, 163, 184, 0.7)',
        },
      })
    }),
  ],
  darkMode: 'class',
}
