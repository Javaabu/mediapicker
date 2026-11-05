<?php

namespace Javaabu\Mediapicker\UrlGenerator;

use Javaabu\Mediapicker\Contracts\Attachment;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;
use Spatie\MediaLibrary\Support\UrlGenerator\UrlGenerator;
use Spatie\MediaLibrary\Support\UrlGenerator\UrlGeneratorFactory;

/**
 * spatie's URL generator, for an attachment's media and one of its attachment conversions
 */
class AttachmentUrlGeneratorFactory extends UrlGeneratorFactory
{
    public static function createForAttachment(Attachment $attachment, string $conversionName = ''): UrlGenerator
    {
        $conversion = $conversionName !== ''
            ? ($attachment->conversion($conversionName) ?? $attachment->conversions()->getByName($conversionName))
            : null;

        return static::createForConversion($attachment, $conversion);
    }

    public static function createForConversion(Attachment $attachment, ?Conversion $conversion = null): UrlGenerator
    {
        $media = $attachment->media;

        $url_generator_class = config('media-library.url_generator');

        static::guardAgainstInvalidUrlGenerator($url_generator_class);

        /** @var UrlGenerator $url_generator */
        $url_generator = app($url_generator_class);

        $url_generator
            ->setMedia($media)
            ->setPathGenerator(PathGeneratorFactory::create($media));

        if ($conversion) {
            $url_generator->setConversion($conversion);
        }

        return $url_generator;
    }
}
