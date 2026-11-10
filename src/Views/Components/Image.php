<?php

namespace Javaabu\Mediapicker\Views\Components;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;
use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Contracts\HasAttachments;
use Javaabu\Mediapicker\Http\Resources\MediaResource;
use Javaabu\Mediapicker\Models\Media;

/**
 * An attached image as `<picture>`: modern formats as sources, responsive widths as srcset,
 * width and height to avoid layout shift, and the media's alt text
 *
 * ```blade
 * <x-mediapicker::image :model="$post" collection="featured_image" conversion="card" sizes="(min-width: 768px) 50vw, 100vw" />
 * <x-mediapicker::image :attachment="$attachment" conversion="card" />
 * <x-mediapicker::image :media="$media" />   {{-- a library image, without attachment conversions --}}
 * ```
 */
class Image extends Component
{
    public ?Attachment $resolved_attachment;

    public ?Media $resolved_media;

    public function __construct(
        public ?Attachment $attachment = null,
        public ?Model $model = null,
        public string $collection = 'default',
        public ?Media $media = null,
        public string $conversion = '',
        public ?string $sizes = null,
        public ?string $alt = null,
        public string $loading = 'lazy',
    ) {
        $this->resolved_attachment = $attachment ?? ($model instanceof HasAttachments ? $model->getFirstAttachment($collection) : null);
        $this->resolved_media = $this->resolved_attachment?->media ?? $media;
    }

    public function shouldRender(): bool
    {
        return (bool) $this->resolved_media;
    }

    /**
     * @return array<int, array{type: string, srcset: string}>
     */
    public function sources(): array
    {
        $metadata = $this->metadata();

        if (! $metadata) {
            return [];
        }

        $conversion = $this->resolved_attachment->conversion($this->conversion);
        $main_format = $conversion->getFormat($this->resolved_media);
        $sources = [];

        foreach (['avif', 'webp', 'png', 'jpg', 'jpeg', 'gif'] as $format) {
            if ($format === $main_format || ! in_array($format, $metadata['formats'] ?? [], true)) {
                continue;
            }

            $sources[] = [
                'type' => 'image/' . ($format === 'jpg' ? 'jpeg' : $format),
                'srcset' => $this->resolved_attachment->getSrcset($this->conversion, $format),
            ];
        }

        return $sources;
    }

    public function src(): string
    {
        if ($this->resolved_attachment) {
            return $this->conversion
                ? $this->resolved_attachment->getAvailableUrl($this->conversion)
                : $this->resolved_attachment->getUrl();
        }

        return MediaResource::conversionUrl($this->resolved_media, 'mediapicker-large') ?? $this->resolved_media->getUrl();
    }

    public function srcset(): ?string
    {
        if (! $this->metadata()) {
            return null;
        }

        return $this->resolved_attachment->getSrcset($this->conversion) ?: null;
    }

    public function width(): ?int
    {
        return $this->metadata()['width'] ?? ($this->conversion ? null : $this->resolved_media->width);
    }

    public function height(): ?int
    {
        return $this->metadata()['height'] ?? ($this->conversion ? null : $this->resolved_media->height);
    }

    public function altText(): string
    {
        return $this->alt ?? $this->resolved_media->altText();
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function metadata(): ?array
    {
        if (! $this->resolved_attachment || ! $this->conversion) {
            return null;
        }

        return $this->resolved_attachment->conversionMetadata($this->conversion);
    }

    public function render(): View
    {
        return view('mediapicker::components.image');
    }
}
