<?php

namespace Javaabu\Mediapicker\AttachmentCollections\Events;

use Illuminate\Queue\SerializesModels;
use Javaabu\Mediapicker\Contracts\HasAttachments;

class AttachmentCollectionHasBeenClearedEvent
{
    use SerializesModels;

    public function __construct(public HasAttachments $model, public string $collectionName) {}
}
