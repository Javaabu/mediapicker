<?php

namespace Javaabu\Mediapicker\Blocks;

use Javaabu\BlockEditor\Blocks\Attribute;
use Javaabu\BlockEditor\Node;
use Javaabu\BlockEditor\Sanitizing\Sanitizer;
use Javaabu\BlockEditor\Support\Url;
use Javaabu\Mediapicker\Rules\LibraryMedia;

/**
 * An image from the media library. Replaces javaabu/block-editor's URL image block (same
 * name, `image`), and still renders images that only have a `src`.
 */
class Image extends MediaBlock
{
    protected string $name = 'image';

    protected string $icon = 'image';

    protected array $keywords = ['photo', 'picture', 'img', 'figure', 'media'];

    protected string $title = 'Image';

    protected string $summary = 'An image from the media library';

    public function attributes(): array
    {
        return [
            'mediaId' => Attribute::integer()->hidden(),
            'src' => Attribute::url()->required()->hidden(),
            'width' => Attribute::integer()->hidden(),
            'height' => Attribute::integer()->hidden(),
            'alt' => Attribute::string()->max(255)->thaana()->label(__('Alt text'))->help(__('Describes the image for people who can\'t see it.')),
            'caption' => Attribute::string()->max(500)->thaana()->label(__('Caption')),
            'link' => Attribute::url()->label(__('Link')),
        ];
    }

    public function rules(): array
    {
        return ['mediaId' => [new LibraryMedia('image')]];
    }

    public function sanitize(Node $node, Sanitizer $sanitizer): ?Node
    {
        return Url::isSafeImage($node->attr('src')) ? $node : null;
    }

    protected function viewData(Node $node, $renderer): array
    {
        $media = static::findMedia([$node->attr('mediaId')])->first();

        return [
            ...parent::viewData($node, $renderer),
            'media' => $media,
            'src' => $media ? static::imageUrl($media) : $node->attr('src'),
            'alt' => $node->attr('alt') ?? $media?->alt ?? '',
            'width' => $media?->width ?? $node->attr('width'),
            'height' => $media?->height ?? $node->attr('height'),
        ];
    }
}
