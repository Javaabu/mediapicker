# Conversions, crops and blocks

## Conversions

- Declare them on the model that attaches the media, in `registerAttachmentConversions()`, with `performOnCollections()` so each collection gets only what it needs.
- They're queued unless `nonQueued()` (spatie's queue config). Make small ones a page needs right away `nonQueued()`.
- `->formats(['avif', 'webp'])` adds `<picture>` sources; `->withResponsiveImages()` adds srcset widths. Formats the image driver can't write are skipped.
- Files are fingerprinted: identical conversions of different models share a file, and nothing is made twice. After changing a definition: `php artisan mediapicker:regenerate --only-missing`, then `php artisan mediapicker:clean`.
- Read what was made with `$attachment->conversionMetadata('card')`; URLs with `getAvailableUrl()`, `getFormatUrl('card', 'webp')`, `getSrcset('card')`.

## Crops

- Presets are in `config('mediapicker.crop_presets')` (`square`, `wide`, `landscape`, `portrait`, `free`). A conversion uses one with `->usingCrop('wide')`.
- Crops are saved per preset on the media, never on the file: `app(\Javaabu\Mediapicker\Cropping\Cropper::class)->crop($media, 'wide', ['x' => 0, 'y' => 40, 'width' => 1600, 'height' => 900])`. Saving regenerates the conversions that use the preset.
- People crop on the library's edit page, or before upload in React pickers with `crop="wide"`.

## Block editor

The package registers `image` (replacing the core URL image), `gallery`, `file` and `mediaVideo` blocks for javaabu/block-editor. Validate content with `BlockContent` as usual: media ids are checked with `LibraryMedia`. Blade rendering re-reads the media; React's `<BlockContent>` uses the stored snapshot. In React apps, `import "@javaabu/mediapicker/blocks"` once next to the editor.
