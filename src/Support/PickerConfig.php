<?php

namespace Javaabu\Mediapicker\Support;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Javaabu\Mediapicker\Mediapicker;

/**
 * What a picker needs to talk to a library: its URL, the file types, the upload limits,
 * the crop presets and the UI strings
 */
class PickerConfig
{
    /**
     * @return array{
     *     url: string,
     *     types: array<int, array{value: string, label: string, icon: string}>,
     *     accept: string,
     *     max_size: int,
     *     crop_presets: array<int, array{name: string, label: string, aspect: float|null}>,
     *     strings: array<string, string>
     * }
     */
    public static function make(?string $route_name = null): array
    {
        return [
            'url' => static::url($route_name),
            'types' => static::types(),
            'accept' => implode(',', FileTypes::mimeTypes()),
            'max_size' => FileTypes::maxSize(),
            'crop_presets' => CropPresets::forUi(),
            'strings' => Strings::all(),
        ];
    }

    /**
     * The library's index URL, or '' when no library routes are registered
     */
    public static function url(?string $route_name = null): string
    {
        try {
            return Mediapicker::url('index', route_name: $route_name);
        } catch (InvalidArgumentException) {
            return '';
        }
    }

    /**
     * @param  array<int, string>|null  $only
     * @return array<int, array{value: string, label: string, icon: string}>
     */
    public static function types(?array $only = null): array
    {
        $types = [];

        foreach (FileTypes::all() as $type => $definition) {
            if ($only && ! in_array($type, $only, true)) {
                continue;
            }

            $types[] = ['value' => $type, 'label' => __(Str::headline($type)), 'icon' => $definition['icon'] ?? 'file'];
        }

        return $types;
    }
}
