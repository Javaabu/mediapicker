<?php

namespace Javaabu\Mediapicker\Blocks;

use Javaabu\BlockEditor\Blocks\Attribute;
use Javaabu\BlockEditor\Node;
use Javaabu\BlockEditor\Sanitizing\Sanitizer;
use Javaabu\BlockEditor\Support\Url;
use Javaabu\Mediapicker\Rules\LibraryMedia;

/**
 * A download link to a file from the media library, with its name and size
 */
class File extends MediaBlock
{
    protected string $name = 'file';

    protected string $icon = 'file-text';

    protected array $keywords = ['download', 'document', 'pdf', 'attachment', 'media'];

    protected string $title = 'File';

    protected string $summary = 'A file from the media library to download';

    public function attributes(): array
    {
        return [
            'mediaId' => Attribute::integer()->hidden(),
            'href' => Attribute::url()->required()->hidden(),
            'fileName' => Attribute::string()->max(255)->hidden(),
            'size' => Attribute::string()->max(50)->hidden(),
            'mimeType' => Attribute::string()->max(255)->hidden(),
            'label' => Attribute::string()->max(255)->thaana()->label(__('Label')),
        ];
    }

    public function rules(): array
    {
        return ['mediaId' => [new LibraryMedia]];
    }

    public function sanitize(Node $node, Sanitizer $sanitizer): ?Node
    {
        return Url::isSafe($node->attr('href')) ? $node : null;
    }

    protected function viewData(Node $node, $renderer): array
    {
        $media = static::findMedia([$node->attr('mediaId')])->first();

        return [
            ...parent::viewData($node, $renderer),
            'media' => $media,
            'href' => $media ? $media->getUrl() : $node->attr('href'),
            'label' => $node->attr('label') ?: ($media?->name ?? $node->attr('fileName') ?? __('Download')),
            'size' => $media?->human_readable_size ?? $node->attr('size'),
            'icon' => $media?->getIcon() ?? 'file',
        ];
    }
}
