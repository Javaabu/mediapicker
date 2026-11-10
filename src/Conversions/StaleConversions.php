<?php

namespace Javaabu\Mediapicker\Conversions;

use Illuminate\Support\Facades\Storage;
use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Mediapicker;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Finds attachment conversion files no attachment uses any more: left by a changed
 * definition, a new crop, or a model that no longer declares the conversion
 */
class StaleConversions
{
    /**
     * The file names every attachment of the media still uses
     *
     * @return array<int, string>
     */
    public function expectedFiles(Media $media): array
    {
        $attachment_class = Mediapicker::attachmentModel();

        $files = [];
        $keys = [];

        $attachment_class::query()
            ->where('media_id', $media->getKey())
            ->get()
            ->each(function (Attachment $attachment) use ($media, &$files, &$keys) {
                $attachment->setRelation('media', $media);

                $attachment->conversions()
                    ->filter(fn (Conversion $conversion) => $conversion->shouldBePerformedOn($attachment->collection_name))
                    ->each(function (AttachmentConversion $conversion) use ($media, &$files, &$keys) {
                        $keys[] = $conversion->key($media);

                        foreach ($this->variants($media, $conversion) as $variant) {
                            $files[] = $variant->getConversionFileName($media);

                            foreach (ConversionMetadata::get($media, $conversion->key($media))['widths'] ?? [] as $width) {
                                $files[] = $variant->forWidth($media, $width)->getConversionFileName($media);
                            }
                        }
                    });
            });

        $this->expected_keys = array_values(array_unique($keys));

        return array_values(array_unique($files));
    }

    /** @var array<int, string> */
    protected array $expected_keys = [];

    /**
     * The stale files, relative to the conversions disk root
     *
     * @return array<int, string>
     */
    public function find(Media $media): array
    {
        $expected = $this->expectedFiles($media);
        $directory = PathFor::directory($media);
        $disk = Storage::disk($media->conversions_disk ?: $media->disk);

        if (! $disk->directoryExists($directory)) {
            return [];
        }

        return collect($disk->files($directory))
            ->reject(fn (string $path) => in_array(basename($path), $expected, true))
            ->values()
            ->all();
    }

    /**
     * Delete the stale files and forget their metadata
     *
     * @return array<int, string> the deleted files
     */
    public function clean(Media $media): array
    {
        $stale = $this->find($media);

        if ($stale) {
            Storage::disk($media->conversions_disk ?: $media->disk)->delete($stale);
        }

        $stale_keys = array_diff(array_keys(ConversionMetadata::all($media)), $this->expected_keys);

        if ($stale_keys) {
            ConversionMetadata::forget($media, $stale_keys);
        }

        return $stale;
    }

    /**
     * @return array<int, AttachmentConversion>
     */
    protected function variants(Media $media, AttachmentConversion $conversion): array
    {
        $variants = [$conversion];

        foreach ($conversion->getFormats() as $format) {
            if ($format !== $conversion->getFormat($media)) {
                $variants[] = $conversion->forFormat($media, $format);
            }
        }

        return $variants;
    }
}
