<?php

namespace Javaabu\Mediapicker\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\LazyCollection;
use Illuminate\Support\Str;
use Javaabu\Mediapicker\AttachmentCollections\AttachmentRepository;
use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Conversions\MediaManipulator;

class AttachmentsRegenerateCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'mediapicker:regenerate {modelType?} {--ids=*}
    {--only=* : Regenerate specific conversions}
    {--starting-from-id= : Regenerate attachment with an id equal to or higher than the provided value}
    {--X|exclude-starting-id : Exclude the provided id when regenerating from a specific id}
    {--only-missing : Regenerate only missing conversions}
    {--force : Force the operation to run when in production}
    {--queue-all : Queue all conversions, even non-queued ones}';

    protected $description = 'Regenerate the derived images of media';

    protected AttachmentRepository $attachmentRepository;

    protected MediaManipulator $mediaManipulator;

    protected array $errorMessages = [];

    public function handle(AttachmentRepository $attachmentRepository, MediaManipulator $mediaManipulator): void
    {
        $this->attachmentRepository = $attachmentRepository;

        $this->mediaManipulator = $mediaManipulator;

        if (! $this->confirmToProceed()) {
            return;
        }

        $attachments = $this->getAttachmentsToBeRegenerated();

        $progressBar = $this->output->createProgressBar($attachments->count());

        if (config('media-library.queue_connection_name') === 'sync') {
            set_time_limit(0);
        }

        $attachments->each(function (Attachment $attachment) use ($progressBar) {
            try {
                $this->mediaManipulator->createDerivedAttachmentFiles(
                    $attachment,
                    Arr::wrap($this->option('only')),
                    $this->option('only-missing'),
                    false,//$this->option('with-responsive-images'),
                    $this->option('queue-all'),
                );
            } catch (Exception $exception) {
                $this->errorMessages[$attachment->getKey()] = $exception->getMessage();
            }

            $progressBar->advance();
        });

        $progressBar->finish();

        if (count($this->errorMessages)) {
            $this->warn('All done, but with some error messages:');

            foreach ($this->errorMessages as $attachmentId => $message) {
                $this->warn("Attachment id {$attachmentId}: `{$message}`");
            }
        }

        $this->newLine(2);

        $this->info('All done!');
    }

    public function getAttachmentsToBeRegenerated(): LazyCollection
    {
        // Get this arg first as it can also be passed to the greater-than-id branch
        $modelType = $this->argument('modelType');

        $startingFromId = (int) $this->option('starting-from-id');
        if ($startingFromId !== 0) {
            $excludeStartingId = (bool) $this->option('exclude-starting-id') ?: false;

            return $this->attachmentRepository->getByIdGreaterThan($startingFromId, $excludeStartingId, is_string($modelType) ? $modelType : '');
        }

        if (is_string($modelType)) {
            return $this->attachmentRepository->getByModelType($modelType);
        }

        $attachmentIds = $this->getAttachmentIds();
        if (count($attachmentIds) > 0) {
            return $this->attachmentRepository->getByIds($attachmentIds);
        }

        return $this->attachmentRepository->all();
    }

    protected function getAttachmentIds(): array
    {
        $attachmentIds = $this->option('ids');

        if (! is_array($attachmentIds)) {
            $attachmentIds = explode(',', (string) $attachmentIds);
        }

        if (count($attachmentIds) === 1 && Str::contains((string) $attachmentIds[0], ',')) {
            $attachmentIds = explode(',', (string) $attachmentIds[0]);
        }

        return $attachmentIds;
    }
}
