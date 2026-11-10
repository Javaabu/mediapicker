---
title: Upgrading
sidebar_position: 1.25
---

# From the unreleased version on `main` to 1.0

1.0 keeps the core of the unreleased version (owner libraries, the `attachments` table, attachment collections and conversions, the `mediapicker:*` commands) and replaces its views, routes and authorization.

## Requirements

- PHP 8.4 and Laravel 12 or 13.
- javaabu/helpers, javaabu/activitylog 1 and javaabu/forms 1 are no longer required. javaabu/forms 2, javaabu/permissions 3, javaabu/stencil, javaabu/manifest and javaabu/block-editor are.

## Routes

`Mediapicker::registerRoutes($url, $middleware, …)` is gone. Use the route macro:

```php
// before
Mediapicker::registerRoutes('media', ['web', 'auth:web_admin']);

// after
Route::middleware('web')->group(fn () => Route::mediapicker(['guard' => 'web_admin', 'prefix' => 'media']));
```

Route names are the same (`media.index`, …, `media.bulk`), and `media.crop` is new. The old picker page (`?mode=picker`) is gone: pickers use the JSON API.

## Authorization

- `MediaOwner` no longer has `canViewAnyMedia()`, `canCreateMedia()`, `canDeleteAnyMedia()`, `canViewOthersMedia()`, `canEditOthersMedia()`, `canDeleteOthersMedia()`, `canViewMedia()`, `canEditMedia()` or `canDeleteMedia()`. The policy decides, from the same permission names, now registered with javaabu/permissions: run `php artisan permissions:sync`.
- `MediaOwner` requires `void` return types on `registerMediapickerCollections()` and `registerMediapickerConversions()`.
- `MediaPolicy::viewLogs()` is gone.
- The scopes `userVisible()` and `findForController()` are now `visibleTo($user)` and `inLibraryOf($user)`, which take the user.

## The media model

- It no longer implements javaabu/helpers' `AdminModel` or logs activity. To log changes, extend it, add javaabu/activitylog's `LogsActivity`, and set it as `mediapicker.media_model` and spatie's `media_model`.
- `$media->url($action)` is now `$media->libraryUrl($action)`.
- `getIcon()` returns a Lucide icon name (`image`, `file-text`), not Font Awesome or Material classes, and takes no arguments.
- New attributes: `alt` and `caption`.

## File types

Types come from `config('mediapicker.file_types')`, not javaabu/helpers' `AllowedMimeTypes`: `image`, `video`, `audio`, `document` (PDF, Word, Excel, PowerPoint, text, CSV) and `archive`. WebP and AVIF are images; SVG isn't accepted by default. `hasFileType('pdf')` becomes `hasFileType('document')`.

## Conversions

Attachment conversion files moved to the media's `conversions/attachments` folder and carry a fingerprint (`beach-card-3f9a1c0b2e.jpg`). Make them again, then remove the old files:

```bash
php artisan mediapicker:regenerate
php artisan media-library:clean   # spatie's: removes the old files from conversions/
```

`updateAttachmentMedia()` now returns every attachment of the collection in order (not only the new ones), and reorders media that were already attached.

## Views

The `material-admin-26` views and the `framework`, `default_layout`, `picker_layout` and `scripts_stack` config keys are gone. The pages are Blade (javaabu/forms v2) or Inertia React (`'views' => 'blade' | 'inertia'`), extending `config('mediapicker.layout')`. Use `<x-mediapicker::picker>` for picker fields.

## Migrations

The package now loads its migrations (call `Mediapicker::ignoreMigrations()` to publish them instead). A new migration adds nullable `translations` and `lang` columns to `media`.
