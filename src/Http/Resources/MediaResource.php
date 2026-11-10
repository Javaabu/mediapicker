<?php

namespace Javaabu\Mediapicker\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use InvalidArgumentException;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\Models\Media;
use Javaabu\Mediapicker\Support\FileTypes;
use Javaabu\Mediapicker\Support\Translations;

/**
 * A library media item, as the pages, the pickers and the JSON API see it
 *
 * @mixin Media
 */
class MediaResource extends JsonResource
{
    /** The route name prefix of the library to link to; null uses the current one */
    protected ?string $route_name = null;

    public function forLibrary(?string $route_name): static
    {
        $this->route_name = $route_name;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Media $media */
        $media = $this->resource;
        $user = $request->user();

        return [
            'id' => $media->getKey(),
            'uuid' => $media->uuid,
            'name' => $media->name,
            'file_name' => $media->file_name,
            'mime_type' => $media->mime_type,
            'type' => $media->file_type,
            'icon' => $media->getIcon(),
            'size' => (int) $media->size,
            'human_size' => $media->human_readable_size,
            'width' => $media->width,
            'height' => $media->height,
            'description' => $media->description,
            'alt' => $media->alt,
            'caption' => $media->caption,
            'url' => $media->getUrl(),
            'thumb' => static::conversionUrl($media, 'mediapicker-thumb'),
            'preview' => static::conversionUrl($media, 'mediapicker-large') ?? ($media->isImage() ? $media->getUrl() : null),
            'crops' => (object) $media->getCustomProperty('crops', []),
            'translations' => Translations::forMedia($media),
            'owner' => $this->owner($media),
            'created_at' => $media->created_at?->toIso8601String(),
            'updated_at' => $media->updated_at?->toIso8601String(),
            'can' => [
                'update' => (bool) $user?->can('update', $media),
                'delete' => (bool) $user?->can('delete', $media),
            ],
            'links' => $this->links($media),
        ];
    }

    /**
     * The URL of a library conversion once it's generated; images fall back to the original
     */
    public static function conversionUrl(Media $media, string $conversion): ?string
    {
        if ($media->hasGeneratedConversion($conversion)) {
            return $media->getUrl($conversion);
        }

        if ($conversion === 'mediapicker-thumb' && FileTypes::typeOf($media->mime_type) === 'image') {
            return $media->getUrl();
        }

        return null;
    }

    /**
     * @return array{id: mixed, name: ?string}|null
     */
    protected function owner(Media $media): ?array
    {
        if (! $media->relationLoaded('model') || ! $media->model) {
            return null;
        }

        return [
            'id' => $media->model->getKey(),
            'name' => $media->model->getAttribute('name'),
        ];
    }

    /**
     * @return array<string, string>|null
     */
    protected function links(Media $media): ?array
    {
        try {
            return [
                'show' => Mediapicker::url('show', $media, route_name: $this->route_name),
                'edit' => Mediapicker::url('edit', $media, route_name: $this->route_name),
                'update' => Mediapicker::url('update', $media, route_name: $this->route_name),
                'destroy' => Mediapicker::url('destroy', $media, route_name: $this->route_name),
            ];
        } catch (InvalidArgumentException) {
            return null;
        }
    }
}
