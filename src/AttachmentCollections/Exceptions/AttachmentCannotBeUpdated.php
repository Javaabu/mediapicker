<?php

namespace Javaabu\Mediapicker\AttachmentCollections\Exceptions;

use Exception;
use Javaabu\Mediapicker\Contracts\Attachment;

class AttachmentCannotBeUpdated extends Exception
{
    public static function doesNotBelongToCollection(string $collectionName, Attachment $attachment): self
    {
        return new static("Attachment id {$attachment->getKey()} is not part of collection `{$collectionName}`");
    }
}
