<?php

namespace Javaabu\Mediapicker\AttachmentCollections\Exceptions;

use Javaabu\Mediapicker\Contracts\HasAttachments;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaUnacceptableForCollection extends MediaCannotBeAdded
{
    public static function create(Media $media, MediaCollection $mediaCollection, HasAttachments $hasAttachments): self
    {
        $modelType = $hasAttachments::class;

        return new static("The media with properties `{$media}` was not accepted into the collection named `{$mediaCollection->name}` of model `{$modelType}` with id `{$hasAttachments->getKey()}`");
    }
}
