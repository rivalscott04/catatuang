import { defineConfig, loadEnv } from 'vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'
import { readFileSync } from 'fs'
import { resolve } from 'path'

// https://vite.dev/config/
export default defineConfig(({ mode }) => {
  // Load .env.prod for production builds
  let env = {}
  if (mode === 'production') {
    try {
      const envProdPath = resolve(process.cwd(), '.env.prod')
      const envProdContent = readFileSync(envProdPath, 'utf-8')
      console.log('Loading .env.prod for production build...')
      envProdContent.split('\n').forEach(line => {
        const trimmedLine = line.trim()
        if (trimmedLine && !trimmedLine.startsWith('#')) {
          const [key, ...valueParts] = trimmedLine.split('=')
          if (key && valueParts.length > 0) {
            const keyTrimmed = key.trim()
            const valueTrimmed = valueParts.join('=').trim()
            env[keyTrimmed] = valueTrimmed
            if (keyTrimmed.startsWith('VITE_')) {
              console.log(`  Found ${keyTrimmed} = ${valueTrimmed}`)
            }
          }
        }
      })
      console.log('Successfully loaded .env.prod')
    } catch (err) {
      // .env.prod tidak ada, gunakan default
      console.warn('Warning: .env.prod not found, using defaults', err.message)
    }
  }
  
  // Merge with Vite's default env loading
  const viteEnv = loadEnv(mode, process.cwd(), '')
  const mergedEnv = { ...viteEnv, ...env }
  
  return {
    plugins: [svelte()],
    define: {
      // Inject env vars that start with VITE_
      ...Object.keys(mergedEnv)
        .filter(key => key.startsWith('VITE_'))
        .reduce((acc, key) => {
          acc[`import.meta.env.${key}`] = JSON.stringify(mergedEnv[key])
          return acc
        }, {}),
    },
    build: {
      minify: 'terser',
      terserOptions: {
        compress: {
          drop_console: true, // Hapus semua console.* saat production build
          drop_debugger: true, // Hapus debugger statements
        },
      },
    },
    server: {
      port: 5173,
      strictPort: false,
      proxy: {
        '/csrf-token': {
          target: 'http://127.0.0.1:8000',
          changeOrigin: true,
        },
        '/admin': {
          target: 'http://127.0.0.1:8000',
          changeOrigin: true,
        },
        '/register': {
          target: 'http://127.0.0.1:8000',
          changeOrigin: true,
        },
      },
    },
  }
})
