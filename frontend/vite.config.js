import { fileURLToPath, URL } from 'node:url'

import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const enableDevTools = env.VITE_VUE_DEVTOOLS === 'true'

  return {
    plugins: [vue(), ...(enableDevTools ? [vueDevTools()] : [])],
    server: {
    allowedHosts: [
      'bdc4-46-109-129-163.ngrok-free.app' // Добавьте ваш конкретный хост
    ]
  },
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
  }
})
