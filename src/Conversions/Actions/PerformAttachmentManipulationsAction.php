<?php

namespace Javaabu\Mediapicker\Conversions\Actions;

use Illuminate\Support\Str;
use Javaabu\Mediapicker\Conversions\AttachmentConversion;
use Spatie\Image\Enums\Orientation;
use Spatie\Image\Image;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Makes one conversion file: the media's crop (when the conversion uses one), then the
 * conversion's manipulations, saved in the conversion's format
 */
class PerformAttachmentManipulationsAction
{
    public function execute(Media $media, AttachmentConversion $conversion, string $image_file): string
    {
        $format = $conversion->getFormat($media);

        $target = pathinfo($image_file, PATHINFO_DIRNAME) . '/' . Str::random(32) . ".{$format}";

        $image = Image::useImageDriver(config('media-library.image_driver'))
            ->loadFile($image_file);

        if ($crop = $conversion->getCrop($media)) {
            $this->applyCrop($image, $crop);
        }

        $conversion->getManipulations()->apply($image);

        $image->format($format)->save($target);

        return $target;
    }

    /**
     * @param  array{x: int, y: int, width: int, height: int, rotation?: int}  $crop
     */
    protected function applyCrop(mixed $image, array $crop): void
    {
        $rotation = ((int) ($crop['rotation'] ?? 0) % 360 + 360) % 360;

        if ($rotation && $orientation = Orientation::tryFrom($rotation)) {
            $image->orientation($orientation);
        }

        $x = max(0, min($crop['x'], $image->getWidth() - 1));
        $y = max(0, min($crop['y'], $image->getHeight() - 1));
        $width = max(1, min($crop['width'], $image->getWidth() - $x));
        $height = max(1, min($crop['height'], $image->getHeight() - $y));

        $image->manualCrop($width, $height, $x, $y);
    }
}
