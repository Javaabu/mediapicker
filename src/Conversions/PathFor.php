<?php

namespace Javaabu\Mediapicker\Conversions;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

/**
 * Where attachment conversion files live on the media's conversions disk
 */
class PathFor
{
    /**
     * The folder of a media's attachment conversions, relative to the disk root
     */
    public static function directory(Media $media): string
    {
        return PathGeneratorFactory::create($media)->getPathForConversions($media) . AttachmentConversion::DIRECTORY . '/';
    }

    /**
     * A conversion's file, relative to the disk root
     */
    public static function conversion(Media $media, AttachmentConversion $conversion): string
    {
        return PathGeneratorFactory::create($media)->getPathForConversions($media) . $conversion->getConversionFile($media);
    }
}
