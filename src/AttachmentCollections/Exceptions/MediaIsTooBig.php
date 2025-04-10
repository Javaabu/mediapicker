<?php

namespace Javaabu\Mediapicker\AttachmentCollections\Exceptions;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\File;

class MediaIsTooBig extends MediaCannotBeAdded
{
    public static function create(Media $media): self
    {
        $fileSize = $media->human_readable_size;

        $maxFileSize = File::getHumanReadableSize(config('media-library.max_file_size'));

        return new static("Media `{$media->getKey()}` has a size of {$fileSize} which is greater than the maximum allowed {$maxFileSize}");
    }
}
