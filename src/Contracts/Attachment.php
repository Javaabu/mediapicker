<?php

namespace Javaabu\Mediapicker\Contracts;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

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

    public function setHighestOrderNumber(): void;

    public function getHighestOrderNumber(): int;

    public function scopeOrdered(Builder $query): Builder;

    public static function setNewOrder(array $ids, int $startOrder = 1): void;

    public function shouldSortWhenCreating(): bool;

}
