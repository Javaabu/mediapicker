<?php

namespace Javaabu\Mediapicker\Tests\TestSupport\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ModelWithSameConversions extends Post
{
    public function registerAttachmentConversions(?Media $media = null)
    {
        $this->addAttachmentConversion('test')
            ->width(50)
            ->height(50)
            ->crop(50, 50);
    }
}
