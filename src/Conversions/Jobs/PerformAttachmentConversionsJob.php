<?php

namespace Javaabu\Mediapicker\Conversions\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Javaabu\Mediapicker\Contracts\Attachment;
use Spatie\MediaLibrary\Conversions\ConversionCollection;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PerformAttachmentConversionsJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public $deleteWhenMissingModels = true;

    public function __construct(
        protected ConversionCollection $conversions,
        protected Attachment $attachment,
        protected bool $onlyMissing = false,
    ) {}

    public function handle(FileManipulator $fileManipulator): bool
    {
        $fileManipulator->performConversions(
            $this->conversions,
            $this->media,
            $this->onlyMissing
        );

        return true;
    }
}
