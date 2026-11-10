import { Button } from "@javaabu/forms"
import { ImageCropper, type PixelCrop } from "@javaabu/onu/media"
import { useMemo, useRef } from "react"

import { translator } from "../strings"
import type { CropPreset, Strings } from "../types"

export interface CropBeforeUploadProps {
  file: File
  preset: CropPreset
  /** The crop in source pixels, or null to upload the whole image. */
  onDone: (crop: PixelCrop | null) => void
  strings?: Partial<Strings> | null
}

/**
 * Crops an image before it's uploaded (Onu's `ImageCropper`). The original is uploaded as
 * is; the crop is saved with it for the preset.
 */
export function CropBeforeUpload({ file, preset, onDone, strings }: CropBeforeUploadProps) {
  const t = useMemo(() => translator(strings), [strings])
  const area = useRef<PixelCrop | null>(null)

  return (
    <div className="mp-cropper flex flex-col gap-3">
      <p className="text-sm">
        <strong>{t("crop_image")}</strong> · {preset.label} · <bdi>{file.name}</bdi>
      </p>
      <ImageCropper
        src={file}
        alt={file.name}
        aspect={preset.aspect ?? undefined}
        rotatable={false}
        onCropComplete={(crop) => (area.current = crop)}
      />
      <div className="flex flex-wrap gap-2">
        <Button type="button" onClick={() => onDone(area.current)}>
          {t("save_crop")}
        </Button>
        <Button type="button" color="secondary" onClick={() => onDone(null)}>
          {t("upload")}
        </Button>
      </div>
    </div>
  )
}
