/// <reference path="../node_modules/@cloudflare/vitest-pool-workers/types/cloudflare-test.d.ts" />

declare namespace Cloudflare {
  interface Env {
    SIDECAR_HMAC_SECRET: string
    PLATE_APP_URL: string
    LOG_LEVEL: 'debug' | 'info' | 'warn' | 'error'
    SIDECAR_USER_CACHE: KVNamespace
  }
}
