<?php

namespace Javaabu\Mediapicker\Models\Observers;

use Javaabu\Mediapicker\AttachmentCollections\AttachmentFilesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaObserver
{
    public function deleted(Media $media): void
    {
        if (method_exists($media, 'isForceDeleting') && ! $media->isForceDeleting()) {
            return;
        }

        /** @var AttachmentFilesystem $filesystem */
        $filesystem = app(AttachmentFilesystem::class);

        $filesystem->removeAllFiles($media);
    }
}
