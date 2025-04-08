<?php

namespace Javaabu\Mediapicker\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Javaabu\Mediapicker\AttachmentCollections\Exceptions\AttachmentCannotBeDeleted;
use Javaabu\Mediapicker\AttachmentCollections\Exceptions\AttachmentCannotBeUpdated;
use Javaabu\Mediapicker\AttachmentCollections\MediaAdder;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

interface HasAttachments
{
    /**
     * Get the media model
     *
     * @return class-string<Attachment>
     */
    public function getAttachmentModel(): string;

    /**
     * Set the polymorphic relation.
     */
    public function attachments(): MorphMany;

    /**
     * Attach a media to the model.
     */
    public function addAttachment(string|Media $media);

    /**
     * Add a media from a request.
     */
    public function addAttachmentFromRequest(string $key): MediaAdder;

    /**
     * Add multiple medias from a request by keys.
     *
     * @param array<string> $keys
     * @return array<MediaAdder>
     */
    public function addMultipleAttachmentsFromRequest(array $keys): array;

    /**
     * Determine if there is media in the given attachment collection.
     */
    public function hasAttachments(string $collectionName = 'default'): bool;

    /**
     * Get attachment collection by its collectionName.
     *
     * @return Collection<Attachment>
     */
    public function getAttachments(string $collectionName = 'default', array|callable $filters = []): Collection;

    /**
     * Get the attachment media
     *
     * @return null|Collection<Media>
     */
    public function getAttachmentMedia(string $collectionName = 'default', array|callable $filters = []): ?Collection;

    /**
     * Get the first attachment
     */
    public function getFirstAttachment(string $collectionName = 'default', array $filters = []): ?Attachment;

    /**
     * Get the first attachment media
     */
    public function getFirstAttachmentMedia(string $collectionName = 'default', array $filters = []): ?Media;

    /*
     * Get the url of the attachment for the given conversionName
     * for first media for the given collectionName.
     * If no conversion is given, return the source's url.
     */
    public function getFirstAttachmentUrl(string $collectionName = 'default', string $conversionName = ''): string;

    /*
     * Get the url of the image for the given conversionName
     * for first media for the given collectionName.
     * If no conversion is given, return the source's url.
     */
    public function getFirstAttachmentTemporaryUrl(DateTimeInterface $expiration, string $collectionName = 'default', string $conversionName = ''): string;

    /*
     * Get the url of the attachment for the given conversionName
     * for first media for the given collectionName.
     * If no conversion is given, return the source's url.
     */
    public function getFirstAttachmentPath(string $collectionName = 'default', string $conversionName = ''): string;

    /**
     * Update an attachment collection by deleting and inserting again with new values.
     *
     * @param array $newAttachmentsArray
     * @param string $collectionName
     *
     * @return Collection<Attachment>
     *
     * @throws AttachmentCannotBeUpdated
     */
    public function updateAttachments(array $newAttachmentsArray, string $collectionName = 'default'): Collection;

    /**
     * Remove all attachments in the given collection.
     */
    public function clearAttachmentCollection(string $collectionName = 'default'): self;

    /**
     * Remove all attachments in the given collection except some.
     *
     * @param string $collectionName
     * @param array<Attachment>|Collection<Attachment> $excludedAttachments
     */
    public function clearAttachmentCollectionExcept(string $collectionName = 'default', array|Collection|Attachment $excludedAttachments = []): self;

    /**
     * Delete the associated attachment with the given id.
     * You may also pass a attachment object.
     *
     * @throws AttachmentCannotBeDeleted
     */
    public function deleteAttachment(int|string|Attachment $attachmentId);

    /**
     * Cache the attachments on the object.
     *
     * @param string $collectionName
     *
     * @return Collection<Attachment>
     */
    public function loadAttachments(string $collectionName): Collection;

    /**
     * Prepare to attach
     *
     * @param Attachment $attachment
     * @param MediaAdder $mediaAdder
     */
    public function prepareToAttachAttachments(Attachment $attachment, MediaAdder $mediaAdder);

    /**
     * Process unattached attachments
     *
     * @param callable $callable
     */
    public function processUnattachedAttachments(callable $callable);

    /*
     * Add a conversion.
     */
    public function addAttachmentConversion(string $name): Conversion;

    /*
     * Register the attachment conversions.
     */
    public function registerAttachmentConversions(?Media $media = null);

    /*
     * Register the attachment collections.
     */
    public function registerAttachmentCollections();

    /*
     * Register the attachment conversions and conversions set in attachment collections.
     */
    public function registerAllAttachmentConversions(?Media $media = null);

    /**
     * Updates the attachment collection with given media from request
     *
     * @param $collection
     * @param Request $request
     * @param string $key the attachment field in the request
     * @return mixed
     */
    public function updateSingleAttachment($collection, Request $request, string $key = '');

    /**
     * With attachments scope
     *
     * @param $query
     * @return mixed
     */
    public function scopeWithAttachments(Builder $query);
}
