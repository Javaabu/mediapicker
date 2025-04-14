<?php

namespace Javaabu\Mediapicker\Models\Observers;

use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Conversions\MediaManipulator;
use Javaabu\Mediapicker\Mediapicker;

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

    public function created(Attachment $attachment): void
    {
        if (is_null($attachment->model_id)) {
            return;
        }

        $attachmentClass = Mediapicker::attachmentModel();

        $eventDispatcher = $attachmentClass::getEventDispatcher();
        $attachmentClass::unsetEventDispatcher();

        /** @var MediaManipulator $mediaManipulator */
        $mediaManipulator = app(MediaManipulator::class);

        $mediaManipulator->createDerivedAttachmentFiles($attachment, onlyMissing: true);

        $attachmentClass::setEventDispatcher($eventDispatcher);
    }
}
