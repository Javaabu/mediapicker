{{--
    javaabu/mediapicker guidelines for AI code assistants

    Maintained by: Javaabu (https://javaabu.com)
--}}

## javaabu/mediapicker

- This project's uploads live in a media library from `javaabu/mediapicker` (on spatie/laravel-medialibrary v11). Users upload to their library; models such as posts **attach** library media to their own collections (`HasAttachments` + `InteractsWithAttachments`) instead of calling `addMedia()` themselves.
- Validate every picked media id with `new \Javaabu\Mediapicker\Rules\LibraryMedia(type: 'image')` and save with `$model->syncAttachmentsFromRequest('collection')`. Picker fields are `<x-mediapicker::picker>` (Blade), `MediaPickerField` from `@javaabu/mediapicker` (React) and `\Javaabu\Mediapicker\Stencil\MediaPickerField` (stencil).
- Image sizes for attached media are attachment conversions (`addAttachmentConversion()` in `registerAttachmentConversions()`), shown with `<x-mediapicker::image>` or React `<Picture>`. Crops are saved per preset on the media; never edit the original file.
- Always activate the `javaabu-mediapicker` skill when adding uploads or images to a model or form, rendering attached images, declaring conversions, cropping, registering the library pages, using the media JSON API or block editor media blocks, or changing who may see or edit media.
