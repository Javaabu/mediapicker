<?php

namespace Javaabu\Mediapicker\Tests\TestSupport\Models;

use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ModelWithIdenticalConversions extends Post
{
    public function registerAttachmentConversions(?Media $media = null)
    {
        $this->addAttachmentConversion('test')
            ->fit(Fit::Crop, 100, 100);
    }
}
