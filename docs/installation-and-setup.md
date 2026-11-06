---
title: Installation & Setup
sidebar_position: 1.2
---

# Installing

```bash
composer require javaabu/mediapicker
```

The service provider is discovered automatically, along with spatie's.

# Migrating

Publish spatie's media table first, then migrate. The package loads its own migrations: the `attachments` table, and nullable `translations` and `lang` columns on `media` (used only with javaabu/translatable).

```bash
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan migrate
```

To publish the package's migrations instead of loading them:

```php
// AppServiceProvider::register()
\Javaabu\Mediapicker\Mediapicker::ignoreMigrations();
```

```bash
php artisan vendor:publish --tag=mediapicker-migrations
```

# The media model

Point spatie at the package's media model, so uploads are created as library media:

```php
// config/media-library.php
'media_model' => Javaabu\Mediapicker\Models\Media::class,
```

With javaabu/translatable, use `Javaabu\Mediapicker\Models\Translatable\Media::class` (see [Translatable](advanced-usage/translatable.md)).

# Owners

Users upload to their own library. Make each user model that has a library a `MediaOwner`:

```php
use Javaabu\Mediapicker\Concerns\OwnsMedia;
use Javaabu\Mediapicker\Contracts\MediaOwner;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Admin extends Authenticatable implements MediaOwner
{
    use HasRoles;
    use InteractsWithMedia;
    use OwnsMedia;

    public function registerMediaCollections(): void
    {
        $this->registerMediapickerCollections();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->registerMediapickerConversions($media);
    }
}
```

Each kind of user (each morph type) has its own library: admins never see users' uploads, whatever their permissions.

# Models that attach media

A model that uses library media implements `HasAttachments`:

```php
use Javaabu\Mediapicker\Concerns\InteractsWithAttachments;
use Javaabu\Mediapicker\Contracts\HasAttachments;

class Post extends Model implements HasAttachments
{
    use InteractsWithAttachments;
}
```

See [Attachments](basic-usage/attachments.md) for collections, and [Conversions](basic-usage/conversions.md) for the image sizes it needs.

# Permissions

The package registers its permissions with javaabu/permissions. Create them, then give them to roles:

```bash
php artisan permissions:sync
```

| Permission | Lets a user |
| --- | --- |
| `view_media` | Open the library and see their own uploads |
| `view_others_media` | See everyone's uploads (of their kind of user) |
| `edit_media` | Upload, and edit their own uploads |
| `edit_others_media` | Edit anyone's uploads |
| `delete_media` | Delete their own uploads |
| `delete_others_media` | Delete anyone's uploads |

See [Permissions and policies](advanced-usage/permissions-and-policies.md).

# Routes

The library isn't loaded automatically. Register it in your routes file, inside the group with your middleware:

```php
// routes/web.php
Route::middleware('web')->group(function () {
    Route::mediapicker();
});

// or for an admin guard
Route::middleware('web')->group(function () {
    Route::mediapicker(['guard' => 'web_admin', 'prefix' => 'admin/media', 'name' => 'admin.media']);
});
```

| Option | Default | |
| --- | --- | --- |
| `guard` | `null` (the default guard) | The guard the library is for; its middleware is `auth:{guard}` |
| `prefix` | `media` | The URL prefix |
| `name` | `media` | The route name prefix (`media.index`, `media.edit`, …) |
| `middleware` | `['auth']` or `['auth:{guard}']` | Replaces the default middleware |
| `controller` | `config('mediapicker.media_controller')` | Another controller for these routes |

Pickers find the library of the signed-in user's guard on their own.

# Views

Choose the stack of the library pages:

```php
// config/mediapicker.php (php artisan vendor:publish --tag=mediapicker-config)
'views' => 'blade',          // or 'inertia'
'layout' => 'layouts.admin', // Blade pages extend it…
'layout_section' => 'content', // …and fill this section
```

## Blade

The pages and the picker use javaabu/forms v2 components, and their scripts and styles come through javaabu/manifest: they're injected into the page automatically (or use `@manifestStyles` and `@manifestScripts`). Apps that build with Vite get them in their build through manifest's Vite plugin; apps without Vite get the prebuilt files in the package's `dist`.

## Inertia React

Install `inertiajs/inertia-laravel` and set `'views' => 'inertia'`. The pages are `mediapicker::Media/Index`, `Create`, `Edit` and `Show`, resolved by `@javaabu/manifest/inertia`'s `resolvePage()`. Install what they import:

```bash
php artisan manifest:check   # lists the npm packages the app needs, the React ones once @inertiajs/react is installed
```

Share the picker config with every page, so React pickers know the library:

```php
// HandleInertiaRequests::share()
'mediapicker' => fn () => \Javaabu\Mediapicker\Mediapicker::pickerConfig(),
```

```tsx
// your layout
import { MediapickerProvider } from "@javaabu/mediapicker"

<MediapickerProvider config={usePage().props.mediapicker}>{children}</MediapickerProvider>
```

Register the stencil field type once, if you use stencil forms:

```tsx
import { StencilMediaPicker } from "@javaabu/mediapicker"

<StencilProvider components={{ mediapicker: StencilMediaPicker }}>
```

# Queue

Attachment conversions are queued unless they're `nonQueued()`, following spatie's `queue_conversions_by_default`. Run a worker (`php artisan queue:work`). The library thumbnail is made right away, so pickers can show it.

# Publishing

| Tag | What |
| --- | --- |
| `mediapicker-config` | `config/mediapicker.php` |
| `mediapicker-migrations` | The migrations |
| `mediapicker-views` | The Blade views, to `resources/views/vendor/mediapicker` |
| `mediapicker-translations` | `lang/en.json` and `lang/dv.json`, to `lang/vendor/mediapicker` |

React pages and components are overridden one file at a time with `php artisan manifest:override mediapicker js/Pages/Media/Index.tsx` (see [Overriding controllers and views](advanced-usage/overriding-controllers-and-views.md)).
