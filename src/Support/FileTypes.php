<?php

namespace Javaabu\Mediapicker\Support;

use Illuminate\Support\Arr;

/**
 * The file types the media library accepts (`image`, `video`, `audio`, `document`, `archive`),
 * read from `config('mediapicker.file_types')`
 *
 * ```php
 * FileTypes::typeOf('image/png');          // 'image'
 * FileTypes::mimeTypes(['image', 'video']); // ['image/jpeg', …, 'video/mp4', …]
 * FileTypes::icon('application/pdf');      // 'file-text'
 * ```
 */
class FileTypes
{
    /**
     * @return array<string, array{mime_types: array<int, string>, icon?: string, max_size?: int|null}>
     */
    public static function all(): array
    {
        return config('mediapicker.file_types', []);
    }

    /**
     * @return array<int, string>
     */
    public static function types(): array
    {
        return array_keys(static::all());
    }

    public static function exists(string $type): bool
    {
        return array_key_exists($type, static::all());
    }

    /**
     * The mime types of one or more types, or of every type when none are given
     *
     * @param  string|array<int, string>|null  $types
     * @return array<int, string>
     */
    public static function mimeTypes(string|array|null $types = null): array
    {
        $all = static::all();
        $types = $types ? Arr::wrap($types) : array_keys($all);

        $mime_types = [];

        foreach ($types as $type) {
            $mime_types = [...$mime_types, ...($all[$type]['mime_types'] ?? [])];
        }

        return array_values(array_unique($mime_types));
    }

    public static function isAllowed(?string $mime_type, string|array|null $types = null): bool
    {
        return $mime_type && in_array(strtolower($mime_type), static::mimeTypes($types), true);
    }

    /**
     * The type a mime type belongs to, or null when no type accepts it
     */
    public static function typeOf(?string $mime_type): ?string
    {
        if (! $mime_type) {
            return null;
        }

        foreach (static::all() as $type => $definition) {
            if (in_array(strtolower($mime_type), $definition['mime_types'] ?? [], true)) {
                return $type;
            }
        }

        return null;
    }

    /**
     * A Lucide icon name for the mime type
     */
    public static function icon(?string $mime_type): string
    {
        $type = static::typeOf($mime_type);

        return static::all()[$type]['icon'] ?? 'file';
    }

    /**
     * The largest upload (KB) allowed for one or more types: the type's `max_size`, else
     * `mediapicker.max_upload_size`, else spatie's `media-library.max_file_size`
     *
     * @param  string|array<int, string>|null  $types
     */
    public static function maxSize(string|array|null $types = null): int
    {
        $all = static::all();
        $types = $types ? Arr::wrap($types) : array_keys($all);
        $default = (int) (config('mediapicker.max_upload_size') ?: ((int) config('media-library.max_file_size', 10 * 1024 * 1024) / 1024));

        $max = 0;

        foreach ($types as $type) {
            $max = max($max, (int) ($all[$type]['max_size'] ?? 0) ?: $default);
        }

        return $max ?: $default;
    }

    /**
     * Validation rules for an uploaded file of the given types
     *
     * @param  string|array<int, string>|null  $types
     * @return array<int, string>
     */
    public static function rules(string|array|null $types = null): array
    {
        return [
            'file',
            'mimetypes:' . implode(',', static::mimeTypes($types)),
            'max:' . static::maxSize($types),
        ];
    }
}
