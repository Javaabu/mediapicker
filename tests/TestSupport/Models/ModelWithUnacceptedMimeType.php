<?php

namespace Javaabu\Mediapicker\Tests\TestSupport\Models;

class ModelWithUnacceptedMimeType extends Post
{
    public function registerAttachmentCollections()
    {
        $this->addAttachmentCollection('test')
            ->acceptsMimeTypes(['text/plain']);
    }
}
