import type { Bindings } from '../env'

const CACHE_TTL_SECONDS = 60 * 60 * 24

function key(platform: string, platformUserId: string): string {
  return `link:${platform}:${platformUserId}`
}

export interface CachedLink {
  plateUserId: string
  linkedAt: number
}

export async function getCachedLink(
  env: Bindings,
  platform: string,
  platformUserId: string,
): Promise<CachedLink | null> {
  const raw = await env.SIDECAR_USER_CACHE.get<CachedLink>(key(platform, platformUserId), 'json')
  return raw ?? null
}

export async function setCachedLink(
  env: Bindings,
  platform: string,
  platformUserId: string,
  value: CachedLink,
): Promise<void> {
  await env.SIDECAR_USER_CACHE.put(
    key(platform, platformUserId),
    JSON.stringify(value),
    { expirationTtl: CACHE_TTL_SECONDS },
  )
}

export async function invalidateCachedLink(
  env: Bindings,
  platform: string,
  platformUserId: string,
): Promise<void> {
  await env.SIDECAR_USER_CACHE.delete(key(platform, platformUserId))
}
