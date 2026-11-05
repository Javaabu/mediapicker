/**
 * javaabu/block-editor blocks that pick from the media library: `image` (replacing the
 * core URL image), `gallery`, `file` and `mediaVideo`. The editor requires this script
 * through each block's `$script`; React apps import it once next to the editor.
 */
import "../../css/mediapicker.css"

import { registerBlock } from "@javaabu/block-editor"

import { file } from "./file"
import { gallery } from "./gallery"
import { image } from "./image"
import { mediaVideo } from "./video"

export const MEDIA_BLOCKS = [image, gallery, file, mediaVideo]

registerBlock(...MEDIA_BLOCKS)

export { image, gallery, file, mediaVideo }
export { imageAttrs } from "./image"
export { galleryItems, type GalleryItem } from "./gallery"
export { fileAttrs } from "./file"
export { videoAttrs } from "./video"
export { ImageRender, GalleryRender, FileRender, VideoRender } from "./render"
