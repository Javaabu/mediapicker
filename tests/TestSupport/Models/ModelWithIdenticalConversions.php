<?php

namespace Javaabu\Mediapicker\Tests\TestSupport\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ModelWithIdenticalConversions extends Post
{
    public function registerAttachmentConversions(?Media $media = null)
    {
        $this->addAttachmentConversion('test')
            ->width(100)
            ->height(100)
            ->crop(100, 100);
    }
}
