<?php

namespace Javaabu\Mediapicker\Concerns;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Javaabu\Mediapicker\AttachmentCollections\AttachmentRepository;
use Javaabu\Mediapicker\AttachmentCollections\Events\AttachmentCollectionHasBeenClearedEvent;
use Javaabu\Mediapicker\AttachmentCollections\Exceptions\AttachmentCannotBeDeleted;
use Javaabu\Mediapicker\AttachmentCollections\Exceptions\AttachmentCannotBeUpdated;
use Javaabu\Mediapicker\AttachmentCollections\MediaAdder;
use Javaabu\Mediapicker\AttachmentCollections\MediaAdderFactory;
use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Contracts\HasAttachments;
use Javaabu\Mediapicker\Exceptions\MediaCannotBeAdded\MediaMimeTypeNotAllowed;
use Javaabu\Mediapicker\Mediapicker;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait InteractsWithAttachments
{
    /** @var array */
    public $attachmentConversions = [];

    /** @var array */
    public $attachmentCollections = [];

    /** @var array */
    protected $unAttachedAttachmentItems = [];


    public static function bootInteractsWithAttachments(): void
    {
        static::deleting(function (HasAttachments $model) {
            if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                if (! $model->forceDeleting) {
                    return;
                }
            }

            $model->attachments()->cursor()->each(fn (Attachment $attachment) => $attachment->delete());
        });
    }

    /**
     * Get the media model
     *
     * @return class-string<Attachment>
     */
    public function getAttachmentModel(): string
    {
        return Mediapicker::attachmentModel();
    }

    /**
     * Set the polymorphic relation.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Mediapicker::attachmentModel(), 'model');
    }

    /**
     * Attach a media to the model.
     */
    public function addAttachment(string|Media $media)
    {
        return app(MediaAdderFactory::class)->create($this, $media);
    }

    /**
     * Add a media from a request.
     */
    public function addAttachmentFromRequest(string $key): MediaAdder
    {
        return app(MediaAdderFactory::class)->createFromRequest($this, $key);
    }

    /**
     * Add multiple medias from a request by keys.
     *
     * @param array<string> $keys
     * @return array<MediaAdder>
     */
    public function addMultipleAttachmentsFromRequest(array $keys): array
    {
        return app(MediaAdderFactory::class)->createMultipleFromRequest($this, $keys);
    }

    /**
     * Determine if there is media in the given attachment collection.
     */
    public function hasAttachments(string $collectionName = 'default'): bool
    {
        return $this->getAttachments($collectionName)->isNotEmpty();
    }


    /**
     * Get attachment collection by its collectionName.
     *
     * @return Collection<Attachment>
     */
    public function getAttachments(string $collectionName = 'default', array|callable $filters = []): Collection
    {
        return app(AttachmentRepository::class)->getCollection($this, $collectionName, $filters);
    }

    /**
     * Get the attachment media
     *
     * @return null|Collection<Media>
     */
    public function getAttachmentMedia(string $collectionName = 'default', array|callable $filters = []): ?Collection
    {
        $attachments = $this->getAttachments($collectionName, $filters);

        return $attachments->pluck('media');
    }

    /**
     * Get the first attachment
     */
    public function getFirstAttachment(string $collectionName = 'default', array $filters = []): ?Attachment
    {
        $attachments = $this->getAttachments($collectionName, $filters);

        return $attachments->first();
    }

    /**
     * Get the first attachment media
     */
    public function getFirstAttachmentMedia(string $collectionName = 'default', array $filters = []): ?Media
    {
        $attachment = $this->getFirstAttachment($collectionName, $filters);

        return $attachment ? $attachment->media : null;
    }

    /*
     * Get the url of the attachment for the given conversionName
     * for first media for the given collectionName.
     * If no conversion is given, return the source's url.
     */
    public function getFirstAttachmentUrl(string $collectionName = 'default', string $conversionName = ''): string
    {
        $attachment = $this->getFirstAttachment($collectionName);

        if (! $attachment) {
            return '';
        }

        return $attachment->getUrl($conversionName);
    }


    /*
     * Get the url of the image for the given conversionName
     * for first media for the given collectionName.
     * If no conversion is given, return the source's url.
     */
    public function getFirstAttachmentTemporaryUrl(DateTimeInterface $expiration, string $collectionName = 'default', string $conversionName = ''): string
    {
        $attachment = $this->getFirstAttachment($collectionName);

        if (! $attachment) {
            return '';
        }

        return $attachment->getTemporaryUrl($expiration, $conversionName);
    }

    /*
     * Get the url of the attachment for the given conversionName
     * for first media for the given collectionName.
     * If no profile is given, return the source's url.
     */
    public function getFirstAttachmentPath(string $collectionName = 'default', string $conversionName = ''): string
    {
        $attachment = $this->getFirstAttachment($collectionName);

        if (! $attachment) {
            return '';
        }

        return $attachment->getPath($conversionName);
    }

    /**
     * Update an attachment collection by deleting and inserting again with new values.
     *
     * @param array $newAttachmentsArray
     * @param string $collectionName
     *
     * @return Collection<Attachment>
     *
     * @throws \Javaabu\Mediapicker\AttachmentCollections\Exceptions\AttachmentCannotBeUpdated
     */
    public function updateAttachments(array $newAttachmentsArray, string $collectionName = 'default'): Collection
    {
        $this->removeAttachmentItemsNotPresentInArray($newAttachmentsArray, $collectionName);

        $attachmentClass = $this->getAttachmentModel();

        return collect($newAttachmentsArray)
            ->map(function (array $newAttachmentItem) use ($collectionName, $attachmentClass) {
                static $orderColumn = 1;

                $currentAttachment = $attachmentClass::findOrFail($newAttachmentItem['id']);

                if ($currentAttachment->collection_name !== $collectionName) {
                    throw AttachmentCannotBeUpdated::doesNotBelongToCollection($collectionName, $currentAttachment);
                }

                $currentAttachment->order_column = $orderColumn++;

                $currentAttachment->save();

                return $currentAttachment;
            });
    }

    /**
     * Remove attachments not in the array
     */
    protected function removeAttachmentItemsNotPresentInArray(array $newAttachmentsArray, string $collectionName = 'default')
    {
        $this->getAttachments($collectionName)
             ->reject(function (Attachment $currentAttachmentItem) use ($newAttachmentsArray) {
                 return in_array($currentAttachmentItem->id, array_column($newAttachmentsArray, 'id'));
             })
            ->each->delete();
    }

    /**
     * Remove all attachments in the given collection.
     */
    public function clearAttachmentCollection(string $collectionName = 'default'): self
    {
        $this->getAttachments($collectionName)
            ->each->delete();

        event(new AttachmentCollectionHasBeenClearedEvent($this, $collectionName));

        if ($this->attachmentsIsPreloaded()) {
            unset($this->attachments);
        }

        return $this;
    }

    /**
     * Update an attachment collection by deleting and inserting media again with new values.
     *
     * @param array $newMediaArray
     * @param string $collectionName
     *
     * @return Collection
     *
     * @throws AttachmentCannotBeUpdated
     */
    public function updateAttachmentMedia(array $newMediaArray, string $collectionName = 'default'): Collection
    {
        $this->removeAttachmentMediaNotPresentInArray($newMediaArray, $collectionName);

        // filter out already attached media
        $existing_media = $this->getAttachments($collectionName)
                               ->pluck('media_id')
                               ->all();

        $newMediaArray = array_diff($newMediaArray, $existing_media);

        // only include distince values
        $newMediaArray = array_unique($newMediaArray);

        return collect($newMediaArray)
            ->map(function ($newMediaId) use ($collectionName) {
                static $orderColumn = 1;

                $currentAttachment = $this->getFirstAttachment($collectionName, ['media_id' => $newMediaId]);

                if (! $currentAttachment) {
                    $currentAttachment = $this->addAttachment($newMediaId)
                                              ->toAttachmentCollection($collectionName);
                }

                $currentAttachment->order_column = $orderColumn++;
                $currentAttachment->save();

                return $currentAttachment;
            });
    }

    /**
     * Remove attachment media not in the array
     *
     * @param array $newMediaArray
     * @param string $collectionName
     */
    protected function removeAttachmentMediaNotPresentInArray(array $newMediaArray, string $collectionName = 'default')
    {
        $this->getAttachments($collectionName)
             ->reject(function (Attachment $currentAttachmentItem) use ($newMediaArray) {
                 return in_array($currentAttachmentItem->media_id, $newMediaArray);
             })
            ->each(fn (Attachment $attachment) => $attachment->delete());
    }



    /**
     * Remove all attachments in the given collection except some.
     *
     * @param string $collectionName
     * @param array<Attachment>|Collection<Attachment> $excludedAttachments
     */
    public function clearAttachmentCollectionExcept(string $collectionName = 'default', array|Collection|Attachment $excludedAttachments = []): self
    {
        if ($excludedAttachments instanceof Attachment) {
            $excludedAttachments = collect()->push($excludedAttachments);
        }

        $excludedAttachments = collect($excludedAttachments);

        if ($excludedAttachments->isEmpty()) {
            return $this->clearAttachmentCollection($collectionName);
        }

        $this->getAttachments($collectionName)
             ->reject(function (Attachment $attachment) use ($excludedAttachments) {
                 return $excludedAttachments->where('id', $attachment->id)->count();
             })
            ->each(fn (Attachment $attachment) => $attachment->delete());

        if ($this->attachmentsIsPreloaded()) {
            unset($this->attachment);
        }

        if ($this->getAttachments($collectionName)->isEmpty()) {
            event(new AttachmentCollectionHasBeenClearedEvent($this, $collectionName));
        }

        return $this;
    }


    /**
     * Check if attachments were preloaded
     */
    protected function attachmentsIsPreloaded(): bool
    {
        return $this->relationLoaded('attachments');
    }

    /**
     * Delete the associated attachment with the given id.
     * You may also pass a attachment object.
     *
     * @throws AttachmentCannotBeDeleted
     */
    public function deleteAttachment(int|string|Attachment $attachmentId)
    {
        if ($attachmentId instanceof Attachment) {
            $attachmentId = $attachmentId->getKey();
        }

        $attachment = $this->attachments->find($attachmentId);

        if (! $attachment) {
            throw AttachmentCannotBeDeleted::doesNotBelongToModel($attachmentId, $this);
        }

        $attachment->delete();
    }


    /**
     * Add attachment collection
     *
     * @param string $name
     * @return MediaCollection
     */
    public function addAttachmentCollection(string $name): MediaCollection
    {
        $mediaCollection = MediaCollection::create($name);

        $this->attachmentCollections[] = $mediaCollection;

        return $mediaCollection;
    }

    /**
     * Cache the attachments on the object.
     *
     * @return Collection<Attachment>
     */
    public function loadAttachments(string $collectionName): Collection
    {
        $collection = $this->exists
            ? $this->attachments()->with('media')->get()
            : collect($this->unAttachedAttachmentItems)->pluck('attachments');

        return $collection
            ->filter(function (Attachment $attachmentItem) use ($collectionName) {
                if ($collectionName == '') {
                    return true;
                }

                return $attachmentItem->collection_name === $collectionName;
            })
            ->sortBy('order_column')
            ->values();
    }

    /**
     * Prepare to attach
     *
     * @param Attachment $attachment
     * @param MediaAdder $mediaAdder
     */
    public function prepareToAttachAttachments(Attachment $attachment, MediaAdder $mediaAdder)
    {
        $this->unAttachedAttachmentItems[] = compact('attachment', 'mediaAdder');
    }

    /**
     * Process unattached attachments
     */
    public function processUnattachedAttachments(callable $callable)
    {
        foreach ($this->unAttachedAttachmentItems as $item) {
            $callable($item['attachment'], $item['mediaAdder']);
        }

        $this->unAttachedAttachmentItems = [];
    }

    /*
     * Add a conversion.
     */
    public function addAttachmentConversion(string $name): Conversion
    {
        $conversion = Conversion::create($name);

        $this->attachmentConversions[] = $conversion;

        return $conversion;
    }

    /**
     * Register attachment media conversions
     */
    public function registerAttachmentConversions(?Media $media = null)
    {
    }

    /**
     * Register attachment media collections
     */
    public function registerAttachmentCollections()
    {
    }

    /**
     * Register all attachment conversions
     */
    public function registerAllAttachmentConversions(?Media $media = null)
    {
        $this->registerAttachmentCollections();

        collect($this->attachmentCollections)->each(function (MediaCollection $mediaCollection) use ($media) {
            $actualAttachmentConversions = $this->attachmentConversions;

            $this->attachmentConversions = [];

            ($mediaCollection->mediaConversionRegistrations)($media);

            $preparedAttachmentConversions = collect($this->attachmentConversions)
                ->each(function (Conversion $conversion) use ($mediaCollection) {
                    $conversion->performOnCollections($mediaCollection->name);
                })
                ->values()
                ->toArray();

            $this->attachmentConversions = array_merge($actualAttachmentConversions, $preparedAttachmentConversions);
        });

        $this->registerAttachmentConversions($media);
    }

    /**
     * Updates the attachment collection with given media from request
     *
     * @param $collection
     * @param Request $request
     * @param string $key the attachment field in the request
     * @return mixed
     */
    public function updateSingleAttachment($collection, Request $request, string $key = '')
    {
        if (! $key) {
            $key = $collection;
        }

        $media_id = $request->input($key);
        $clear = $request->input('clear_file');
        $response = false;

        if ($request->input($key) || $clear) {
            if ($media_id) {
                // check if it's the same attachment
                $current_media = $this->getFirstAttachmentMedia($collection);

                // attach only if different
                if (empty($current_media) || $current_media->id != $media_id) {
                    $response = $this->addAttachment($media_id)
                                     ->toAttachmentCollection($collection);
                }
            } else {
                // clear
                $this->clearAttachmentCollection($collection);
                $clear = true;
                $response = 0;
            }
        }

        return $response;
    }

    /**
     * With attachments scope
     */
    public function scopeWithAttachments(Builder $query)
    {
        $query->with('attachments.media');
    }

    /**
     * Validate media mimetype
     *
     * @param Media $media
     * @param array ...$allowedMimeTypes
     * @throws MediaMimeTypeNotAllowed
     */
    protected function guardAgainstInvalidMediaMimeType(Media $media, ...$allowedMimeTypes)
    {
        $allowedMimeTypes = Arr::flatten($allowedMimeTypes);

        if (empty($allowedMimeTypes)) {
            return;
        }

        $validation = Validator::make(
            ['mimetype' => $media->mime_type],
            ['mimetype' => 'string|in:' . implode(',', $allowedMimeTypes)]
        );

        if ($validation->fails()) {
            throw MediaMimeTypeNotAllowed::create($media, $allowedMimeTypes);
        }
    }
}
