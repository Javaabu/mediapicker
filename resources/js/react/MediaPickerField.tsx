import { FormGroup, useFieldErrors } from "@javaabu/forms"
import type { ReactNode } from "react"

import { usePickerConfig } from "./context"
import { MediaPicker, type MediaPickerProps } from "./MediaPicker"

export interface MediaPickerFieldProps extends Omit<MediaPickerProps, "url" | "name" | "cropOnUpload"> {
  /** The field name; ids are submitted as `{name}` or `{name}[]`, and errors are read from it. */
  name: string
  label?: ReactNode
  help?: ReactNode
  required?: boolean
  /** Defaults to the provider's library URL. */
  url?: string
  /** A crop preset name: new images are cropped to it before they're uploaded. */
  crop?: string | null
}

/**
 * `MediaPicker` in a form group, with its label, help and validation errors (including
 * `name.0`, …). Bind it by hand with `value` / `onChange` (see Javaabu/react-forms#23).
 */
export function MediaPickerField({ name, label, help, required, url, crop = null, multiple = false, ...props }: MediaPickerFieldProps) {
  const config = usePickerConfig()
  const errors = useFieldErrors(name)
  const id = `${name.replace(/[[\].]+/g, "_")}_picker`
  const preset = crop ? (config?.crop_presets.find((item) => item.name === crop) ?? null) : null

  return (
    <FormGroup name={name} label={label} help={help} required={required}>
      <MediaPicker
        {...props}
        id={id}
        multiple={multiple}
        name={multiple ? `${name}[]` : name}
        url={url ?? config?.url ?? ""}
        types={props.types ?? config?.types}
        accept={props.accept ?? config?.accept}
        strings={props.strings ?? config?.strings}
        cropOnUpload={preset}
        invalid={!!errors?.length}
      />
    </FormGroup>
  )
}
