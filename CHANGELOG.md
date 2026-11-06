# Changelog

All notable changes to `javaabu/mediapicker` are documented in this file.

## 1.0.0 - unreleased

The first release. Compared with the unreleased version on `main` (see `docs/upgrading.md`):

- PHP 8.4 and Laravel 12/13; no javaabu/helpers, javaabu/activitylog 1 or javaabu/forms 1
- `Route::mediapicker()` with a guard, prefix, name, middleware and controller; overridable controller; every action answers JSON
- Permissions and an overridable `MediaPolicy` through javaabu/permissions; a library per guard
- Library pages in Blade (javaabu/forms v2) and Inertia React (@javaabu/forms and Onu, through javaabu/manifest)
- `<x-mediapicker::picker>`, React `MediaPicker`/`MediaPickerField`/`MediaLibraryDialog`, and the stencil `MediaPickerField`; the `LibraryMedia` rule and `syncAttachmentsFromRequest()`
- Fingerprinted attachment conversions: shared between models, never made twice, extra formats (WebP, AVIF), responsive widths; `<x-mediapicker::image>` and React `<Picture>`
- Non-destructive cropping to aspect-ratio presets, on upload and later, driving conversions
- Image, gallery, file and video blocks for javaabu/block-editor
- Optional javaabu/translatable support for names, descriptions, alt text and captions
- Dhivehi translations and RTL layouts
- Docs, `llms.txt`, and a Laravel Boost guideline (in a verbatim block, tested with Boost's own renderer) and skill
