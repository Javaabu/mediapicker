<?php

namespace Javaabu\Mediapicker\Conversions;

use Imagick;

/**
 * Whether the configured image driver can write a format (AVIF needs a GD or ImageMagick
 * built with it)
 */
class FormatSupport
{
    /** @var array<string, bool> */
    protected static array $cache = [];

    public static function supports(string $format): bool
    {
        $format = strtolower($format === 'jpeg' ? 'jpg' : $format);
        $driver = strtolower((string) config('media-library.image_driver', 'gd'));

        return static::$cache["{$driver}:{$format}"] ??= $driver === 'imagick'
            ? static::imagickSupports($format)
            : static::gdSupports($format);
    }

    protected static function gdSupports(string $format): bool
    {
        if (! function_exists('gd_info')) {
            return false;
        }

        return match ($format) {
            'jpg' => function_exists('imagejpeg'),
            'png' => function_exists('imagepng'),
            'gif' => function_exists('imagegif'),
            'webp' => function_exists('imagewebp') && (gd_info()['WebP Support'] ?? false),
            'avif' => function_exists('imageavif') && (gd_info()['AVIF Support'] ?? false),
            default => false,
        };
    }

    protected static function imagickSupports(string $format): bool
    {
        if (! class_exists(Imagick::class)) {
            return false;
        }

        return Imagick::queryFormats(strtoupper($format === 'jpg' ? 'jpeg' : $format)) !== [];
    }
}
