import { fileURLToPath } from 'node:url'
import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import vuetify from 'vite-plugin-vuetify'

export default defineConfig(({ mode }) => {
  const entorno = loadEnv(mode, process.cwd(), '')

  return {
    plugins: [
      vue(),
      // Importa solo los componentes de Vuetify que se usan.
      vuetify({ autoImport: true })
    ],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url))
      }
    },
    server: {
      port: 5173,
      // En desarrollo, /api va al backend PHP (php -S localhost:8000 -t backend/public),
      // así navegador, frontend y API comparten origen y la cookie de sesión funciona.
      proxy: {
        '/api': {
          // 127.0.0.1 en vez de localhost: PHP puede quedar escuchando solo en IPv6 (::1)
          // y el proxy fallaría con 502.
          target: entorno.VITE_BACKEND_URL || 'http://127.0.0.1:8000',
          changeOrigin: true
        }
      }
    }
  }
})
