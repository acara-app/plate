import { cloudflareTest } from '@cloudflare/vitest-pool-workers'
import { defineConfig } from 'vitest/config'

export default defineConfig({
  plugins: [
    cloudflareTest({
      wrangler: { configPath: './wrangler.toml' },
      miniflare: {
        bindings: {
          SIDECAR_HMAC_SECRET: 'test-secret-1234567890abcdef',
          PLATE_APP_URL: 'https://plate.test',
          LOG_LEVEL: 'error',
        },
        kvNamespaces: ['SIDECAR_USER_CACHE'],
      },
    }),
  ],
})
