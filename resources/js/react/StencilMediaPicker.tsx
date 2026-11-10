import type { StencilComponentProps } from "@javaabu/stencil"
import { useState } from "react"

import type { CropPreset, FileTypeOption, MediaItem, Strings } from "../types"
import { MediapickerProvider, usePickerConfig } from "./context"
import { MediaPickerField } from "./MediaPickerField"

/**
 * The React component of the stencil `mediapicker` field type
 * (`Javaabu\Mediapicker\Stencil\MediaPickerField`). Register it once:
 *
 * ```tsx
 * <StencilProvider components={{ mediapicker: StencilMediaPicker }}>
 * ```
 */
export function StencilMediaPicker({ node }: StencilComponentProps) {
  const props = (node.props ?? {}) as {
    label?: string
    help?: string
    required?: boolean
    disabled?: boolean
    multiple?: boolean
    type?: string[]
    url?: string
    crop?: string | null
    value?: MediaItem[]
    types?: FileTypeOption[]
    accept?: string
    cropPresets?: CropPreset[]
    strings?: Strings
    maxSize?: number
  }

  const inherited = usePickerConfig()
  const multiple = props.multiple ?? false
  const [value, setValue] = useState<MediaItem | MediaItem[] | null>(
    multiple ? (props.value ?? []) : (props.value?.[0] ?? null),
  )

  const config = {
    url: props.url ?? inherited?.url ?? "",
    types: props.types ?? inherited?.types ?? [],
    accept: props.accept ?? inherited?.accept ?? "",
    max_size: props.maxSize ?? inherited?.max_size ?? 0,
    crop_presets: props.cropPresets ?? inherited?.crop_presets ?? [],
    strings: (props.strings ?? inherited?.strings) as Strings,
  }

  return (
    <MediapickerProvider config={config}>
      <MediaPickerField
        name={node.name ?? node.key}
        label={props.label}
        help={props.help}
        required={props.required}
        disabled={props.disabled}
        multiple={multiple}
        type={props.type ?? null}
        crop={props.crop ?? null}
        value={value}
        onChange={setValue}
      />
    </MediapickerProvider>
  )
}
