---
title: Conversions
sidebar_position: 4
---

A model declares the image sizes its attachments need. They're made for the attachment, from the library file, and kept with the media.

# Declaring conversions

```php
use Javaabu\Mediapicker\Contracts\HasAttachments;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements HasAttachments
{
    use InteractsWithAttachments;

    public function registerAttachmentConversions(?Media $media = null): void
    {
        $this->addAttachmentConversion('card')
            ->usingCrop('wide')                     // the media's 16:9 crop, when it has one
            ->fit(Fit::Crop, 800, 450)
            ->formats(['avif', 'webp'])             // extra copies in modern formats
            ->withResponsiveImages()                // smaller widths for srcset
            ->performOnCollections('featured_image');

        $this->addAttachmentConversion('thumb')
            ->fit(Fit::Crop, 200, 200)
            ->nonQueued()                           // made right away
            ->performOnCollections('featured_image', 'gallery');
    }
}
```

`addAttachmentConversion()` returns an `AttachmentConversion`, which is spatie's `Conversion` (every manipulation, `queued()`, `nonQueued()`, `keepOriginalImageFormat()`, `withResponsiveImages()`, `pdfPageNumber()`, `extractVideoFrameAtSecond()`) with:

| Method | |
| --- | --- |
| `usingCrop('wide')` | Apply the media's crop for this preset first (see [Cropping](cropping.md)) |
| `formats(['avif', 'webp'])` | Also save the conversion in these formats. Formats the image driver can't write are skipped with a warning |

Conversions can also be declared per collection, as with spatie: `$this->addAttachmentCollection('gallery')->registerMediaConversions(fn () => $this->addAttachmentConversion('thumb')->width(200))`.

# Less work

- **Only what a collection needs.** A conversion is made only for the collections in `performOnCollections()` (all of them when it names none), and only for images, PDFs and videos spatie can convert.
- **Queued.** Conversions are queued unless they're `nonQueued()` (following spatie's `queue_conversions_by_default`, queue and connection). The library's own thumbnail is made right away, for the pickers.
- **Never made twice.** Each conversion file name carries a fingerprint of what it does: its manipulations, format, and the crop it uses (`beach-card-3f9a1c0b2e.jpg`, in the media's `conversions/attachments` folder). Attaching media again, or attaching it to another model that declares the same conversion, makes nothing new: identical conversions share one file.
- **No clashes.** Two models can both have a `thumb` of different sizes; they get different files.
- **Only what changed.** A new crop changes the fingerprint of the conversions that use it, so only those are made again. Changing a definition makes new files on the next `mediapicker:regenerate --only-missing`. Old files stay until `mediapicker:clean` removes them, so pages never point at a missing file.

spatie's own `media-library:clean` and file removal leave the `attachments` folder alone, except when the media itself is deleted.

# Showing images

## Blade

```blade
<x-mediapicker::image :model="$post" collection="featured_image" conversion="card" sizes="(min-width: 768px) 50vw, 100vw" class="rounded-lg" />
<x-mediapicker::image :attachment="$attachment" conversion="thumb" />
<x-mediapicker::image :media="$media" />   {{-- a library image, at its preview size --}}
```

It renders `<picture>` with a `<source>` per extra format, `srcset` and `sizes` when there are responsive widths, `width` and `height` (no layout shift), the media's alt text (or `alt`), and `loading="lazy"`. Until a queued conversion is made, it shows the original. It renders nothing when there's no image.

## Inertia React

Prepare the image on the server and render it with `<Picture>`, which outputs the same markup:

```php
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\Support\ImageData;

'image' => Mediapicker::image($post, 'featured_image', 'card', sizes: '100vw'),
'gallery' => ImageData::forCollection($post, 'gallery', 'thumb'),
```

```tsx
import { Picture } from "@javaabu/mediapicker"

<Picture image={props.image} className="rounded-lg" />
```

## By hand

```php
$attachment = $post->getFirstAttachment('featured_image');

$attachment->getAvailableUrl('card');
$attachment->getFormatUrl('card', 'avif');
$attachment->getSrcset('card', 'webp');
$attachment->conversionMetadata('card'); // ['width' => 800, 'height' => 450, 'formats' => ['jpg', 'avif', 'webp'], 'widths' => [669, 560]]
```

# Regenerating

```bash
php artisan mediapicker:regenerate                       # every attachment
php artisan mediapicker:regenerate post --only=card      # one model type, one conversion
php artisan mediapicker:regenerate --only-missing        # only what isn't made yet (after changing a definition)
php artisan mediapicker:clean                            # then remove the files nothing uses
```
