<?php

namespace Javaabu\Mediapicker;

use Illuminate\Support\Facades\Route;
use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Http\Controllers\MediaController;
use Javaabu\Mediapicker\Models\Media;

class Mediapicker
{

    public static function collectionName(): string
    {
        return config('mediapicker.collection_name');
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
        return config('mediapicker.media_controller');
    }

    /**
     * @return class-string<Media>
     */
    public static function mediaModel(): string
    {
        return config('mediapicker.media_model');
    }

    public static function newMediaInstance(): Media
    {
        $media_class = static::mediaModel();

        return new $media_class;
    }

    public static function getViewName(string $view, string $framework = ''): string
    {
        if (! $framework) {
            $framework = config('mediapicker.framework');
        }

        return 'mediapicker::' . $framework . '.' . $view;
    }

    public static function getIconPack(string $framework = ''): string
    {
        if (! $framework) {
            $framework = config('mediapicker.framework');
        }

        return $framework == 'material-admin' ? 'material' : 'fontawesome';
    }

    /**
     * Register the admin routes
     */
    public static function registerRoutes(
        string $url = 'media',
        array|string  $middleware = [],
        array $resource_options = [],
        array $resource_names = [
            'index' =>	'media.index',
            'create' => 'media.create',
            'store' => 'media.store',
            'show' => 'media.show',
            'edit' => 'media.edit',
            'update' => 'media.update',
            'destroy' => 'media.destroy',
        ],
        string $bulk_name = 'media.bulk',
        string $picker_name = 'media.picker',
    )
    {
        $controller = static::mediaController();

        Route::match(['PUT', 'PATCH'], $url, [$controller, 'bulk'])
            ->name($bulk_name)
            ->middleware($middleware);

        /*Route::get($url . '/picker', [$controller, 'picker'])
            ->name($picker_name)
            ->middleware($middleware);*/

        $resource_options = array_merge([
            'parameters' => [
                $url => 'media',
            ]
        ], $resource_options);

        Route::resource('media', $controller, $resource_options)
            ->middleware($middleware)
            ->names($resource_names);
    }
}
