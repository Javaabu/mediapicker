<?php

namespace Javaabu\Mediapicker\Contracts;

use Illuminate\Contracts\Auth\Access\Authorizable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * A user model with a media library: any guard's user can be an owner
 *
 * Use the `OwnsMedia` trait to implement it. What an owner may do in the library is decided
 * by the media policy (`view_media`, `edit_media`, … through javaabu/permissions).
 */
interface MediaOwner extends Authorizable, HasMedia
{
    /**
     * Register the library collection
     */
    public function registerMediapickerCollections(): void;

    /**
     * Register the library thumbnail and preview conversions
     */
    public function registerMediapickerConversions(?Media $media = null): void;

    /**
     * The media collection that holds this owner's library uploads
     */
    public function getMediapickerCollectionName(): string;

    /**
     * Whether this owner uploaded the media
     */
    public function ownsMedia(Media $media): bool;
}
