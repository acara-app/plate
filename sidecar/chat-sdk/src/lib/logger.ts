export type LogLevel = 'debug' | 'info' | 'warn' | 'error'

const LEVELS: Record<LogLevel, number> = {
  debug: 10,
  info: 20,
  warn: 30,
  error: 40,
}

export interface Logger {
  debug(message: string, context?: Record<string, unknown>): void
  info(message: string, context?: Record<string, unknown>): void
  warn(message: string, context?: Record<string, unknown>): void
  error(message: string, context?: Record<string, unknown>): void
  child(extraContext: Record<string, unknown>): Logger
}

export function createLogger(
  level: LogLevel,
  baseContext: Record<string, unknown> = {},
): Logger {
  const threshold = LEVELS[level]

  function log(
    lvl: LogLevel,
    message: string,
    context: Record<string, unknown> = {},
  ): void {
    if (LEVELS[lvl] < threshold) return
    const line = JSON.stringify({
      level: lvl,
      msg: message,
      ts: new Date().toISOString(),
      ...baseContext,
      ...context,
    })
    if (lvl === 'error' || lvl === 'warn') {
      console.error(line)
    } else {
      console.log(line)
    }
  }

  return {
    debug: (m, c) => log('debug', m, c),
    info: (m, c) => log('info', m, c),
    warn: (m, c) => log('warn', m, c),
    error: (m, c) => log('error', m, c),
    child: (extra) => createLogger(level, { ...baseContext, ...extra }),
  }
}
