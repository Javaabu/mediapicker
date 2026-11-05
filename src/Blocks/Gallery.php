<?php

namespace Javaabu\Mediapicker\Blocks;

use Javaabu\BlockEditor\Blocks\Attribute;
use Javaabu\BlockEditor\Node;
use Javaabu\BlockEditor\Sanitizing\Sanitizer;
use Javaabu\BlockEditor\Support\Url;
use Javaabu\Mediapicker\Http\Resources\MediaResource;
use Javaabu\Mediapicker\Rules\LibraryMedia;

/**
 * Several images from the media library in a grid. `items` holds each image's
 * `{id, src, thumb, alt, width, height}`.
 */
class Gallery extends MediaBlock
{
    protected string $name = 'gallery';

    protected string $icon = 'images';

    protected array $keywords = ['photos', 'images', 'grid', 'album', 'media'];

    protected string $title = 'Gallery';

    protected string $summary = 'Images from the media library in a grid';

    public function attributes(): array
    {
        return [
            'items' => Attribute::json()->required()->hidden(),
            'columns' => Attribute::integer()->default(3)->min(1)->max(6)->label(__('Columns')),
            'caption' => Attribute::string()->max(500)->thaana()->label(__('Caption')),
        ];
    }

    public function rules(): array
    {
        return [
            'items' => ['array', 'min:1', 'max:100'],
            'items.*.id' => ['nullable', new LibraryMedia('image')],
            'items.*.src' => ['required', 'string', 'max:2048'],
        ];
    }

    public function sanitize(Node $node, Sanitizer $sanitizer): ?Node
    {
        $items = array_values(array_filter(
            is_array($node->attr('items')) ? $node->attr('items') : [],
            fn ($item) => is_array($item) && Url::isSafeImage($item['src'] ?? null),
        ));

        if (! $items) {
            return null;
        }

        return $node->withAttrs([...$node->attrs, 'items' => $items]);
    }

    protected function viewData(Node $node, $renderer): array
    {
        $items = is_array($node->attr('items')) ? $node->attr('items') : [];
        $media = static::findMedia(array_column($items, 'id'));

        $images = array_map(function (array $item) use ($media) {
            $current = isset($item['id']) ? $media->get((string) $item['id']) : null;

            return [
                'src' => $current ? $current->getUrl() : ($item['src'] ?? ''),
                'thumb' => $current ? (MediaResource::conversionUrl($current, 'mediapicker-large') ?? $current->getUrl()) : ($item['thumb'] ?? $item['src'] ?? ''),
                'alt' => $current?->alt ?? ($item['alt'] ?? ''),
                'width' => $current?->width ?? ($item['width'] ?? null),
                'height' => $current?->height ?? ($item['height'] ?? null),
            ];
        }, $items);

        return [
            ...parent::viewData($node, $renderer),
            'images' => $images,
            'columns' => max(1, min(6, (int) ($node->attr('columns') ?? 3))),
        ];
    }
}
