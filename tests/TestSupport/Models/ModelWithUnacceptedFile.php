<?php

namespace Javaabu\Mediapicker\Tests\TestSupport\Models;

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
