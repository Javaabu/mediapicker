<?php

namespace Javaabu\Mediapicker\Tests\TestSupport\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Javaabu\Mediapicker\Concerns\InteractsWithAttachments;
use Javaabu\Mediapicker\Contracts\HasAttachments;
use Javaabu\Mediapicker\Tests\TestSupport\Factories\PostFactory;
use Spatie\MediaLibrary\MediaCollections\File;

class ModelWithUnacceptedFile extends Post
{
    public function registerAttachmentCollections()
    {
        $this->addAttachmentCollection('test')
            ->acceptsFile(function (File $file) {
                return $file->name != 'test.jpg';
            });
    }
}
