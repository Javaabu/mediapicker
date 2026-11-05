import { useEffect, useRef } from "react"

import { CropperWidget } from "../cropper/cropper"
import type { CropPreset, MediaItem, Strings } from "../types"

export interface MediaCropperProps {
  /** The library's index URL. */
  url: string
  media: MediaItem
  presets: CropPreset[]
  /** Called with the updated media after a crop is saved. */
  onCropped?: (media: MediaItem, preset: string) => void
  strings?: Partial<Strings> | null
  className?: string
}

/**
 * Crops a library image per preset, without changing the original: the same cropper as the
 * Blade edit page. Conversions that use the preset are made again from the new crop.
 */
export function MediaCropper({ url, media, presets, onCropped, strings, className }: MediaCropperProps) {
  const element = useRef<HTMLDivElement>(null)
  const callback = useRef(onCropped)

  useEffect(() => {
    callback.current = onCropped
  }, [onCropped])

  useEffect(() => {
    const root = element.current
    if (!root || !presets.length) return

    const widget = new CropperWidget(root, { url, media, presets, strings: strings ?? undefined })
    const listener = (event: Event) => {
      const detail = (event as CustomEvent<{ media: MediaItem; preset: string }>).detail
      callback.current?.(detail.media, detail.preset)
    }
    root.addEventListener("mediapicker:cropped", listener)

    return () => {
      root.removeEventListener("mediapicker:cropped", listener)
      widget.destroy()
    }
    // the widget is rebuilt for another image
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [url, media.id, media.url])

  return <div ref={element} className={className} />
}
