<?php

namespace Javaabu\Mediapicker;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Http\Controllers\MediaController;
use Javaabu\Mediapicker\Models\Media;
use Javaabu\Mediapicker\Routing\MediapickerRoutes;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Mediapicker
{
    /** Whether the package loads its migrations. Turn off with `Mediapicker::ignoreMigrations()`. */
    public static bool $runs_migrations = true;

    public static function ignoreMigrations(): void
    {
        static::$runs_migrations = false;
    }

    /**
     * The collection on owners that holds their library uploads
     */
    public static function collectionName(): string
    {
        return config('mediapicker.collection_name', 'mediapicker');
    }

    /**
     * @return class-string<Attachment>
     */
    public static function attachmentModel(): string
    {
        return config('mediapicker.attachment_model');
    }

    /**
     * @return class-string<MediaController>
     */
    public static function mediaController(): string
    {
        return config('mediapicker.media_controller', MediaController::class);
    }

    /**
     * @return class-string<Media>
     */
    public static function mediaModel(): string
    {
        return config('mediapicker.media_model', Media::class);
    }

    public static function newMediaInstance(): Media
    {
        $media_class = static::mediaModel();

        return new $media_class;
    }

    /**
     * The route name prefix of the library to link to: the library of the current request,
     * else the one registered for a guard the visitor is signed in with, else the default one
     */
    public static function routeName(?string $guard = null): string
    {
        if ($guard) {
            return MediapickerRoutes::nameFor($guard) ?? 'media';
        }

        if ($current = MediapickerRoutes::currentName(request())) {
            return $current;
        }

        foreach (MediapickerRoutes::registered() as $registered_guard => $name) {
            if ($registered_guard !== '*' && Auth::guard($registered_guard)->check()) {
                return $name;
            }
        }

        return MediapickerRoutes::nameFor() ?? 'media';
    }

    /**
     * A URL of the library: `index`, `create`, `store`, `bulk`, or `show`, `edit`, `update`,
     * `destroy` and `crop` with a media
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function url(string $action, ?BaseMedia $media = null, array $parameters = [], ?string $route_name = null): string
    {
        $name = ($route_name ?: static::routeName()) . ".{$action}";

        if (! Route::has($name)) {
            throw new InvalidArgumentException("The route [{$name}] isn't registered. Add Route::mediapicker() to your routes.");
        }

        if ($media && in_array($action, ['show', 'edit', 'update', 'destroy', 'crop'], true)) {
            $parameters = ['media' => $media->getKey(), ...$parameters];
        }

        return route($name, $parameters);
    }
}
