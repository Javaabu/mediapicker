<?php

namespace Javaabu\Mediapicker\Support;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Contracts\HasAttachments;
use Javaabu\Mediapicker\Views\Components\Image;

/**
 * An attached image as data for React's `<Picture>`: the same src, srcset, sources, size
 * and alt that `<x-mediapicker::image>` renders
 *
 * ```php
 * 'image' => ImageData::for($post, 'featured_image', 'card'),
 * ```
 */
class ImageData
{
    /**
     * @return array{
     *     src: string,
     *     srcset: ?string,
     *     sizes: ?string,
     *     sources: array<int, array{type: string, srcset: string}>,
     *     width: ?int,
     *     height: ?int,
     *     alt: string
     * }|null
     */
    public static function for(Model|Attachment|null $subject, string $collection = 'default', string $conversion = '', ?string $sizes = null): ?array
    {
        $attachment = match (true) {
            $subject instanceof Attachment => $subject,
            $subject instanceof HasAttachments => $subject->getFirstAttachment($collection),
            default => null,
        };

        if (! $attachment?->media) {
            return null;
        }

        $image = new Image(attachment: $attachment, conversion: $conversion, sizes: $sizes);

        return [
            'src' => $image->src(),
            'srcset' => $image->srcset(),
            'sizes' => $sizes,
            'sources' => $image->sources(),
            'width' => $image->width(),
            'height' => $image->height(),
            'alt' => $image->altText(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function forCollection(HasAttachments $model, string $collection = 'default', string $conversion = '', ?string $sizes = null): array
    {
        return $model->getAttachments($collection)
            ->map(fn (Attachment $attachment) => static::for($attachment, conversion: $conversion, sizes: $sizes))
            ->filter()
            ->values()
            ->all();
    }
}
