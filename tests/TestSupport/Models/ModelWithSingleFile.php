<?php

namespace Javaabu\Mediapicker\Tests\TestSupport\Models;

class ModelWithSingleFile extends Post
{
    public function registerAttachmentCollections()
    {
        $this->addAttachmentCollection('test')
            ->singleFile();
    }
}
