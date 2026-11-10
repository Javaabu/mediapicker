<?php

namespace Javaabu\Mediapicker\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\Support\Translations;

/**
 * Changes to a library media's details
 */
class UpdateMediaRequest extends FormRequest
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
        $rules = StoreMediaRequest::detailRules();

        $rules['name'] = ['sometimes', 'required', 'string', 'max:255'];

        return Translations::rules(Mediapicker::mediaModel(), $rules);
    }
}
