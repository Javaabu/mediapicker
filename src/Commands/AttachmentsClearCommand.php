<?php

namespace Javaabu\Mediapicker\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\LazyCollection;
use Javaabu\Mediapicker\AttachmentCollections\AttachmentRepository;
use Javaabu\Mediapicker\Contracts\Attachment;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AttachmentsClearCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'mediapicker:clear {modelType?} {collectionName?}
    {-- force : Force the operation to run when in production}';

    protected $description = 'Delete all items in an attachment collection.';

    protected AttachmentRepository $attachmentRepository;

    public function handle(AttachmentRepository $attachmentRepository): void
    {
        $this->attachmentRepository = $attachmentRepository;

        if (! $this->confirmToProceed()) {
            return;
        }

        $attachmentItems = $this->getAttachmentItems();

        $progressBar = $this->output->createProgressBar($attachmentItems->count());

        $attachmentItems->each(function (Attachment $attachment) use ($progressBar) {
            $attachment->delete();
            $progressBar->advance();
        });

        $progressBar->finish();

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
}
