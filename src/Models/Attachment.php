<?php

namespace Javaabu\Mediapicker\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\UrlGenerator\AttachmentUrlGeneratorFactory;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\IsSorted;

class Attachment extends Model implements \Javaabu\Mediapicker\Contracts\Attachment
{
    use IsSorted;

    /**
     * An attachment belongs to a media item
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Mediapicker::mediaModel());
    }

    /**
     * An attachment belongs to a model
     */
    public function model(): MorphTo
    {
        return $this->morphTo('model');
    }

    /*
     * Get all the names of the registered media conversions.
     */
    public function getMediaConversionNames(): array
    {
        $conversions = AttachmentConversionCollection::createForAttachment($this);

        return $conversions->map(function (Conversion $conversion) {
            return $conversion->getName();
        })->toArray();
    }

    /**
     * Check if has a generated conversion
     */
    public function hasGeneratedConversion(string $conversionName): bool
    {
        $media = $this->media;

        return $media ? $media->hasGeneratedConversion($conversionName) : false;
    }

    /**
     * Mark conversion as generated
     */
    public function markAsConversionGenerated(string $conversionName, bool $generated): self
    {
        $media = $this->media;

        if ($media) {
            $media->markAsConversionGenerated($conversionName, $generated);
        }

        return $this;
    }

    /**
     * Get the path to the original media file.
     */
    public function getPath(string $conversionName = ''): string
    {
        $urlGenerator = AttachmentUrlGeneratorFactory::createForAttachment($this, $conversionName);

        return $urlGenerator->getPath();
    }

    /**
     * Get the url to the original media file.
     */
    public function getUrl(string $conversionName = ''): string
    {
        $urlGenerator = AttachmentUrlGeneratorFactory::createForAttachment($this, $conversionName);

        return $urlGenerator->getUrl();
    }

    /**
     * Get temporary url
     */
    public function getTemporaryUrl(DateTimeInterface $expiration, string $conversionName = '', array $options = []): string
    {
        $urlGenerator = AttachmentUrlGeneratorFactory::createForAttachment($this, $conversionName);

        return $urlGenerator->getTemporaryUrl($expiration, $options);
    }
}
