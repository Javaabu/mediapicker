<?php

namespace Javaabu\Mediapicker\AttachmentCollections;

use Javaabu\Mediapicker\FileRemover\AttachmentFileRemoverFactory;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AttachmentFilesystem extends Filesystem
{


    public function removeAllFiles(Media $media): void
    {
        $fileRemover = AttachmentFileRemoverFactory::create($media);

        $fileRemover->removeAllFiles($media);
    }

}
