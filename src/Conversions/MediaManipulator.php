<?php

namespace Javaabu\Mediapicker\Conversions;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Conversions\Jobs\PerformAttachmentConversionsJob;
use Spatie\MediaLibrary\Conversions\Actions\PerformConversionAction;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\Conversions\ImageGenerators\ImageGeneratorFactory;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\ResponsiveImages\Jobs\GenerateResponsiveImagesJob;
use Spatie\MediaLibrary\Support\TemporaryDirectory;

class MediaManipulator extends FileManipulator
{
    public function createDerivedAttachmentFiles(
        Attachment $attachment,
        array $onlyConversionNames = [],
        bool $onlyMissing = false,
        bool $withResponsiveImages = false,
        bool $queueAll = false,
    ): void {
        $media = $attachment->media;

        if (! $this->canConvertMedia($media)) {
            return;
        }

        [$queuedConversions, $conversions] = AttachmentConversionCollection::createForAttachment($attachment)
            ->filter(function (Conversion $conversion) use ($onlyConversionNames) {
                if (count($onlyConversionNames) === 0) {
                    return true;
                }

                return in_array($conversion->getName(), $onlyConversionNames);
            })
            ->filter(fn (Conversion $conversion) => $conversion->shouldBePerformedOn($attachment->collection_name))
            ->partition(fn (Conversion $conversion) => $queueAll || $conversion->shouldBeQueued());

        $this
            ->performAttachmentConversions($conversions, $attachment, $onlyMissing)
            ->dispatchQueuedAttachmentConversions($attachment, $queuedConversions, $onlyMissing);
    }

    public function performAttachmentConversions(
        AttachmentConversionCollection $conversions,
        Attachment $attachment,
        bool $onlyMissing = false
    ): self {
        $media = $attachment->media;

        $conversions = $conversions
            ->when(
                $onlyMissing,
                fn (AttachmentConversionCollection $conversions) => $conversions->reject(function (Conversion $conversion) use ($attachment, $media) {
                    $relativePath = $attachment->getPath($conversion->getName());

                    if ($rootPath = config("filesystems.disks.{$media->disk}.root")) {
                        $relativePath = str_replace($rootPath, '', $relativePath);
                    }

                    return Storage::disk($media->disk)->exists($relativePath);
                })
            );

        if ($conversions->isEmpty()) {
            return $this;
        }

        $temporaryDirectory = TemporaryDirectory::create();

        $copiedOriginalFile = app(Filesystem::class)->copyFromMediaLibrary(
            $media,
            $temporaryDirectory->path(Str::random(32).'.'.$media->extension)
        );

        $conversions->each(fn (Conversion $conversion) => (new PerformConversionAction)->execute($conversion, $media, $copiedOriginalFile));

        $temporaryDirectory->delete();

        return $this;
    }

    /**
     * @return $this
     */
    protected function dispatchQueuedAttachmentConversions(
        Attachment $attachment,
        AttachmentConversionCollection $conversions,
        bool $onlyMissing = false
    ): self {
        if ($conversions->isEmpty()) {
            return $this;
        }

        $performConversionsJobClass = config(
            'mediapicker.jobs.perform_conversions',
            PerformAttachmentConversionsJob::class
        );

        /** @var PerformAttachmentConversionsJob $job */
        $job = (new $performConversionsJobClass($conversions, $attachment, $onlyMissing))
            ->onConnection(config('media-library.queue_connection_name'))
            ->onQueue(config('media-library.queue_name'));

        config('media-library.queue_conversions_after_database_commit')
            ? dispatch($job)->afterCommit()
            : dispatch($job);

        return $this;
    }

    protected function canConvertMedia(Media $media): bool
    {
        $imageGenerator = ImageGeneratorFactory::forMedia($media);

        return $imageGenerator ? true : false;
    }
}
