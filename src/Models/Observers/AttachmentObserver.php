<?php

namespace Javaabu\Mediapicker\Models\Observers;

use Javaabu\Mediapicker\Contracts\Attachment;

class AttachmentObserver
{
    public function creating(Attachment $attachment): void
    {
        if ($attachment->shouldSortWhenCreating()) {
            if (is_null($attachment->order_column)) {
                $attachment->setHighestOrderNumber();
            }
        }
    }

    // TODO
}
