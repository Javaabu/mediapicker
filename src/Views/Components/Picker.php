<?php

namespace Javaabu\Mediapicker\Views\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\Component;
use Javaabu\Forms\FormsDataBinder;
use Javaabu\Manifest\Attributes\RequiresAssets;
use Javaabu\Mediapicker\Contracts\HasAttachments;
use Javaabu\Mediapicker\Http\Resources\MediaResource;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\Models\Media;
use Javaabu\Mediapicker\Support\FileTypes;
use Javaabu\Mediapicker\Support\PickerConfig;
use Javaabu\Mediapicker\Support\Strings;

/**
 * A form field that picks media from the library (and uploads to it)
 *
 * ```blade
 * <x-mediapicker::picker name="featured_image" :model="$post" type="image" required />
 * <x-mediapicker::picker name="gallery" :model="$post" type="image" multiple />
 * <x-mediapicker::picker name="attachments" collection="files" type="document" multiple :label="__('Files')" />
 * ```
 *
 * It submits the media ids (`gallery[]` for several). Its value is the old input, else
 * `value`, else the model's attachments in `collection` (default: the name). Save with
 * `$post->syncAttachmentsFromRequest('gallery')` after validating with `LibraryMedia`.
 */
#[RequiresAssets('mediapicker::picker')]
class Picker extends Component
{
    /** @var array<int, string> */
    public array $types;

    public string $collection;

    /** @var array<int, array<string, mixed>> */
    public array $items;

    public string $id;

    public string $input_name;

    public string $dot_name;

    /**
     * @param  string|array<int, string>|null  $type
     * @param  Collection<int, Media>|array<int, Media|int>|Media|int|null  $value
     */
    public function __construct(
        public string $name,
        public string $label = '',
        public ?Model $model = null,
        ?string $collection = null,
        string|array|null $type = null,
        public bool $multiple = false,
        public bool $required = false,
        public bool $disabled = false,
        public string $help = '',
        public bool $showLabel = true,
        public bool $upload = true,
        public ?string $route = null,
        public ?string $url = null,
        mixed $value = null,
    ) {
        $this->types = array_values(array_filter(Arr::wrap($type), fn ($item) => FileTypes::exists((string) $item)));
        $this->dot_name = str_replace(['[]', '[', ']'], ['', '.', ''], $name);
        $this->collection = $collection ?: Str::afterLast($this->dot_name, '.');
        $this->input_name = $multiple ? "{$name}[]" : $name;
        $this->id = str_replace('.', '_', $this->dot_name) . '_picker';
        $this->model ??= $this->boundModel();
        $this->items = $this->resolveItems($value);
    }

    public function labelText(): string
    {
        return $this->label ?: __(Str::headline($this->dot_name));
    }

    /**
     * The config the field's script reads
     *
     * @return array<string, mixed>
     */
    public function config(): array
    {
        $picker = PickerConfig::make($this->route);

        return [
            'name' => $this->input_name,
            'multiple' => $this->multiple,
            'type' => $this->types,
            'url' => $this->url ?? $picker['url'],
            'value' => $this->items,
            'types' => $picker['types'],
            'accept' => implode(',', FileTypes::mimeTypes($this->types ?: null)),
            'upload' => $this->upload,
            'disabled' => $this->disabled,
            'strings' => Strings::all(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function resolveItems(mixed $value): array
    {
        $session = request()->hasSession() ? request()->session() : null;

        if ($session?->hasOldInput($this->dot_name)) {
            return $this->toItems($this->findMedia(Arr::wrap($session->getOldInput($this->dot_name))));
        }

        if ($value !== null) {
            return $this->toItems($this->normalize($value));
        }

        if ($this->model instanceof HasAttachments && $this->model->exists) {
            return $this->toItems(collect($this->model->getAttachmentMedia($this->collection))->filter()->values());
        }

        return [];
    }

    /**
     * @return Collection<int, Media>
     */
    protected function normalize(mixed $value): Collection
    {
        $value = $value instanceof Collection ? $value->all() : Arr::wrap($value);

        if (collect($value)->every(fn ($item) => $item instanceof Media)) {
            return collect($value);
        }

        return $this->findMedia(array_map(fn ($item) => $item instanceof Media ? $item->getKey() : $item, $value));
    }

    /**
     * The media of the ids the user may see, in the given order
     *
     * @param  array<int, mixed>  $ids
     * @return Collection<int, Media>
     */
    protected function findMedia(array $ids): Collection
    {
        $ids = array_values(array_filter($ids, fn ($id) => is_numeric($id)));

        if (! $ids) {
            return collect();
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
            ->values();
    }

    /**
     * @param  Collection<int, Media>  $media
     * @return array<int, array<string, mixed>>
     */
    protected function toItems(Collection $media): array
    {
        $items = $media->map(fn (Media $item) => (new MediaResource($item))->forLibrary($this->route)->resolve(request()))->values()->all();

        return $this->multiple ? $items : array_slice($items, 0, 1);
    }

    protected function boundModel(): ?Model
    {
        if (! class_exists(FormsDataBinder::class)) {
            return null;
        }

        $bound = app(FormsDataBinder::class)->get();

        return $bound instanceof Model ? $bound : null;
    }

    public function render(): View
    {
        return view('mediapicker::components.picker');
    }
}
