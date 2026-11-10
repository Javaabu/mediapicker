<?php

namespace Javaabu\Mediapicker\Blocks;

use Illuminate\Support\Collection;
use Javaabu\BlockEditor\Blocks\Block;
use Javaabu\Mediapicker\Assets\Assets;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\Models\Media;
use Javaabu\Mediapicker\Support\PickerConfig;

/**
 * A javaabu/block-editor block that picks from the media library
 *
 * The node keeps the media id and a snapshot of what the editor and React need (URL, size,
 * alt). Blade rendering looks the media up again, so it shows current URLs and alt text,
 * and falls back to the snapshot when the media is gone.
 */
abstract class MediaBlock extends Block
{
    protected string $category = 'media';

    protected ?string $script = Assets::BLOCKS;

    /** The English title, translated with the package's JSON strings */
    protected string $title = '';

    protected string $summary = '';

    /** The route name prefix of the library to pick from; null: the signed-in user's */
    public static ?string $library = null;

    public function title(): string
    {
        return __($this->title);
    }

    public function description(): string
    {
        return __($this->summary);
    }

    public function view(): string
    {
        return $this->view ?? 'mediapicker::blocks.' . str($this->name)->kebab();
    }

    /**
     * The block's config for the editor, with what the picker needs
     *
     * @return array<string, mixed>
     */
    public function toEditor(): array
    {
        return [...parent::toEditor(), 'mediapicker' => PickerConfig::make(static::$library)];
    }

    /**
     * The media of these ids, keyed by id
     *
     * @param  array<int, mixed>  $ids
     * @return Collection<string, Media>
     */
    public static function findMedia(array $ids): Collection
    {
        $ids = array_values(array_filter($ids, fn ($id) => is_numeric($id)));

        if (! $ids) {
            return collect();
        }

        $media_class = Mediapicker::mediaModel();

        return $media_class::query()
            ->whereKey($ids)
            ->get()
            ->keyBy(fn (Media $media) => (string) $media->getKey());
    }

    /**
     * The URL to show an image at: the library preview when it's made, else the original
     */
    public static function imageUrl(Media $media): string
    {
        return $media->hasGeneratedConversion('mediapicker-large')
            ? $media->getUrl('mediapicker-large')
            : $media->getUrl();
    }
}
