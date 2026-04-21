import { describe, expect, it } from 'vitest'
import { signHmac, verifyHmac } from '../src/lib/hmac'

const SECRET = 'test-secret-1234567890abcdef'

describe('hmac', () => {
  it('signs and verifies a roundtrip', async () => {
    const payload = '1700000000.{"hello":"world"}'
    const sig = await signHmac(SECRET, payload)

    expect(sig).toMatch(/^[0-9a-f]+$/)
    await expect(verifyHmac(SECRET, payload, sig)).resolves.toBe(true)
  })

  it('rejects a tampered payload', async () => {
    const sig = await signHmac(SECRET, 'original')
    await expect(verifyHmac(SECRET, 'tampered', sig)).resolves.toBe(false)
  })

  it('rejects a wrong secret', async () => {
    const sig = await signHmac(SECRET, 'payload')
    await expect(verifyHmac('other-secret-xxxxxxxxxxxx', 'payload', sig)).resolves.toBe(false)
  })

  it('rejects a non-hex signature', async () => {
    await expect(verifyHmac(SECRET, 'any', 'not-hex-xyz')).resolves.toBe(false)
  })

  it('rejects an odd-length hex signature', async () => {
    await expect(verifyHmac(SECRET, 'any', 'abc')).resolves.toBe(false)
  })
})
