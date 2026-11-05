<?php

namespace Javaabu\Mediapicker\Concerns;

use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\Support\FileTypes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait OwnsMedia
{
    public function getMediapickerCollectionName(): string
    {
        return Mediapicker::collectionName();
    }

    /**
     * Register the library collection, accepting the file types in config('mediapicker.file_types')
     */
    public function registerMediapickerCollections(): void
    {
        $this->addMediaCollection($this->getMediapickerCollectionName())
            ->acceptsFile(function (File $file) {
                return FileTypes::isAllowed($file->mimeType);
            });
    }

    /**
     * Register the library conversions: a square thumbnail, made right away so pickers can show
     * it, and a queued preview
     */
    public function registerMediapickerConversions(?Media $media = null): void
    {
        $this->addMediaConversion('mediapicker-large')
            ->fit(Fit::Max, 1200, 1200)
            ->width(1200)
            ->height(1200)
            ->keepOriginalImageFormat()
            ->shouldBePerformedOn($this->getMediapickerCollectionName());

        $this->addMediaConversion('mediapicker-thumb')
            ->width(250)
            ->height(250)
            ->fit(Fit::Crop, 250, 250)
            ->keepOriginalImageFormat()
            ->nonQueued()
            ->shouldBePerformedOn($this->getMediapickerCollectionName());
    }

    /**
     * Whether this owner uploaded the media
     */
    public function ownsMedia(Media $media): bool
    {
        return $media->model_type === $this->getMorphClass()
            && (string) $media->model_id === (string) $this->getKey();
    }
}
