<?php

namespace Javaabu\Mediapicker\Conversions\Actions;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Javaabu\Mediapicker\Models\Attachment;
use Spatie\Image\Exceptions\UnsupportedImageFormat;
use Spatie\Image\Image;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PerformAttachmentManipulationsAction
{
    public function execute(
        Attachment $attachment,
        Conversion $conversion,
        string $imageFile,
    ): string {
        $media = $attachment->media;

        if ($conversion->getManipulations()->isEmpty()) {
            return $imageFile;
        }

        $conversionTempFile = $this->getConversionTempFileName($media, $conversion, $imageFile);

        File::copy($imageFile, $conversionTempFile);

        $supportedFormats = ['jpg', 'jpeg', 'pjpg', 'png', 'gif', 'webp'];
        if ($conversion->shouldKeepOriginalImageFormat() && in_array($media->extension, $supportedFormats)) {
            $conversion->format($media->extension);
        }

        $image = Image::useImageDriver(config('media-library.image_driver'))
            ->loadFile($conversionTempFile)
            ->format('jpg');

        try {
            $conversion->getManipulations()->apply($image);

            $image->save();
        } catch (UnsupportedImageFormat) {

        }

        return $conversionTempFile;
    }

    protected function getConversionTempFileName(
        Media $media,
        Conversion $conversion,
        string $imageFile,
    ): string {
        $directory = pathinfo($imageFile, PATHINFO_DIRNAME);

        $extension = $media->extension;

        if ($extension === '') {
            $extension = 'jpg';
        }

        $fileName = Str::random(32)."{$conversion->getName()}.{$extension}";

        return "{$directory}/{$fileName}";
    }
}
