<?php

namespace Javaabu\Mediapicker\Tests\TestSupport\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Javaabu\Mediapicker\Concerns\InteractsWithAttachments;
use Javaabu\Mediapicker\Contracts\HasAttachments;
use Javaabu\Mediapicker\Tests\TestSupport\Factories\PostFactory;
use Spatie\MediaLibrary\MediaCollections\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ModelWithMultipleConversions extends Post
{
    public function registerAttachmentConversions(?Media $media = null)
    {
        $this->addAttachmentConversion('test')
            ->width(100)
            ->height(100)
            ->crop(100, 100);

        $this->addAttachmentConversion('test2')
            ->width(50)
            ->height(50)
            ->crop(50, 50);
    }
}
