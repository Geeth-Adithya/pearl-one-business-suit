/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        brand: {
          100: '#B3D4FF',
          300: '#68AAFF',
          500: '#0064E7',
          700: '#0000B5',
          900: '#000078',
        },
        bg: {
          light: '#F4F7FE',
          dark: '#071022' // Matched exact background from image
        },
        card: {
          light: '#FFFFFF',
          dark: '#0B172F' // Matched exact card/sidebar from image
        },
        border: {
          light: '#E2E8F0',
          dark: '#182745'
        },
        text: {
          light: '#1E293B',
          dark: '#F8FAFC',
          muted: '#64748B',
          mutedDark: '#8BA3C0'
        }
      },
      fontFamily: {
        sans: ['Inter', 'sans-serif'],
      }
    },
  },
  plugins: [],
}
