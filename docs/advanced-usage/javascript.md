---
title: JavaScript
sidebar_position: 7
---

The package's TypeScript ships inside the Composer package and is imported as `@javaabu/mediapicker` through javaabu/manifest (no npm install). Blade pages get the prebuilt scripts automatically.

# Scripts (manifest assets)

| Asset | Entry | |
| --- | --- | --- |
| `mediapicker::picker` | `js/blade/picker.ts` | Mounts `[data-mediapicker-field]`; `window.Mediapicker.open()`, `.mount()`, `.field()` |
| `mediapicker::cropper` | `js/blade/cropper.ts` | Mounts `[data-mediapicker-cropper]` |
| `mediapicker::blocks` | `js/blocks/index.ts` | The editor blocks |

Each imports `css/mediapicker.css`. Apps without Vite get them from the package's `dist`.

# Exports

```ts
import {
  // React
  MediaPicker, MediaPickerField, MediaLibraryDialog, MediaCropper, CropBeforeUpload,
  MediaPreview, Picture, StencilMediaPicker, MediapickerProvider, usePickerConfig, useLibrary, Icon,
  // framework-free
  openMediaPicker, PickerField, mountPickerFields, pickerField, CropperWidget, mountCroppers,
  // API
  createLibraryClient, LibraryError, csrfHeaders, translator,
} from "@javaabu/mediapicker"

import { image, gallery, file, mediaVideo, ImageRender, GalleryRender } from "@javaabu/mediapicker/blocks"
```

Types: `MediaItem`, `Crop`, `CropPreset`, `ImageData`, `Strings`, `PickerConfig`, and the page props (`MediaIndexProps`, …).

# The picker dialog without a framework

```ts
import { openMediaPicker } from "@javaabu/mediapicker"

const chosen = await openMediaPicker({
  url: "/admin/media",
  type: "image",          // or ["image", "video"]
  multiple: true,
  selected: current,      // MediaItem[] that start selected
  upload: true,
  strings,                // from Mediapicker::pickerConfig()['strings']
})
// MediaItem[], or null when cancelled
```

It's a native `<dialog>`: focus stays inside, Escape cancels, items are toggle buttons with `aria-pressed`, and the selection count is announced.

# npm packages the app needs

The React components import `@javaabu/forms`, `@javaabu/onu` (`MediaLister`, and `ImageCropper` from `@javaabu/onu/media`, which loads `react-easy-crop`, `signature_pad`, `media-chrome`, `yet-another-react-lightbox` and `uqr`), `@inertiajs/react` (pages), `cropperjs` (cropper) and `@tiptap/core` (blocks). Run `php artisan manifest:check` to list what's missing.
