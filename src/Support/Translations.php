<?php

namespace Javaabu\Mediapicker\Support;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Translatable\Translatable;

/**
 * Optional javaabu/translatable support, feature-detected so the package works without it
 */
class Translations
{
    /** The media fields that can be translated */
    public const array FIELDS = ['name', 'description', 'alt', 'caption'];

    public static function installed(): bool
    {
        return class_exists(Translatable::class);
    }

    public static function enabled(): bool
    {
        return static::installed() && Translatable::enabled();
    }

    public static function supports(object|string|null $model, ?string $field = null): bool
    {
        return static::installed() && Translatable::supports($model, $field);
    }

    /**
     * The media's translatable values per locale (`name_en`, `name_dv`, …), or null when the
     * media can't be translated
     *
     * @return array<string, mixed>|null
     */
    public static function forMedia(?Model $media): ?array
    {
        if (! static::supports($media)) {
            return null;
        }

        return Translatable::values($media);
    }

    /**
     * Expand rules for the translatable fields into per-locale rules
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    public static function rules(object|string|null $model, array $rules): array
    {
        if (! static::supports($model)) {
            return $rules;
        }

        return Translatable::rules($model, $rules);
    }
}
