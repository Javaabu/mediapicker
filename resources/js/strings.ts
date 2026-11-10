import defaultStrings from "./strings.en.json"
import type { StringKey, Strings } from "./types"

/** The English strings, used for any key the server didn't send. */
export const englishStrings: Strings = defaultStrings

/** The server's strings over the English ones. */
export function withDefaults(strings?: Partial<Strings> | null): Strings {
  return { ...englishStrings, ...(strings ?? {}) }
}

/** Replaces `:name` placeholders, longest names first so `:count` isn't cut by `:c`. */
export function format(text: string, replace: Record<string, string | number> = {}): string {
  return Object.keys(replace)
    .sort((a, b) => b.length - a.length)
    .reduce((result, key) => result.split(`:${key}`).join(String(replace[key])), text)
}

/** A translator for a set of strings. */
export function translator(strings?: Partial<Strings> | null) {
  const all = withDefaults(strings)

  return (key: StringKey, replace?: Record<string, string | number>) => format(all[key] ?? key, replace)
}

export type Translator = ReturnType<typeof translator>

/** "1.5 MB", from bytes. */
export function formatBytes(bytes: number): string {
  if (bytes < 1024) return `${bytes} B`
  const units = ["KB", "MB", "GB"]
  let size = bytes / 1024
  let unit = 0
  while (size >= 1024 && unit < units.length - 1) {
    size /= 1024
    unit++
  }
  return `${Math.round(size * 10) / 10} ${units[unit]}`
}
