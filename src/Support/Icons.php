<?php

namespace Javaabu\Mediapicker\Support;

/**
 * The Lucide icons the views use (resources/icons/icons.php)
 */
class Icons
{
    /** @var array<string, string>|null */
    protected static ?array $icons = null;

    /**
     * The inner markup of an icon's SVG; the file icon for unknown names
     */
    public static function svg(string $name): string
    {
        static::$icons ??= require __DIR__ . '/../../resources/icons/icons.php';

        return static::$icons[$name] ?? static::$icons['file'];
    }

    public static function has(string $name): bool
    {
        static::$icons ??= require __DIR__ . '/../../resources/icons/icons.php';

        return array_key_exists($name, static::$icons);
    }
}
