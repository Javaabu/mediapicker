<?php

namespace Javaabu\Mediapicker\Stencil;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Javaabu\Mediapicker\Contracts\HasAttachments;
use Javaabu\Mediapicker\Http\Resources\MediaResource;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\Models\Media;
use Javaabu\Mediapicker\Rules\LibraryMedia;
use Javaabu\Mediapicker\Support\CropPresets;
use Javaabu\Mediapicker\Support\FileTypes;
use Javaabu\Mediapicker\Support\PickerConfig;
use Javaabu\Mediapicker\Support\Strings;
use Javaabu\Stencil\Contracts\SavesRelationships;
use Javaabu\Stencil\Fields\Field;

/**
 * A stencil field that picks library media and attaches it to the record
 *
 * ```php
 * MediaPickerField::make('featured_image')->mediaType('image')->required(),
 * MediaPickerField::make('gallery')->collection('gallery')->mediaType('image')->multiple(),
 * ```
 *
 * The value is the media ids; they're validated with `LibraryMedia` and saved after the
 * record with `updateAttachmentMedia()` on the record's attachment collection. React apps
 * register the `mediapicker` type: `StencilProvider components={{ mediapicker: StencilMediaPicker }}`.
 */
class MediaPickerField extends Field implements SavesRelationships
{
    protected string $type = 'mediapicker';

    protected ?string $view = 'mediapicker::stencil.picker';

    protected string|Closure|null $collection = null;

    /** @var string|array<int, string>|Closure|null */
    protected string|array|Closure|null $media_type = null;

    protected bool $is_multiple = false;

    protected string|Closure|null $crop = null;

    protected string|Closure|null $route = null;

    protected bool|Closure $upload = true;

    /**
     * The attachment collection of the record; default: the field name
     */
    public function collection(string|Closure|null $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    /**
     * Only these file types: `image`, `video`, `audio`, `document`, `archive`
     *
     * @param  string|array<int, string>|Closure|null  $type
     */
    public function mediaType(string|array|Closure|null $type): static
    {
        $this->media_type = $type;

        return $this;
    }

    public function multiple(bool $condition = true): static
    {
        $this->is_multiple = $condition;

        return $this;
    }

    /**
     * Crop new images to this preset before they're uploaded (React)
     */
    public function crop(string|Closure|null $preset): static
    {
        $this->crop = $preset;

        return $this;
    }

    /**
     * The route name prefix of the library to pick from (`admin.media`); default: the one
     * of the signed-in user's guard
     */
    public function library(string|Closure|null $route_name): static
    {
        $this->route = $route_name;

        return $this;
    }

    public function upload(bool|Closure $condition = true): static
    {
        $this->upload = $condition;

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->is_multiple;
    }

    public function getCollection(): string
    {
        return $this->evaluate($this->collection) ?: $this->getName();
    }

    /**
     * @return array<int, string>
     */
    public function getMediaTypes(): array
    {
        return array_values(array_filter(Arr::wrap($this->evaluate($this->media_type)), fn ($type) => FileTypes::exists((string) $type)));
    }

    public function getInputName(): string
    {
        return parent::getInputName() . ($this->is_multiple ? '[]' : '');
    }

    public function savesToAttribute(): bool
    {
        return false;
    }

    protected function readFromRecord(mixed $record): mixed
    {
        if (! $record instanceof HasAttachments || ! $record instanceof Model || ! $record->exists) {
            return null;
        }

        $ids = $record->getAttachments($this->getCollection())->pluck('media_id')->all();

        return $this->is_multiple ? $ids : ($ids[0] ?? null);
    }

    public function normalizeState(mixed $state): mixed
    {
        $ids = array_values(array_filter(Arr::wrap($state), fn ($id) => is_numeric($id)));
        $ids = array_map('intval', $ids);

        return $this->is_multiple ? $ids : ($ids[0] ?? null);
    }

    protected function castDehydratedState(mixed $state): mixed
    {
        return $this->normalizeState($state);
    }

    public function saveRelationship(Model $record, mixed $state): void
    {
        if (! $record instanceof HasAttachments) {
            return;
        }

        $record->updateAttachmentMedia(array_filter(Arr::wrap($state)), $this->getCollection());
    }

    protected function getTypeRules(): array
    {
        if ($this->is_multiple) {
            return ['array'];
        }

        return [new LibraryMedia($this->getMediaTypes())];
    }

    public function getAllValidationRules(): array
    {
        $rules = parent::getAllValidationRules();

        if ($this->is_multiple) {
            $rules["{$this->getStatePath()}.*"] = [new LibraryMedia($this->getMediaTypes())];
        }

        return $rules;
    }

    protected function getFieldProps(): array
    {
        $types = $this->getMediaTypes();
        $route = $this->evaluate($this->route);

        return [
            'multiple' => $this->is_multiple,
            'type' => $types,
            'url' => PickerConfig::url($route),
            'route' => $route,
            'crop' => $this->evaluate($this->crop),
            'upload' => (bool) $this->evaluate($this->upload),
            'value' => $this->items($route),
            'types' => PickerConfig::types(),
            'accept' => implode(',', FileTypes::mimeTypes($types ?: null)),
            'maxSize' => FileTypes::maxSize($types ?: null),
            'cropPresets' => CropPresets::forUi(),
            'strings' => Strings::all(),
        ];
    }

    /**
     * The chosen media, as the pickers show them
     *
     * @return array<int, array<string, mixed>>
     */
    protected function items(?string $route): array
    {
        $ids = array_values(array_filter(Arr::wrap($this->getState()), fn ($id) => is_numeric($id)));

        if (! $ids) {
            return [];
        }

        $media_class = Mediapicker::mediaModel();

        $media = $media_class::query()
            ->visibleTo(auth()->user())
            ->whereKey($ids)
            ->get()
            ->keyBy(fn (Media $item) => (string) $item->getKey());

        return collect($ids)
            ->map(fn ($id) => $media->get((string) $id))
            ->filter()
            ->map(fn (Media $item) => (new MediaResource($item))->forLibrary($route)->resolve(request()))
            ->values()
            ->all();
    }
}
