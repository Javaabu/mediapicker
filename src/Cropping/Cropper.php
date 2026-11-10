<?php

namespace Javaabu\Mediapicker\Cropping;

use InvalidArgumentException;
use Javaabu\Mediapicker\Events\MediaCropped;
use Javaabu\Mediapicker\Models\Media;
use Javaabu\Mediapicker\Support\CropPresets;

/**
 * Saves crops on media without touching the original file
 *
 * ```php
 * app(Cropper::class)->crop($media, 'wide', ['x' => 0, 'y' => 120, 'width' => 1600, 'height' => 900]);
 * ```
 */
class Cropper
{
    /**
     * @param  array<string, mixed>  $crop  `x`, `y`, `width`, `height` in source pixels, and `rotation` in degrees
     */
    public function crop(Media $media, string $preset, array $crop): Media
    {
        if (! CropPresets::exists($preset)) {
            throw new InvalidArgumentException("Unknown crop preset [{$preset}].");
        }

        $crop = CropPresets::normalize($crop);

        $crops = $media->getCustomProperty('crops', []);
        $crops[$preset] = $crop;

        $media->setCustomProperty('crops', $crops);
        $media->save();

        MediaCropped::dispatch($media, $preset, $crop);

        return $media;
    }

    /**
     * Remove a preset's crop, so conversions that use it go back to the whole image
     */
    public function forget(Media $media, string $preset): Media
    {
        $crops = $media->getCustomProperty('crops', []);

        unset($crops[$preset]);

        $media->setCustomProperty('crops', $crops);
        $media->save();

        return $media;
    }

    /**
     * @return array{x: int, y: int, width: int, height: int, rotation: int}|null
     */
    public function cropFor(Media $media, string $preset): ?array
    {
        return $media->getCustomProperty("crops.{$preset}");
    }
}
