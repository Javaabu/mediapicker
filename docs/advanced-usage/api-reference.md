---
title: API reference
sidebar_position: 8
---

# Javaabu\Mediapicker\Mediapicker

| Method | |
| --- | --- |
| `url(string $action, ?Media $media = null, array $parameters = [], ?string $route_name = null): string` | A library URL. Throws when no library is registered |
| `routeName(?string $guard = null): string` | The library to link to: the current request's, the signed-in guard's, else the default |
| `pickerConfig(?string $route_name = null): array` | `url`, `types`, `accept`, `max_size`, `crop_presets`, `strings` for React |
| `image(Model\|Attachment\|null $subject, string $collection = 'default', string $conversion = '', ?string $sizes = null): ?array` | `ImageData` for `<Picture>` |
| `mediaModel()`, `attachmentModel()`, `mediaController()`, `collectionName()` | Classes and names from config |
| `ignoreMigrations()` | Don't load the package's migrations |

# Routing

`Route::mediapicker(array $options = [])` with `guard`, `prefix`, `name`, `middleware`, `controller`. `Routing\MediapickerRoutes::guard($request)`, `currentName($request)`, `nameFor($guard)`, `registered()`.

# Contracts and traits

**`Contracts\MediaOwner`** (trait `Concerns\OwnsMedia`): `registerMediapickerCollections()`, `registerMediapickerConversions()`, `getMediapickerCollectionName()`, `ownsMedia(Media $media)`.

**`Contracts\HasAttachments`** (trait `Concerns\InteractsWithAttachments`):

| Method | |
| --- | --- |
| `attachments(): MorphMany` | |
| `addAttachment(int\|string\|Media $media): MediaAdder` | Then `->setOrder(?int)` and `->toAttachmentCollection($name)` |
| `addAttachmentFromRequest(string $key)`, `addMultipleAttachmentsFromRequest(array $keys)` | |
| `updateAttachmentMedia(array $media_ids, string $collection = 'default'): Collection` | Exactly these, in order; `[]` clears |
| `syncAttachmentsFromRequest(string $collection, ?string $key = null, ?Request $request = null): ?Collection` | From a picker field; null when the field is absent |
| `updateAttachments(array $attachments, string $collection = 'default')` | Reorder and remove by attachment ids |
| `hasAttachments()`, `getAttachments()`, `getAttachmentMedia()`, `getFirstAttachment()`, `getFirstAttachmentMedia()` | `($collection = 'default', $filters = [])` |
| `getFirstAttachmentUrl()`, `getFirstAttachmentPath()`, `getFirstAttachmentTemporaryUrl()` | `($collection, $conversion)` |
| `deleteAttachment()`, `clearAttachmentCollection()`, `clearAttachmentCollectionExcept()` | |
| `addAttachmentCollection(string $name): MediaCollection` | In `registerAttachmentCollections()` |
| `addAttachmentConversion(string $name): AttachmentConversion` | In `registerAttachmentConversions(?Media $media = null)` |
| `scopeWithAttachments()` | Eager loads `attachments.media` |

**`Contracts\Attachment`** (`Models\Attachment`): `media()`, `model()`, `conversions()`, `conversion($name)`, `conversionMetadata($name)`, `hasGeneratedConversion($name)`, `getUrl($conversion = '')`, `getPath()`, `getTemporaryUrl()`, `getAvailableUrl($conversions)`, `getFormatUrl($conversion, $format)`, `getSrcset($conversion, $format = null)`.

# Models\Media

Extends spatie's `Media`. Attributes: `description`, `alt`, `caption`, `width`, `height` (custom properties), `file_type`, `short_name`, `admin_url`. Methods: `getIcon()`, `isImage()`, `altText()`, `isOwnedBy(?Model $user)`, `libraryUrl($action = 'show')`. Scopes: `hasFileType($types)`, `search($text)`, `inLibraryOf($user)`, `visibleTo($user)`, `ownedBy($user)`.

`Models\Translatable\Media` adds javaabu/translatable's JSON translations of `name`, `description`, `alt`, `caption`.

# Conversions

**`Conversions\AttachmentConversion`** extends spatie's `Conversion`: `usingCrop(?string $preset)`, `formats(string|array $formats)`, `getCropPreset()`, `getFormats()`, `getCrop($media)`, `fingerprint($media)`, `key($media)`, `getConversionFile($media)`, `forFormat($media, $format)`, `forWidth($media, $width)`.

`Conversions\MediaManipulator::createDerivedAttachmentFiles($attachment, $only = [], $only_missing = false, $with_responsive_images = false, $queue_all = false)`. `Conversions\StaleConversions::find($media)` and `clean($media)`. `Conversions\ConversionMetadata::get($media, $key)`. `Conversions\FormatSupport::supports($format)`.

Events: `Conversions\Events\AttachmentConversionWillStartEvent`, `AttachmentConversionHasBeenCompletedEvent` (`$attachment`, `$conversion`), `Events\MediaCropped` (`$media`, `$preset`, `$crop`), `AttachmentCollections\Events\AttachmentCollectionHasBeenClearedEvent`.

# Cropping

`Cropping\Cropper`: `crop(Media $media, string $preset, array $crop)`, `forget($media, $preset)`, `cropFor($media, $preset)`. `Support\CropPresets`: `all()`, `names()`, `exists()`, `aspect()`, `forUi()`, `rules()`, `cropRules()`, `normalize()`.

# Validation and fields

- `Rules\LibraryMedia(string|array|null $type = null, ?Model $user = null)`: ids of media the user may see, of these types. Null and empty values pass.
- `Views\Components\Picker`: `<x-mediapicker::picker>`. `Views\Components\Image`: `<x-mediapicker::image>`.
- `Stencil\MediaPickerField`: `collection()`, `mediaType()`, `multiple()`, `crop()`, `library()`, `upload()`. `Stencil\MediaDetailsForm`.
- `Http\Resources\MediaResource`, `->forLibrary(?string $route_name)`; `MediaResource::conversionUrl($media, $conversion)`.

# Support

`Support\FileTypes`: `all()`, `types()`, `exists()`, `mimeTypes()`, `isAllowed()`, `typeOf()`, `icon()`, `maxSize()`, `rules()`. `Support\PickerConfig::make()`, `Support\ImageData::for()` and `forCollection()`, `Support\Strings::all()`, `Support\Translations::enabled()`, `supports()`, `forMedia()`, `rules()`, `Support\Icons::svg()`.

# Blocks

`Blocks\Image`, `Blocks\Gallery`, `Blocks\File`, `Blocks\MediaVideo` (all `Blocks\MediaBlock`, with `MediaBlock::$library` for the route name prefix of the library to pick from).
