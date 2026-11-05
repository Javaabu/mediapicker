<?php

namespace Javaabu\Mediapicker\Tests\TestSupport\Models;

use Javaabu\Mediapicker\Conversions\AttachmentConversion;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Attachment conversions with crops, formats, responsive widths, queueing and collections
 */
class Article extends Post
{
    public function registerAttachmentCollections(): void
    {
        $this->addAttachmentCollection('featured')->singleFile();
        $this->addAttachmentCollection('gallery');
        $this->addAttachmentCollection('hero');
    }

    public function registerAttachmentConversions(?Media $media = null): void
    {
        $this->addAttachmentConversion('card')
            ->usingCrop('wide')
            ->fit(Fit::Crop, 160, 90)
            ->formats(['webp'])
            ->withResponsiveImages()
            ->nonQueued()
            ->performOnCollections('featured');

        $this->addAttachmentConversion('large')
            ->width(200)
            ->queued()
            ->performOnCollections('featured');

        $this->addAttachmentConversion('hero')
            ->width(340)
            ->keepOriginalImageFormat()
            ->withResponsiveImages()
            ->nonQueued()
            ->performOnCollections('hero');

        $this->addAttachmentConversion('gallery-thumb')
            ->width(50)
            ->nonQueued()
            ->performOnCollections('gallery');
    }

    public function cardConversion(): AttachmentConversion
    {
        return $this->getFirstAttachment('featured')->conversion('card');
    }
}
