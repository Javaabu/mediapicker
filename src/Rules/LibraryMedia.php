<?php

namespace Javaabu\Mediapicker\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\Support\FileTypes;

/**
 * Media ids a user may attach: in the library they can see, and of the allowed types.
 * Use it for every picker field, or anyone could attach media they can't see.
 *
 * ```php
 * 'featured_image' => ['nullable', new LibraryMedia(type: 'image')],
 * 'gallery' => ['nullable', 'array', 'max:20'],
 * 'gallery.*' => [new LibraryMedia(type: 'image')],
 * ```
 */
class LibraryMedia implements ValidationRule
{
    /** @var array<int, string> */
    protected array $types;

    /**
     * @param  string|array<int, string>|null  $type
     */
    public function __construct(
        string|array|null $type = null,
        protected ?Model $user = null,
    ) {
        $this->types = array_values(array_filter(Arr::wrap($type)));
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $ids = array_filter(Arr::wrap($value), fn ($id) => $id !== null && $id !== '');

        if (! $ids) {
            return;
        }

        foreach ($ids as $id) {
            if (! is_numeric($id)) {
                $fail(__('The selected :attribute is invalid.'));

                return;
            }
        }

        $media_class = Mediapicker::mediaModel();

        $query = $media_class::query()
            ->visibleTo($this->user ?? auth()->user())
            ->whereKey(array_unique($ids));

        if ($this->types) {
            $query->whereIn('mime_type', FileTypes::mimeTypes($this->types));
        }

        if ($query->count() !== count(array_unique($ids))) {
            $fail(__('The selected :attribute is invalid.'));
        }
    }
}
