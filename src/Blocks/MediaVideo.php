<?php

namespace Javaabu\Mediapicker\Blocks;

use Javaabu\BlockEditor\Blocks\Attribute;
use Javaabu\BlockEditor\Node;
use Javaabu\BlockEditor\Sanitizing\Sanitizer;
use Javaabu\BlockEditor\Support\Url;
use Javaabu\Mediapicker\Rules\LibraryMedia;

/**
 * An uploaded video from the media library. javaabu/block-editor's `embed` block keeps
 * YouTube and Vimeo videos.
 */
class MediaVideo extends MediaBlock
{
    protected string $name = 'mediaVideo';

    protected string $icon = 'film';

    protected array $keywords = ['video', 'movie', 'clip', 'mp4', 'media'];

    protected string $title = 'Video file';

    protected string $summary = 'A video from the media library';

    public function attributes(): array
    {
        return [
            'mediaId' => Attribute::integer()->hidden(),
            'src' => Attribute::url()->required()->hidden(),
            'mimeType' => Attribute::string()->max(255)->hidden(),
            'caption' => Attribute::string()->max(500)->thaana()->label(__('Caption')),
            'controls' => Attribute::boolean()->default(true)->label(__('Controls')),
            'autoplay' => Attribute::boolean()->default(false)->label(__('Autoplay')),
            'loop' => Attribute::boolean()->default(false)->label(__('Loop')),
            'muted' => Attribute::boolean()->default(false)->label(__('Muted')),
        ];
    }

    public function rules(): array
    {
        return ['mediaId' => [new LibraryMedia('video')]];
    }

    public function sanitize(Node $node, Sanitizer $sanitizer): ?Node
    {
        return Url::isSafe($node->attr('src')) ? $node : null;
    }

    protected function viewData(Node $node, $renderer): array
    {
        $media = static::findMedia([$node->attr('mediaId')])->first();

        return [
            ...parent::viewData($node, $renderer),
            'src' => $media ? $media->getUrl() : $node->attr('src'),
            'type' => $media?->mime_type ?? $node->attr('mimeType'),
        ];
    }
}
