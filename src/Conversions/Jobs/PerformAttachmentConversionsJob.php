<?php

namespace Javaabu\Mediapicker\Conversions\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Conversions\AttachmentConversionCollection;
use Javaabu\Mediapicker\Conversions\MediaManipulator;

class PerformAttachmentConversionsJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $deleteWhenMissingModels = true;

    public function __construct(
        protected AttachmentConversionCollection $conversions,
        protected Attachment $attachment,
        protected bool $onlyMissing = false,
    ) {}

    public function handle(MediaManipulator $mediaManipulator): bool
    {
        $mediaManipulator->performAttachmentConversions(
            $this->conversions,
            $this->attachment,
            $this->onlyMissing
        );

        return true;
    }
}
