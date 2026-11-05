<?php

namespace Javaabu\Mediapicker\Conversions;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Conversions\Actions\PerformAttachmentConversionAction;
use Javaabu\Mediapicker\Conversions\Jobs\PerformAttachmentConversionsJob;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\Conversions\FileManipulator;
use Spatie\MediaLibrary\Conversions\ImageGenerators\ImageGeneratorFactory;
use Spatie\MediaLibrary\MediaCollections\Filesystem;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\TemporaryDirectory;

/**
 * Generates the conversions an attachment needs: only those its model declares for its
 * collection, only the ones not generated yet (`only_missing`), queued unless a conversion
 * is `nonQueued()`
 */
class MediaManipulator extends FileManipulator
{
    /**
     * @param  array<int, string>  $only_conversion_names
     */
    public function createDerivedAttachmentFiles(
        Attachment $attachment,
        array $only_conversion_names = [],
        bool $only_missing = false,
        bool $with_responsive_images = false,
        bool $queue_all = false,
    ): void {
        $media = $attachment->media;

        if (! $media || ! $this->canConvertMedia($media)) {
            return;
        }

        [$queued, $now] = $this->conversionsFor($attachment, $only_conversion_names)
            ->partition(fn (Conversion $conversion) => $queue_all || $conversion->shouldBeQueued());

        $this->performAttachmentConversions($now, $attachment, $only_missing)
            ->dispatchQueuedAttachmentConversions($attachment, $queued, $only_missing);
    }

    /**
     * The attachment's conversions for its collection
     *
     * @param  array<int, string>  $only_conversion_names
     */
    public function conversionsFor(Attachment $attachment, array $only_conversion_names = []): AttachmentConversionCollection
    {
        return AttachmentConversionCollection::createForAttachment($attachment)
            ->filter(fn (Conversion $conversion) => ! $only_conversion_names || in_array($conversion->getName(), $only_conversion_names, true))
            ->filter(fn (Conversion $conversion) => $conversion->shouldBePerformedOn($attachment->collection_name))
            ->values();
    }

    public function performAttachmentConversions(
        AttachmentConversionCollection $conversions,
        Attachment $attachment,
        bool $only_missing = false,
    ): self {
        $media = $attachment->media;

        if ($only_missing) {
            $conversions = $conversions->reject(fn (AttachmentConversion $conversion) => $this->isGenerated($media, $conversion));
        }

        if ($conversions->isEmpty()) {
            return $this;
        }

        $temporary_directory = TemporaryDirectory::create();

        $copied_original_file = app(Filesystem::class)->copyFromMediaLibrary(
            $media,
            $temporary_directory->path(Str::random(32) . '.' . $media->extension),
        );

        $conversions->each(fn (AttachmentConversion $conversion) => (new PerformAttachmentConversionAction)->execute($conversion, $attachment, $copied_original_file));

        $temporary_directory->delete();

        return $this;
    }

    /**
     * Whether the conversion's files for the media exist: recorded and on the disk
     */
    public function isGenerated(Media $media, AttachmentConversion $conversion): bool
    {
        if (! ConversionMetadata::has($media, $conversion->key($media))) {
            return false;
        }

        $path = PathFor::conversion($media, $conversion);

        return Storage::disk($media->conversions_disk ?: $media->disk)->exists($path);
    }

    protected function dispatchQueuedAttachmentConversions(
        Attachment $attachment,
        AttachmentConversionCollection $conversions,
        bool $only_missing = false,
    ): self {
        if ($conversions->isEmpty()) {
            return $this;
        }

        $job_class = config('mediapicker.jobs.perform_conversions', PerformAttachmentConversionsJob::class);

        /** @var PerformAttachmentConversionsJob $job */
        $job = (new $job_class($conversions, $attachment, $only_missing))
            ->onConnection(config('media-library.queue_connection_name'))
            ->onQueue(config('media-library.queue_name'));

        config('media-library.queue_conversions_after_database_commit')
            ? dispatch($job)->afterCommit()
            : dispatch($job);

        return $this;
    }

    protected function canConvertMedia(Media $media): bool
    {
        return (bool) ImageGeneratorFactory::forMedia($media);
    }
}
