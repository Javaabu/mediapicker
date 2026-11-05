<?php

namespace Javaabu\Mediapicker\Conversions\Actions;

use Illuminate\Support\Facades\Log;
use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Conversions\AttachmentConversion;
use Javaabu\Mediapicker\Conversions\ConversionMetadata;
use Javaabu\Mediapicker\Conversions\Events\AttachmentConversionHasBeenCompletedEvent;
use Javaabu\Mediapicker\Conversions\Events\AttachmentConversionWillStartEvent;
use Javaabu\Mediapicker\Conversions\FormatSupport;
use Spatie\Image\Image;
use Spatie\MediaLibrary\Conversions\ImageGenerators\ImageGeneratorFactory;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Generates an attachment conversion: its main file, a copy per extra format, and smaller
 * widths of each when it has responsive images, then records them on the media
 */
class PerformAttachmentConversionAction
{
    public function execute(AttachmentConversion $conversion, Attachment $attachment, string $copied_original_file): void
    {
        $media = $attachment->media;

        $image_generator = ImageGeneratorFactory::forMedia($media);

        $base_image = $image_generator?->convert($copied_original_file, $conversion);

        if (! $base_image) {
            return;
        }

        event(new AttachmentConversionWillStartEvent($attachment, $conversion, $base_image));

        $metadata = ['width' => null, 'height' => null, 'formats' => [], 'widths' => []];

        foreach ($this->variants($media, $conversion) as $index => $variant) {
            $file = $this->renameInLocalDirectory(
                (new PerformAttachmentManipulationsAction)->execute($media, $variant, $base_image),
                $variant->getConversionFileName($media),
            );

            if ($index === 0) {
                [$metadata['width'], $metadata['height']] = $this->dimensions($file);
            }

            $metadata['formats'][] = $variant->getFormat($media);

            if ($conversion->shouldGenerateResponsiveImages()) {
                $metadata['widths'] = $this->generateWidths($media, $variant, $file, $metadata['width']);
            }

            $this->copyToMediaLibrary($file, $media, $variant);
        }

        ConversionMetadata::put($media, $conversion->key($media), $metadata);

        event(new AttachmentConversionHasBeenCompletedEvent($attachment, $conversion));
    }

    /**
     * The conversion, then a copy for each extra format
     *
     * @return array<int, AttachmentConversion>
     */
    protected function variants(Media $media, AttachmentConversion $conversion): array
    {
        $main_format = $conversion->getFormat($media);

        $variants = [$conversion];

        foreach ($conversion->getFormats() as $format) {
            if ($format === $main_format) {
                continue;
            }

            if (! FormatSupport::supports($format)) {
                Log::warning("Mediapicker: the image driver can't write {$format}, so the {$conversion->getName()} conversion has no {$format} copy.");

                continue;
            }

            $variants[] = $conversion->forFormat($media, $format);
        }

        return $variants;
    }

    /**
     * Smaller copies for srcset, at the widths spatie's width calculator picks below the
     * conversion's own width
     *
     * @return array<int, int>
     */
    protected function generateWidths(Media $media, AttachmentConversion $variant, string $file, ?int $full_width): array
    {
        $widths = app(config('media-library.responsive_images.width_calculator'))
            ->calculateWidthsFromFile($file)
            ->filter(fn (int $width) => ! $full_width || $width < $full_width)
            ->sortDesc()
            ->values()
            ->all();

        foreach ($widths as $width) {
            $sized = $variant->forWidth($media, $width);
            $target = pathinfo($file, PATHINFO_DIRNAME) . '/' . $sized->getConversionFileName($media);

            Image::useImageDriver(config('media-library.image_driver'))
                ->loadFile($file)
                ->width($width)
                ->format($variant->getFormat($media))
                ->save($target);

            $this->copyToMediaLibrary($target, $media, $sized);
        }

        return $widths;
    }

    protected function copyToMediaLibrary(string $file, Media $media, AttachmentConversion $conversion): void
    {
        app(Filesystem::class)->copyToMediaLibrary($file, $media, 'conversions', $conversion->getConversionFile($media));
    }

    /**
     * @return array{0: ?int, 1: ?int}
     */
    protected function dimensions(string $file): array
    {
        $size = @getimagesize($file);

        return $size ? [$size[0], $size[1]] : [null, null];
    }

    protected function renameInLocalDirectory(string $file, string $new_name): string
    {
        $target = pathinfo($file, PATHINFO_DIRNAME) . '/' . $new_name;

        rename($file, $target);

        return $target;
    }
}
