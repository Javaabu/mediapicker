<?php

namespace Javaabu\Mediapicker\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Javaabu\Helpers\Media\AllowedMimeTypes;

class MediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $type = $this->input('type');
        $media = $this->route('media');

        $rules = [
            'file' => AllowedMimeTypes::getValidationRule($type ?? '', true),
            'type' => [
                'nullable',
                'string',
                Rule::in(AllowedMimeTypes::getAllowedTypes()),
            ],
            'name' => [
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'max:64000',
                'string',
            ],
        ];

        if ($media) {
            //
        } else {
            $rules['file'][] = 'required';
        }

        return $rules;
    }
}
