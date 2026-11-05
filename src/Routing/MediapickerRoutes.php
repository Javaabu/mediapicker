<?php

namespace Javaabu\Mediapicker\Routing;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Javaabu\Mediapicker\Http\Controllers\MediaController;

/**
 * Registers the media library for `Route::mediapicker()`
 *
 * ```php
 * Route::mediapicker();                                                      // /media, media.*, auth
 * Route::mediapicker(['guard' => 'web_admin', 'prefix' => 'admin/media', 'name' => 'admin.media']);
 * ```
 */
class MediapickerRoutes
{
    /** The route action key that holds the guard the routes were registered for */
    public const string GUARD_ACTION = 'mediapicker_guard';

    /** The route action key that holds the route name prefix of the library */
    public const string NAME_ACTION = 'mediapicker_name';

    /** @var array<string, string> guard (or `*` for the default guard) => route name prefix */
    protected static array $registered = [];

    /**
     * @param  array{
     *     guard?: string|null,
     *     prefix?: string,
     *     name?: string,
     *     middleware?: string|array<int, string>|null,
     *     controller?: class-string|null
     * }  $options
     */
    public static function register(Router $router, array $options = []): void
    {
        $guard = $options['guard'] ?? null;
        $prefix = trim($options['prefix'] ?? 'media', '/');
        $name = rtrim($options['name'] ?? 'media', '.');
        $middleware = $options['middleware'] ?? [$guard ? "auth:{$guard}" : 'auth'];
        $controller = $options['controller'] ?? config('mediapicker.media_controller', MediaController::class);

        static::$registered[$guard ?? '*'] = $name;

        $router->group([
            'prefix' => $prefix,
            'as' => "{$name}.",
            'middleware' => $middleware,
            self::GUARD_ACTION => $guard,
            self::NAME_ACTION => $name,
        ], function (Router $router) use ($controller) {
            $router->get('/', [$controller, 'index'])->name('index');
            $router->post('/', [$controller, 'store'])->name('store');
            $router->match(['PUT', 'PATCH'], '/', [$controller, 'bulk'])->name('bulk');
            $router->get('/create', [$controller, 'create'])->name('create');
            $router->get('/{media}', [$controller, 'show'])->whereNumber('media')->name('show');
            $router->get('/{media}/edit', [$controller, 'edit'])->whereNumber('media')->name('edit');
            $router->match(['PUT', 'PATCH'], '/{media}', [$controller, 'update'])->whereNumber('media')->name('update');
            $router->delete('/{media}', [$controller, 'destroy'])->whereNumber('media')->name('destroy');
            $router->put('/{media}/crops/{preset}', [$controller, 'crop'])->whereNumber('media')->name('crop');
        });
    }

    /**
     * The guard the current route was registered for, or null for the default guard
     */
    public static function guard(?Request $request): ?string
    {
        return $request?->route()?->getAction(self::GUARD_ACTION);
    }

    /**
     * The route name prefix of the library the request came through, e.g. `admin.media`
     */
    public static function currentName(?Request $request): ?string
    {
        return $request?->route()?->getAction(self::NAME_ACTION);
    }

    /**
     * The route name prefix of the library registered for a guard. Without a guard: the library
     * registered without one, else the first one registered.
     */
    public static function nameFor(?string $guard = null): ?string
    {
        if ($guard && isset(static::$registered[$guard])) {
            return static::$registered[$guard];
        }

        return static::$registered['*'] ?? (static::$registered ? reset(static::$registered) : null);
    }

    /**
     * @return array<string, string>
     */
    public static function registered(): array
    {
        return static::$registered;
    }

    public static function forgetRegistered(): void
    {
        static::$registered = [];
    }
}
