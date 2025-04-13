<?php

namespace Javaabu\Mediapicker\Conversions\Events;

use Illuminate\Queue\SerializesModels;
use Javaabu\Mediapicker\Contracts\Attachment;
use Spatie\MediaLibrary\Conversions\Conversion;

class AttachmentConversionHasBeenCompletedEvent
{
    use SerializesModels;

    public function __construct(public Attachment $attachment, public Conversion $conversion) {}
}
