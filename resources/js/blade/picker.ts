/**
 * The Blade entry for `<x-mediapicker::picker>` and the library pages: mounts every
 * `[data-mediapicker-field]`, now and when added later, and exposes the picker dialog as
 * `window.Mediapicker.open()`.
 */
import "../../css/mediapicker.css"

import { openMediaPicker } from "../picker/dialog"
import { mountPickerFields, pickerField } from "../picker/field"
import { onReady, watch } from "./mount"

declare global {
  interface Window {
    Mediapicker?: {
      open: typeof openMediaPicker
      mount: typeof mountPickerFields
      field: typeof pickerField
    }
  }
}

window.Mediapicker = { ...(window.Mediapicker ?? {}), open: openMediaPicker, mount: mountPickerFields, field: pickerField }

onReady(() => {
  mountPickerFields()
  watch(() => mountPickerFields())
})
