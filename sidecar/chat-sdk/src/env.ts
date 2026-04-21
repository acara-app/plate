import { z } from 'zod'

export const EnvVarsSchema = z.object({
  SIDECAR_HMAC_SECRET: z.string().min(16, 'SIDECAR_HMAC_SECRET must be at least 16 chars'),
  PLATE_APP_URL: z.string().url(),
  LOG_LEVEL: z.enum(['debug', 'info', 'warn', 'error']).default('info'),
})

export type EnvVars = z.infer<typeof EnvVarsSchema>

export interface Bindings extends EnvVars {
  SIDECAR_USER_CACHE: KVNamespace
}

export type HonoEnv = {
  Bindings: Bindings
  Variables: {
    requestId: string
  }
}

export function assertEnv(env: Record<string, unknown>): EnvVars {
  const result = EnvVarsSchema.safeParse(env)
  if (!result.success) {
    const flat = result.error.flatten().fieldErrors
    throw new Error(
      `Invalid sidecar environment. Missing or malformed: ${JSON.stringify(flat)}`,
    )
  }
  return result.data
}
