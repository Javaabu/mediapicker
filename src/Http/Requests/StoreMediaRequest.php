<?php

namespace Javaabu\Mediapicker\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\Support\CropPresets;
use Javaabu\Mediapicker\Support\FileTypes;
use Javaabu\Mediapicker\Support\Translations;

/**
 * An upload to the library: the file, its details, and optional crops (`crops[wide][x]`, …)
 *
 * `type` (one type or a list) limits what may be uploaded, as a picker for images does.
 */
class StoreMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $rules = [
            'type' => ['nullable', is_array($this->input('type')) ? 'array' : 'string'],
            'type.*' => ['string', Rule::in(FileTypes::types())],
            'file' => ['required', ...FileTypes::rules($this->types())],
            ...static::detailRules(),
            ...CropPresets::rules(),
        ];

        if (is_string($this->input('type'))) {
            $rules['type'][] = Rule::in(FileTypes::types());
        }

        return Translations::rules(Mediapicker::mediaModel(), $rules);
    }

    /**
     * The known types the upload is limited to, or null for any
     *
     * @return array<int, string>|null
     */
    public function types(): ?array
    {
        $types = array_values(array_filter(
            Arr::wrap($this->input('type')),
            fn ($type) => is_string($type) && FileTypes::exists($type),
        ));

        return $types ?: null;
    }

    /**
     * The rules for the details people can edit
     *
     * @return array<string, array<int, mixed>>
     */
    public static function detailRules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:64000'],
            'alt' => ['nullable', 'string', 'max:1000'],
            'caption' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
