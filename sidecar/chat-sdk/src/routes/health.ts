import { Hono } from 'hono'
import type { HonoEnv } from '../env'

export const health = new Hono<HonoEnv>()

health.get('/healthz', (c) => c.json({ status: 'ok' }))
health.get('/readyz', (c) => c.json({ status: 'ok' }))
