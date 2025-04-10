<?php

namespace Javaabu\Mediapicker\AttachmentCollections;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaAdderFactory
{
    public static function create(Model $subject, int|string|Media $media): MediaAdder
    {
        /** @var MediaAdder $mediaAdder */
        $mediaAdder = app(MediaAdder::class);

        return $mediaAdder
            ->setSubject($subject)
            ->setMedia($media);
    }

    public static function createFromRequest(Model $subject, string $key): MediaAdder
    {
        return static::createMultipleFromRequest($subject, [$key])->first();
    }

    /**
     * @return Collection<MediaAdder>
     */
    public static function createMultipleFromRequest(Model $subject, array $keys = []): Collection
    {
        return collect($keys)
            ->map(function (string $key) use ($subject) {
                $key = trim(basename($key), './');

                $media = request()->input($key);

                if (! is_array($media)) {
                    return static::create($subject, $media);
                }

                return array_map(fn ($media_object) => static::create($subject, $media_object), $media);
            })->flatten();
    }
}
