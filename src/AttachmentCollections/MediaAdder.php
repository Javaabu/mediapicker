<?php

namespace Javaabu\Mediapicker\AttachmentCollections;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Macroable;
use Javaabu\Mediapicker\AttachmentCollections\Exceptions\MediaIsTooBig;
use Javaabu\Mediapicker\AttachmentCollections\Exceptions\MediaUnacceptableForCollection;
use Javaabu\Mediapicker\AttachmentCollections\Exceptions\UnknownType;
use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Contracts\HasAttachments;
use Javaabu\Mediapicker\Mediapicker;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\MediaCollections\File as PendingFile;

class MediaAdder
{
    use Macroable;

    protected ?HasAttachments $subject = null;

    protected Media $media;

    public ?int $order = null;

    /**
     * @return $this
     */
    public function setSubject(Model $subject): self
    {
        /** @var HasAttachments $subject */
        $this->subject = $subject;

        return $this;
    }

    /**
     * @return $this
     */
    public function setMedia(int|string|Media $media): self
    {
        if (! $media instanceof Media) {
            $media = Media::find($media);
        }

        if (! $media instanceof Media) {
            throw UnknownType::create();
        }

        $this->media = $media;

        return $this;
    }

    /**
     * @return $this
     */
    public function setOrder(?int $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * Add to attachment collection
     */
    public function toAttachmentCollection(string $collectionName = 'default'): Attachment
    {
        if ($this->media->size > config('media-library.max_file_size')) {
            throw MediaIsTooBig::create($this->media);
        }

        $attachmentClass = $this->subject?->getAttachmentModel() ?? Mediapicker::attachmentModel();

        /** @var Attachment $attachment */
        $attachment = new $attachmentClass;

        $attachment->media()->associate($this->media);

        $attachment->collection_name = $collectionName;

        if (! is_null($this->order)) {
            $attachment->order_column = $this->order;
        }

        $this->attachAttachment($attachment);

        return $attachment;
    }

    protected function attachAttachment(Attachment $attachment): void
    {
        if (! $this->subject->exists) {
            $this->subject->prepareToAttachAttachments($attachment, $this);

            $class = $this->subject::class;

            $class::created(function ($model) {
                $model->processUnattachedAttachments(function (Attachment $attachment, self $mediaAdder) use ($model) {
                    $this->processAttachmentItem($model, $attachment, $mediaAdder);
                });
            });

            return;
        }

        $this->processAttachmentItem($this->subject, $attachment, $this);
    }

    protected function processAttachmentItem(HasAttachments $model, Attachment $attachment, self $mediaAdder): void
    {
        $this->guardAgainstDisallowedMediaAdditions($attachment);

        if (! $attachment->getConnectionName()) {
            $attachment->setConnection($model->getConnectionName());
        }

        $model->attachments()->save($attachment);

        if ($collectionSizeLimit = optional($this->getAttachmentCollection($attachment->collection_name))->collectionSizeLimit) {
            /** @var HasAttachments */
            $subject = $this->subject->fresh();
            $collectionMedia = $subject->getAttachments($attachment->collection_name);

            if ($collectionMedia->count() > $collectionSizeLimit) {
                $model->clearAttachmentCollectionExcept($attachment->collection_name, $collectionMedia->slice(-$collectionSizeLimit, $collectionSizeLimit));
            }
        }
    }

    protected function getAttachmentCollection(string $collectionName): ?MediaCollection
    {
        $this->subject->registerAttachmentCollections();

        return collect($this->subject->attachmentCollections)
            ->first(fn (MediaCollection $collection) => $collection->name === $collectionName);
    }

    protected function guardAgainstDisallowedMediaAdditions(Attachment $attachment): void
    {
        $media = $attachment->media;

        $file = PendingFile::createFromMedia($media);

        if (! $collection = $this->getAttachmentCollection($attachment->collection_name)) {
            return;
        }

        if (! ($collection->acceptsFile)($file, $this->subject)) {
            throw MediaUnacceptableForCollection::create($media, $collection, $this->subject);
        }

        if (! empty($collection->acceptsMimeTypes) && ! in_array($file->mimeType, $collection->acceptsMimeTypes)) {
            throw MediaUnacceptableForCollection::create($media, $collection, $this->subject);
        }
    }




}
