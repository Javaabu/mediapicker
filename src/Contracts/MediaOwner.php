<?php

namespace Javaabu\Mediapicker\Contracts;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface MediaOwner extends HasMedia, Authorizable
{
    /*
     * Register the media picker collections.
     */
    public function registerMediapickerCollections();

    /*
     * Register the media picker conversions.
     */
    public function registerMediapickerConversions(?Media $media = null);

    public function getMediapickerCollectionName(): string;

    /*
     * Check if can owns the given media
     */
    public function ownsMedia(Media $media): bool;

    public function canViewAnyMedia(): bool;

    public function canCreateMedia(): bool;

    public function canViewOthersMedia(): bool;

    public function canEditOthersMedia(): bool;

    public function canDeleteOthersMedia(): bool;

    public function canViewMedia(Media $media): bool;

    public function canEditMedia(Media $media): bool;

    public function canDeleteMedia(Media $media): bool;
}
