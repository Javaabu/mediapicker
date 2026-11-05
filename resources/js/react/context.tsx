import { createContext, useContext, type ReactNode } from "react"

import type { CropPreset, FileTypeOption, Strings } from "../types"

/** What `Mediapicker::pickerConfig()` gives React: share it with Inertia once. */
export interface PickerConfig {
  /** The library's index URL. */
  url: string
  types: FileTypeOption[]
  accept: string
  max_size: number
  crop_presets: CropPreset[]
  strings: Strings
}

const PickerConfigContext = createContext<PickerConfig | null>(null)

/**
 * Gives every picker inside the library URL, file types and strings, so fields don't repeat
 * them. Share `Mediapicker::pickerConfig()` from your HandleInertiaRequests middleware:
 *
 * ```tsx
 * <MediapickerProvider config={usePage().props.mediapicker}>…</MediapickerProvider>
 * ```
 */
export function MediapickerProvider({
  config,
  children,
}: {
  config: PickerConfig | null | undefined
  children: ReactNode
}) {
  return <PickerConfigContext.Provider value={config ?? null}>{children}</PickerConfigContext.Provider>
}

export function usePickerConfig(): PickerConfig | null {
  return useContext(PickerConfigContext)
}
