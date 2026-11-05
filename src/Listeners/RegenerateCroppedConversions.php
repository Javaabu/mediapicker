<?php

namespace Javaabu\Mediapicker\Listeners;

use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Conversions\AttachmentConversion;
use Javaabu\Mediapicker\Conversions\MediaManipulator;
use Javaabu\Mediapicker\Events\MediaCropped;
use Javaabu\Mediapicker\Mediapicker;

/**
 * After a crop changes, generates the attachment conversions that use that preset. Their
 * fingerprint includes the crop, so only those are new; queued ones go to the queue.
 */
class RegenerateCroppedConversions
{
    public function __construct(
        protected MediaManipulator $manipulator,
    ) {}

    public function handle(MediaCropped $event): void
    {
        $attachment_class = Mediapicker::attachmentModel();

        $attachment_class::query()
            ->where('media_id', $event->media->getKey())
            ->get()
            ->each(function (Attachment $attachment) use ($event) {
                $attachment->setRelation('media', $event->media);

                $names = $attachment->conversions()
                    ->filter(fn ($conversion) => $conversion instanceof AttachmentConversion && $conversion->getCropPreset() === $event->preset)
                    ->map(fn (AttachmentConversion $conversion) => $conversion->getName())
                    ->values()
                    ->all();

                if ($names) {
                    $this->manipulator->createDerivedAttachmentFiles($attachment, $names, only_missing: true);
                }
            });
    }
}
