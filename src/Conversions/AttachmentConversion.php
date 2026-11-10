<?php

namespace Javaabu\Mediapicker\Conversions;

use Illuminate\Support\Arr;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A conversion a model declares for the media attached to it
 *
 * ```php
 * $this->addAttachmentConversion('card')
 *     ->usingCrop('wide')                 // the media's `wide` crop, when it has one
 *     ->fit(Fit::Crop, 800, 450)
 *     ->formats(['avif', 'webp'])         // extra copies in modern formats
 *     ->withResponsiveImages()            // smaller widths for srcset
 *     ->performOnCollections('featured_image');
 * ```
 *
 * Its file name carries a fingerprint of what it does (manipulations, format and crop):
 * `{file}-{conversion}-{fingerprint}.{ext}` in the media's `conversions/attachments` folder.
 * So identical conversions declared by different models share one file, same-named
 * conversions that differ don't overwrite each other, unchanged media is never processed
 * again, and a new crop or definition gets a new file.
 */
class AttachmentConversion extends Conversion
{
    /** The folder inside the media's conversions folder */
    public const string DIRECTORY = 'attachments';

    protected ?string $crop_preset = null;

    /** @var array<int, string> */
    protected array $formats = [];

    /** Set on copies made for an extra format or a responsive width */
    protected ?string $base_fingerprint = null;

    protected ?int $responsive_width = null;

    /**
     * Apply the media's crop for this preset (see `config('mediapicker.crop_presets')`) before
     * the other manipulations. Media without that crop use the whole image.
     */
    public function usingCrop(?string $preset): static
    {
        $this->crop_preset = $preset;

        return $this;
    }

    public function getCropPreset(): ?string
    {
        return $this->crop_preset;
    }

    /**
     * Extra copies in other formats, for `<picture>` sources: `['avif', 'webp']`
     *
     * @param  string|array<int, string>  $formats
     */
    public function formats(string|array $formats): static
    {
        $this->formats = array_values(array_unique(array_map('strtolower', Arr::wrap($formats))));

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getFormats(): array
    {
        return $this->formats;
    }

    /**
     * The crop this conversion applies to the media, if any
     *
     * @return array{x: int, y: int, width: int, height: int, rotation: int}|null
     */
    public function getCrop(Media $media): ?array
    {
        if (! $this->crop_preset) {
            return null;
        }

        $crop = $media->getCustomProperty("crops.{$this->crop_preset}");

        return is_array($crop) ? $crop : null;
    }

    /**
     * A short hash of what this conversion does to this media
     */
    public function fingerprint(Media $media): string
    {
        if ($this->base_fingerprint) {
            return $this->base_fingerprint;
        }

        $manipulations = Arr::except($this->getManipulations()->toArray(), ['optimize']);

        return substr(sha1(json_encode([
            $manipulations,
            $this->getResultExtension($this->originalExtension($media)),
            $this->getCrop($media),
            $this->getExtractVideoFrameAtSecond(),
            $this->getPdfPageNumber(),
        ])), 0, 10);
    }

    /**
     * The key of this conversion's files in the media's metadata: `{conversion}-{fingerprint}`
     */
    public function key(Media $media): string
    {
        return "{$this->getName()}-{$this->fingerprint($media)}";
    }

    /**
     * The file, relative to the media's conversions folder, so URL and path generators
     * resolve it
     */
    public function getConversionFile(Media $media): string
    {
        return self::DIRECTORY . '/' . $this->getConversionFileName($media);
    }

    public function getConversionFileName(Media $media): string
    {
        $file = pathinfo($media->file_name, PATHINFO_FILENAME);
        $width = $this->responsive_width ? "___{$this->responsive_width}" : '';
        $extension = $this->getResultExtension($this->originalExtension($media)) ?: $this->originalExtension($media);

        return "{$file}-{$this->getName()}-{$this->fingerprint($media)}{$width}.{$extension}";
    }

    /**
     * The same conversion, saved in another format
     */
    public function forFormat(Media $media, string $format): static
    {
        $variant = clone $this;
        $variant->manipulations = clone $this->manipulations;
        $variant->base_fingerprint = $this->fingerprint($media);
        $variant->formats = [];
        $variant->keepOriginalImageFormat = false;
        $variant->format($format);

        return $variant;
    }

    /**
     * This conversion's file scaled down to a width, for srcset
     */
    public function forWidth(Media $media, int $width): static
    {
        $variant = clone $this;
        $variant->base_fingerprint = $this->fingerprint($media);
        $variant->responsive_width = $width;

        return $variant;
    }

    public function getResponsiveWidth(): ?int
    {
        return $this->responsive_width;
    }

    /**
     * The format of this conversion's main file
     */
    public function getFormat(Media $media): string
    {
        return strtolower($this->getResultExtension($this->originalExtension($media)) ?: $this->originalExtension($media));
    }

    protected function originalExtension(Media $media): string
    {
        return strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION));
    }
}
