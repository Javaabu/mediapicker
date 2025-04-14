<?php

namespace Javaabu\Mediapicker\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Javaabu\Mediapicker\AttachmentCollections\AttachmentRepository;
use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Conversions\AttachmentConversionCollection;
use Javaabu\Mediapicker\Conversions\MediaManipulator;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGeneratorFactory;

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

    protected function deleteFilesGeneratedForDeprecatedConversions(): void
    {
        $this->getAttachmentItems()->each(function (Attachment $attachment) {
            $this->deleteConversionFilesForDeprecatedConversions($attachment);

            if ($this->rateLimit) {
                usleep((1 / $this->rateLimit) * 1_000_000 * 2);
            }
        });
    }

    protected function deleteConversionFilesForDeprecatedConversions(Attachment $attachment): void
    {
        $conversionFilePaths = AttachmentConversionCollection::createForAttachment($attachment)->getConversionsFiles($attachment->collection_name);

        $media = $attachment->media;
        $conversionPath = PathGeneratorFactory::create($media)->getPathForConversions($media);
        $currentFilePaths = $this->fileSystem->disk($media->disk)->files($conversionPath);

        collect($currentFilePaths)
            ->reject(fn (string $currentFilePath) => $conversionFilePaths->contains(basename($currentFilePath)))
            ->reject(fn (string $currentFilePath) => $media->file_name === basename($currentFilePath))
            ->each(function (string $currentFilePath) use ($media) {
                if (! $this->isDryRun) {
                    $this->fileSystem->disk($media->disk)->delete($currentFilePath);

                    $this->markConversionAsRemoved($media, $currentFilePath);
                }

                $this->info("Deprecated conversion file `{$currentFilePath}` ".($this->isDryRun ? 'found' : 'has been removed'));
            });
    }

    protected function markConversionAsRemoved(Media $media, string $conversionPath): void
    {
        $conversionFile = pathinfo($conversionPath, PATHINFO_FILENAME);

        $generatedConversionName = null;

        $media->getGeneratedConversions()
            ->dot()
            ->filter(
                fn (bool $isGenerated, string $generatedConversionName) => Str::contains($conversionFile, $generatedConversionName)
            )
            ->each(
                fn (bool $isGenerated, string $conversionName) => $media->markAsConversionNotGenerated($conversionName)
            );

        $media->save();
    }
}
