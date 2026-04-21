import { mockAdapter } from './mock/adapter'
import type { PlatformAdapter } from './types'

// Register new platform adapters here. Keep the left-hand key matching
// `adapter.name`. See docs/ADDING_A_PLATFORM.md for the full walkthrough.
const adapters: Record<string, PlatformAdapter> = {
  [mockAdapter.name]: mockAdapter,
}

export function getAdapter(name: string): PlatformAdapter | null {
  return adapters[name] ?? null
}

export function listAdapters(): string[] {
  return Object.keys(adapters)
}
