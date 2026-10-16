import { defineConfig, loadEnv } from 'vite';
import path from 'path';
import react from '@vitejs/plugin-react';
import svgr from 'vite-plugin-svgr';

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '');
  const url = env.VITE_PROXY_TARGET || 'https://test-lk.orb.ru';

  return {
    plugins: [react(), svgr()],
    server: {
      proxy: {
        '/csrf_token': {
          target: url,
          changeOrigin: true,
        },
        '/admin/api': {
          target: url,
          changeOrigin: true,
        },
        '/profile': {
          target: url,
          changeOrigin: true,
        },
        '/api': {
          target: url,
          changeOrigin: true,
        },
        '/after_logout': {
          target: url,
          changeOrigin: true,
        },
        '/storage/avatars': {
          target: url,
          changeOrigin: true,
        },
        '/oauth/authorize': {
          target: url,
          changeOrigin: true,
        },
        '/oauth/token': {
          target: url,
          changeOrigin: true,
        },
      },
    },
    resolve: {
      alias: {
        '@': path.resolve(__dirname, './src'),
      },
    },
  };
});
