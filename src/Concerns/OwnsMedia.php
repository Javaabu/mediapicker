<?php

namespace Javaabu\Mediapicker\Concerns;

use Javaabu\Helpers\Media\AllowedMimeTypes;
use Javaabu\Mediapicker\Mediapicker;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait OwnsMedia
{
    public function getMediapickerCollectionName(): string
    {
        return Mediapicker::collectionName();
    }

    /*
     * Register the media picker collections.
     */
    public function registerMediapickerCollections()
    {
        $this->addMediaCollection($this->getMediapickerCollectionName())
            ->acceptsFile(function (File $file) {
                return AllowedMimeTypes::isAllowedMimeType($file->mimeType);
            });
    }

    /*
     * Register the media picker conversions.
     */
    public function registerMediapickerConversions(?Media $media = null)
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
            ->shouldBePerformedOn($this->getMediapickerCollectionName());
    }

    /*
     * Check if can owns the given media
     */
    public function ownsMedia(Media $media): bool
    {
        return $media->model_type == $this->getMorphClass() && $media->model_id == $this->getKey();
    }

    public function canViewAnyMedia(): bool
    {
        return $this->can('view_media');
    }

    public function canCreateMedia(): bool
    {
        return $this->can('edit_media');
    }

    public function canDeleteAnyMedia(): bool
    {
        return $this->can('delete_media');
    }

    public function canViewOthersMedia(): bool
    {
        return $this->can('view_others_media');
    }

    public function canEditOthersMedia(): bool
    {
        return $this->can('edit_others_media');
    }

    public function canDeleteOthersMedia(): bool
    {
        return $this->can('delete_others_media');
    }

    public function canViewMedia(Media $media): bool
    {
        return $this->canViewAnyMedia() &&
               ($this->canViewOthersMedia() || $this->ownsMedia($media));
    }

    public function canEditMedia(Media $media): bool
    {
        return $this->canCreateMedia() &&
            ($this->canEditOthersMedia() || $this->ownsMedia($media));
    }

    public function canDeleteMedia(Media $media): bool
    {
        return $this->canDeleteAnyMedia() &&
            ($this->canDeleteOthersMedia() || $this->ownsMedia($media));
    }
}
