/** @type {import('tailwindcss').Config} */
export default {
  future: {
    hoverOnlyWhenSupported: true,
  },
  darkMode: ['class'],
  content: ['./index.html', './src/**/*.{js,ts,jsx,tsx}'],
  theme: {
    extend: {
      borderRadius: {
        '4xl': 'calc(var(--radius) * 4)' /*32px*/,
        'lg-plus': 'calc(var(--radius) + 2px)',
        lg: 'var(--radius)',
        md: 'calc(var(--radius) - 2px)',
        sm: 'calc(var(--radius) - 4px)',
      },
      colors: {
        accept: 'var(--accept)',
        aggressive: 'hsl(var(--aggressive))',
        background: 'hsl(var(--background))',
        'modal-background': 'hsla(var(--modal-background))',
        'modal-foreground': 'hsl(var(--modal-foreground))',
        foreground: 'hsl(var(--foreground))',
        standard: 'hsl(var(--standard))',
        error: 'hsla(var(--error))',
        card: {
          DEFAULT: 'hsl(var(--card))',
          foreground: 'hsl(var(--card-foreground))',
        },
        popover: {
          DEFAULT: 'hsl(var(--popover))',
          foreground: 'hsl(var(--popover-foreground))',
        },
        primary: {
          DEFAULT: 'hsl(var(--primary))',
          foreground: 'hsl(var(--primary-foreground))',
          hover: 'hsl(var(--primary-hover))',
        },
        secondary: {
          DEFAULT: 'hsl(var(--secondary))',
          foreground: 'hsl(var(--secondary-foreground))',
        },
        ghost: {
          DEFAULT: 'hsla(var(--ghost))',
          foreground: 'hsl(var(--ghost-foreground))',
          hover: 'hsla(var(--ghost-hover))',
        },
        muted: {
          DEFAULT: 'hsl(var(--muted))',
          foreground: 'hsl(var(--muted-foreground))',
          'foreground-placeholder': 'hsl(var(--muted-foreground-placeholder))',
        },
        accent: {
          DEFAULT: 'hsl(var(--accent))',
          foreground: 'hsl(var(--accent-foreground))',
          'foreground-header': 'hsl(var(--accent-foreground-header))',
        },
        destructive: {
          DEFAULT: 'hsl(var(--destructive))',
          foreground: 'hsl(var(--destructive-foreground))',
        },
        border: 'hsla(var(--border))',
        input: 'hsl(var(--input))',
        ring: 'hsl(var(--ring))',
        'basic-black': 'hsl(var(--black-basic))',
        'basic-black-opacity': 'hsla(var(--black-basic-opacity))',
        chart: {
          1: 'hsl(var(--chart-1))',
          2: 'hsl(var(--chart-2))',
          3: 'hsl(var(--chart-3))',
          4: 'hsl(var(--chart-4))',
          5: 'hsl(var(--chart-5))',
        },
      },
      maxWidth: {
        container: 'var(--container-max-width)',
        modal: 'var(--max-w-modal)',
        'modal-mobile': 'var(--max-w-modal-mobile)',
      },
      height: {
        'app-bar': 'var(--app-bar-height)',
        '134px': '134px',
      },
      margin: {
        'app-bar': 'var(--app-bar-height)',
      },
      padding: {
        '13px': '13px',
      },
      fontFamily: {
        sans: ['Golos UI', 'sans-serif'],
      },
      screens: {
        mobile: '450px',
      },
      keyframes: {
        'accordion-down': {
          from: {
            height: '0',
          },
          to: {
            height: 'var(--radix-accordion-content-height)',
          },
        },
        'accordion-up': {
          from: {
            height: 'var(--radix-accordion-content-height)',
          },
          to: {
            height: '0',
          },
        },
      },
      animation: {
        'accordion-down': 'accordion-down 0.2s ease-out',
        'accordion-up': 'accordion-up 0.2s ease-out',
      },
    },
  },
  plugins: [require('tailwindcss-animate'), require('@tailwindcss/typography')],
};
