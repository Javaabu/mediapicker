<?php

namespace Javaabu\Mediapicker\UrlGenerator;

use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Conversions\AttachmentConversionCollection;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;
use Spatie\MediaLibrary\Support\UrlGenerator\UrlGenerator;
use Spatie\MediaLibrary\Support\UrlGenerator\UrlGeneratorFactory;

class AttachmentUrlGeneratorFactory extends UrlGeneratorFactory
{
    public static function createForAttachment(Attachment $attachment, string $conversionName = ''): UrlGenerator
    {
        $media = $attachment->media;

        $urlGeneratorClass = config('media-library.url_generator');

        static::guardAgainstInvalidUrlGenerator($urlGeneratorClass);

        /** @var UrlGenerator $urlGenerator */
        $urlGenerator = app($urlGeneratorClass);

        $pathGenerator = PathGeneratorFactory::create($media);

        $urlGenerator
            ->setMedia($media)
            ->setPathGenerator($pathGenerator);

        if ($conversionName !== '') {
            $conversion = AttachmentConversionCollection::createForAttachment($attachment)->getByName($conversionName);

            $urlGenerator->setConversion($conversion);
        }

        return $urlGenerator;
    }
}
