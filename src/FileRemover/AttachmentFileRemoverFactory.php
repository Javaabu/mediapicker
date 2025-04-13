<?php

namespace Javaabu\Mediapicker\FileRemover;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\FileRemover\FileRemoverFactory;
use Spatie\MediaLibrary\Support\FileRemover\FileRemover;

class AttachmentFileRemoverFactory extends FileRemoverFactory
{
    public static function create(Media $media): FileRemover
    {
        $fileRemoverClass = config('mediapicker.file_remover_class');

        static::guardAgainstInvalidFileRemover($fileRemoverClass);

        return app($fileRemoverClass);
    }
}
