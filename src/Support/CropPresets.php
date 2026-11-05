<?php

namespace Javaabu\Mediapicker\Support;

/**
 * The aspect-ratio presets images can be cropped to, from `config('mediapicker.crop_presets')`
 *
 * A crop is kept per preset on the media (`custom_properties.crops.{preset}`), in source
 * pixels: `['x' => 0, 'y' => 40, 'width' => 1600, 'height' => 900, 'rotation' => 0]`.
 */
class CropPresets
{
    /**
     * @return array<string, array{label: string, aspect: float|null}>
     */
    public static function all(): array
    {
        $presets = [];

        foreach (config('mediapicker.crop_presets', []) as $name => $preset) {
            $presets[$name] = [
                'label' => (string) ($preset['label'] ?? str($name)->headline()),
                'aspect' => isset($preset['aspect']) ? (float) $preset['aspect'] : null,
            ];
        }

        return $presets;
    }

    /**
     * @return array<int, string>
     */
    public static function names(): array
    {
        return array_keys(static::all());
    }

    public static function exists(string $name): bool
    {
        return array_key_exists($name, static::all());
    }

    public static function aspect(string $name): ?float
    {
        return static::all()[$name]['aspect'] ?? null;
    }

    /**
     * The presets with translated labels, for the cropper UIs
     *
     * @return array<int, array{name: string, label: string, aspect: float|null}>
     */
    public static function forUi(): array
    {
        $presets = [];

        foreach (static::all() as $name => $preset) {
            $presets[] = ['name' => $name, 'label' => __($preset['label']), 'aspect' => $preset['aspect']];
        }

        return $presets;
    }

    /**
     * Rules for crops sent with an upload: `crops[{preset}][x|y|width|height|rotation]`
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(string $prefix = 'crops'): array
    {
        $rules = [$prefix => ['nullable', 'array:' . implode(',', static::names())]];

        foreach (static::names() as $name) {
            $rules = [...$rules, ...static::cropRules("{$prefix}.{$name}", false)];
        }

        return $rules;
    }

    /**
     * Rules for one crop
     *
     * @return array<string, array<int, mixed>>
     */
    public static function cropRules(string $prefix = '', bool $required = true): array
    {
        $key = fn (string $field) => $prefix === '' ? $field : "{$prefix}.{$field}";
        $presence = $required ? 'required' : "required_with:{$prefix}";

        return [
            $key('x') => [$presence, 'numeric', 'min:0'],
            $key('y') => [$presence, 'numeric', 'min:0'],
            $key('width') => [$presence, 'numeric', 'min:1'],
            $key('height') => [$presence, 'numeric', 'min:1'],
            $key('rotation') => ['nullable', 'numeric', 'min:-360', 'max:360'],
        ];
    }

    /**
     * A crop as it's stored: whole pixels and a rotation
     *
     * @param  array<string, mixed>  $crop
     * @return array{x: int, y: int, width: int, height: int, rotation: int}
     */
    public static function normalize(array $crop): array
    {
        return [
            'x' => (int) round((float) ($crop['x'] ?? 0)),
            'y' => (int) round((float) ($crop['y'] ?? 0)),
            'width' => max(1, (int) round((float) ($crop['width'] ?? 1))),
            'height' => max(1, (int) round((float) ($crop['height'] ?? 1))),
            'rotation' => (int) round((float) ($crop['rotation'] ?? 0)) % 360,
        ];
    }
}
