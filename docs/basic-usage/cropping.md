---
title: Cropping
sidebar_position: 5
---

Images are cropped to aspect-ratio presets. A crop is saved on the media, in source pixels, per preset; the original file is never changed. Attachment conversions that use a preset are made from its crop.

# Presets

```php
// config/mediapicker.php
'crop_presets' => [
    'square' => ['label' => 'Square', 'aspect' => 1],
    'wide' => ['label' => 'Wide (16:9)', 'aspect' => 16 / 9],
    'landscape' => ['label' => 'Landscape (4:3)', 'aspect' => 4 / 3],
    'portrait' => ['label' => 'Portrait (3:4)', 'aspect' => 3 / 4],
    'free' => ['label' => 'Free', 'aspect' => null],
],
```

Labels are translated (add yours to `lang/dv.json`). A conversion uses a preset with `->usingCrop('wide')`; without a saved crop it uses the whole image, and its own `fit()` decides the rest.

# In the library

The edit page has the cropper for images: choose a preset, move and resize the box, and save. Saving makes the conversions that use the preset again (queued ones on the queue), for every attachment of the image. The same cropper is on the Blade page (cropperjs) and the React page (`MediaCropper`).

# On upload

React pickers can crop an image before it's uploaded: give the field a preset.

```tsx
<MediaPickerField name="featured_image" type="image" crop="wide" value={image} onChange={setImage} />
```

```php
MediaPickerField::make('featured_image')->mediaType('image')->crop('wide');
```

The dialog shows Onu's `ImageCropper` for each new image, and the crop is saved with the upload. In Blade, uploading from the library goes to the edit page, where the image is cropped.

# From code and the API

```php
use Javaabu\Mediapicker\Cropping\Cropper;

app(Cropper::class)->crop($media, 'wide', ['x' => 0, 'y' => 120, 'width' => 1600, 'height' => 900]);
app(Cropper::class)->forget($media, 'wide');   // back to the whole image
app(Cropper::class)->cropFor($media, 'wide');  // ['x' => 0, 'y' => 120, 'width' => 1600, 'height' => 900, 'rotation' => 0]
```

```http
PUT /media/12/crops/wide
{"x": 0, "y": 120, "width": 1600, "height": 900}
```

Uploads take crops too: `crops[wide][x]`, `crops[wide][y]`, `crops[wide][width]`, `crops[wide][height]`. A crop may have a `rotation` of 0, 90, 180 or 270 degrees (clockwise, applied before cropping); the cropper UIs don't rotate.

Saving a crop fires `Javaabu\Mediapicker\Events\MediaCropped` (`$media`, `$preset`, `$crop`), which the package listens to for regenerating.
