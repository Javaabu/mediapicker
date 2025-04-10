<?php

namespace Javaabu\Mediapicker\AttachmentCollections\Exceptions;

class UnknownType extends MediaCannotBeAdded
{
    public static function create(): self
    {
        return new static('Only media ids, Media objects can be attached');
    }
}
