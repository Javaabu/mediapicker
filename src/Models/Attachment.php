<?php

namespace Javaabu\Mediapicker\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Javaabu\Mediapicker\Contracts\Attachment as AttachmentContract;
use Javaabu\Mediapicker\Conversions\AttachmentConversion;
use Javaabu\Mediapicker\Conversions\AttachmentConversionCollection;
use Javaabu\Mediapicker\Conversions\ConversionMetadata;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\UrlGenerator\AttachmentUrlGeneratorFactory;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\IsSorted;

/**
 * A library media attached to a model, in one of its attachment collections
 *
 * @property int $id
 * @property int $media_id
 * @property string $collection_name
 * @property ?int $order_column
 * @property-read ?Media $media
 */
class Attachment extends Model implements AttachmentContract
{
    use IsSorted;

    /** @var array<string, AttachmentConversionCollection> */
    protected array $conversion_collections = [];

    public function media(): BelongsTo
    {
        return $this->belongsTo(Mediapicker::mediaModel());
    }

    public function model(): MorphTo
    {
        return $this->morphTo('model');
    }

    /**
     * The names of the conversions the attached model declares
     *
     * @return array<int, string>
     */
    public function getMediaConversionNames(): array
    {
        return $this->conversions()
            ->map(fn (Conversion $conversion) => $conversion->getName())
            ->values()
            ->all();
    }

    /**
     * The conversions the attached model declares for this attachment's collection
     */
    public function conversions(): AttachmentConversionCollection
    {
        $key = "{$this->model_type}:{$this->collection_name}:{$this->media_id}";

        return $this->conversion_collections[$key] ??= AttachmentConversionCollection::createForAttachment($this);
    }

    /**
     * A conversion by name, or null when the model doesn't declare it
     */
    public function conversion(string $conversion_name): ?AttachmentConversion
    {
        $conversion = $this->conversions()->first(fn (Conversion $conversion) => $conversion->getName() === $conversion_name);

        return $conversion instanceof AttachmentConversion ? $conversion : null;
    }

    /**
     * Whether the conversion's files for the current media and crop were generated
     */
    public function hasGeneratedConversion(string $conversionName): bool
    {
        $conversion = $this->conversion($conversionName);

        if (! $conversion || ! $this->media) {
            return false;
        }

        return ConversionMetadata::has($this->media, $conversion->key($this->media));
    }

    /**
     * What was generated for a conversion: width, height, formats and responsive widths
     *
     * @return array{width?: ?int, height?: ?int, formats?: array<int, string>, widths?: array<int, int>}|null
     */
    public function conversionMetadata(string $conversion_name): ?array
    {
        $conversion = $this->conversion($conversion_name);

        if (! $conversion || ! $this->media) {
            return null;
        }

        return ConversionMetadata::get($this->media, $conversion->key($this->media));
    }

    /**
     * Kept for the contract: generation is recorded per fingerprint by the conversion action
     */
    public function markAsConversionGenerated(string $conversionName, bool $generated): self
    {
        $this->media?->markAsConversionGenerated($conversionName, $generated);

        return $this;
    }

    /**
     * The path of the original file, or of a conversion
     */
    public function getPath(string $conversionName = ''): string
    {
        return AttachmentUrlGeneratorFactory::createForAttachment($this, $conversionName)->getPath();
    }

    /**
     * The URL of the original file, or of a conversion (whether or not it's generated yet)
     */
    public function getUrl(string $conversionName = ''): string
    {
        return AttachmentUrlGeneratorFactory::createForAttachment($this, $conversionName)->getUrl();
    }

    public function getTemporaryUrl(DateTimeInterface $expiration, string $conversionName = '', array $options = []): string
    {
        return AttachmentUrlGeneratorFactory::createForAttachment($this, $conversionName)->getTemporaryUrl($expiration, $options);
    }

    /**
     * The URL of the first of the conversions that is generated, else of the original file.
     * Use it in pages, so queued conversions never show a broken image.
     *
     * @param  string|array<int, string>  $conversion_names
     */
    public function getAvailableUrl(string|array $conversion_names = []): string
    {
        foreach ((array) $conversion_names as $conversion_name) {
            if ($this->hasGeneratedConversion($conversion_name)) {
                return $this->getUrl($conversion_name);
            }
        }

        return $this->getUrl();
    }

    /**
     * The URL of a conversion's copy in another format (`webp`, `avif`), once generated
     */
    public function getFormatUrl(string $conversion_name, string $format): ?string
    {
        $conversion = $this->conversion($conversion_name);
        $metadata = $this->conversionMetadata($conversion_name);

        if (! $conversion || ! in_array($format, $metadata['formats'] ?? [], true)) {
            return null;
        }

        $variant = $format === $conversion->getFormat($this->media)
            ? $conversion
            : $conversion->forFormat($this->media, $format);

        return AttachmentUrlGeneratorFactory::createForConversion($this, $variant)->getUrl();
    }

    /**
     * A srcset for a conversion (in its own format, or another generated one): its responsive
     * widths and its full width. Empty until the conversion is generated.
     */
    public function getSrcset(string $conversion_name, ?string $format = null): string
    {
        $conversion = $this->conversion($conversion_name);
        $metadata = $this->conversionMetadata($conversion_name);

        if (! $conversion || ! $metadata) {
            return '';
        }

        $format ??= $conversion->getFormat($this->media);

        if (! in_array($format, $metadata['formats'] ?? [], true)) {
            return '';
        }

        $variant = $format === $conversion->getFormat($this->media)
            ? $conversion
            : $conversion->forFormat($this->media, $format);

        $sources = [];

        if ($metadata['width'] ?? null) {
            $sources[] = AttachmentUrlGeneratorFactory::createForConversion($this, $variant)->getUrl() . " {$metadata['width']}w";
        }

        foreach ($metadata['widths'] ?? [] as $width) {
            $sources[] = AttachmentUrlGeneratorFactory::createForConversion($this, $variant->forWidth($this->media, $width))->getUrl() . " {$width}w";
        }

        return implode(', ', $sources);
    }
}
