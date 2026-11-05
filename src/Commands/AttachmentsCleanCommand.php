<?php

namespace Javaabu\Mediapicker\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\LazyCollection;
use Javaabu\Mediapicker\AttachmentCollections\AttachmentRepository;
use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Conversions\MediaManipulator;
use Javaabu\Mediapicker\Conversions\StaleConversions;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AttachmentsCleanCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'mediapicker:clean {modelType?} {collectionName?} {disk?}
    {--dry-run : List files that will be removed without removing them},
    {--force : Force the operation to run when in production},
    {--rate-limit= : Limit the number of requests per second},
    {--delete-orphaned : Delete orphaned attachment items},
    {--skip-conversions : Do not remove deprecated conversions}';

    protected $description = 'Clean deprecated conversions and files without related model.';

    protected AttachmentRepository $attachmentRepository;

    protected MediaManipulator $mediaManipulator;

    protected Factory $fileSystem;

    protected bool $isDryRun = false;

    protected int $rateLimit = 0;

    public function handle(
        AttachmentRepository $attachmentRepository,
        MediaManipulator $mediaManipulator,
        Factory $fileSystem,
    ): void {
        $this->attachmentRepository = $attachmentRepository;
        $this->mediaManipulator = $mediaManipulator;
        $this->fileSystem = $fileSystem;

        if (! $this->confirmToProceed()) {
            return;
        }

        $this->isDryRun = $this->option('dry-run');
        $this->rateLimit = (int) $this->option('rate-limit');

        if ($this->option('delete-orphaned')) {
            $this->deleteOrphanedAttachmentItems();
        }

        if (! $this->option('skip-conversions')) {
            $this->deleteFilesGeneratedForDeprecatedConversions();
        }

        $this->info('All done!');
    }

    /** @return LazyCollection<int, Media> */
    public function getAttachmentItems(): LazyCollection
    {
        $modelType = $this->argument('modelType');
        $collectionName = $this->argument('collectionName');

        if (is_string($modelType) && is_string($collectionName)) {
            return $this->attachmentRepository->getByModelTypeAndCollectionName(
                $modelType,
                $collectionName
            );
        }

        if (is_string($modelType)) {
            return $this->attachmentRepository->getByModelType($modelType);
        }

        if (is_string($collectionName)) {
            return $this->attachmentRepository->getByCollectionName($collectionName);
        }

        return $this->attachmentRepository->all();
    }

    protected function deleteOrphanedAttachmentItems(): void
    {
        $this->getOrphanedAttachmentItems()->each(function (Attachment $attachment): void {
            if ($this->isDryRun) {
                $this->info("Orphaned Attachment[id={$attachment->id}] found");

                return;
            }

            $attachment->delete();

            if ($this->rateLimit) {
                usleep((1 / $this->rateLimit) * 1_000_000);
            }

            $this->info("Orphaned Attachment[id={$attachment->id}] has been removed");
        });
    }

    /** @return LazyCollection<int, Media> */
    protected function getOrphanedAttachmentItems(): LazyCollection
    {
        $collectionName = $this->argument('collectionName');

        if (is_string($collectionName)) {
            return $this->attachmentRepository->getOrphansByCollectionName($collectionName);
        }

        return $this->attachmentRepository->getOrphans();
    }

    /**
     * Remove the attachment conversion files of the selected attachments' media that no
     * attachment of that media uses any more
     */
    protected function deleteFilesGeneratedForDeprecatedConversions(): void
    {
        $stale_conversions = app(StaleConversions::class);
        $seen = [];

        $this->getAttachmentItems()->each(function (Attachment $attachment) use ($stale_conversions, &$seen) {
            $media = $attachment->media;

            if (! $media || isset($seen[$media->getKey()])) {
                return;
            }

            $seen[$media->getKey()] = true;

            $files = $this->isDryRun ? $stale_conversions->find($media) : $stale_conversions->clean($media);

            foreach ($files as $file) {
                $this->info("Deprecated conversion file `{$file}` " . ($this->isDryRun ? 'found' : 'has been removed'));
            }

            if ($this->rateLimit) {
                usleep((1 / $this->rateLimit) * 1_000_000 * 2);
            }
        });
    }
}
