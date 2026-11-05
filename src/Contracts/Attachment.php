<?php

namespace Javaabu\Mediapicker\Contracts;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Javaabu\Mediapicker\Conversions\AttachmentConversion;
use Javaabu\Mediapicker\Conversions\AttachmentConversionCollection;

interface Attachment
{
    /**
     * An attachment belongs to a media item
     */
    public function media(): BelongsTo;

    /**
     * An attachment belongs to a model
     */
    public function model(): MorphTo;

    /*
     * Get all the names of the registered media conversions.
     */
    public function getMediaConversionNames(): array;

    /**
     * Check if has a generated conversion
     */
    public function hasGeneratedConversion(string $conversionName): bool;

    /**
     * Mark conversion as generated
     */
    public function markAsConversionGenerated(string $conversionName, bool $generated): Attachment;

    /**
     * Get the path to the original media file.
     */
    public function getPath(string $conversionName = ''): string;

    /**
     * Get the url to the original media file.
     */
    public function getUrl(string $conversionName = ''): string;

    /**
     * Get temporary url
     */
    public function getTemporaryUrl(DateTimeInterface $expiration, string $conversionName = '', array $options = []): string;

    /**
     * The conversions the attached model declares for this attachment's collection
     */
    public function conversions(): AttachmentConversionCollection;

    /**
     * A conversion by name, or null when the model doesn't declare it
     */
    public function conversion(string $conversion_name): ?AttachmentConversion;

    /**
     * What was generated for a conversion: width, height, formats and responsive widths
     *
     * @return array{width?: ?int, height?: ?int, formats?: array<int, string>, widths?: array<int, int>}|null
     */
    public function conversionMetadata(string $conversion_name): ?array;

    /**
     * The URL of the first generated conversion, else of the original file
     *
     * @param  string|array<int, string>  $conversion_names
     */
    public function getAvailableUrl(string|array $conversion_names = []): string;

    /**
     * The URL of a conversion's copy in another format, once generated
     */
    public function getFormatUrl(string $conversion_name, string $format): ?string;

    /**
     * A srcset for a conversion, in its own format or another generated one
     */
    public function getSrcset(string $conversion_name, ?string $format = null): string;

    public function setHighestOrderNumber(): void;

    public function getHighestOrderNumber(): int;

    public function scopeOrdered(Builder $query): Builder;

    public static function setNewOrder(array $ids, int $startOrder = 1): void;

    public function shouldSortWhenCreating(): bool;
}
