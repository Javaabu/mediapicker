/**
 * The Blade entry for the cropper on the media edit page: mounts every
 * `[data-mediapicker-cropper]`.
 */
import "../../css/mediapicker.css"

import { mountCroppers } from "../cropper/cropper"
import { onReady, watch } from "./mount"

onReady(() => {
  mountCroppers()
  watch(() => mountCroppers())
})
