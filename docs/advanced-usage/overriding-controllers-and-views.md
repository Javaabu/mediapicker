---
title: Overriding controllers and views
sidebar_position: 4
---

# The controller

Extend the package's controller and point the config (or one `Route::mediapicker()`) at it:

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Javaabu\Mediapicker\Http\Controllers\MediaController as BaseController;

class MediaController extends BaseController
{
    // only images in this app's library
    protected function filters(Request $request): array
    {
        return [...parent::filters($request), 'type' => ['image']];
    }
}
```

```php
// config/mediapicker.php
'media_controller' => App\Http\Controllers\MediaController::class,

// or for one library
Route::mediapicker(['controller' => App\Http\Controllers\MediaController::class]);
```

Protected methods to override: `addToLibrary()` (what an upload saves), `query()` (the library query), `filters()`, `pageData()` (what every page gets), `detailsForm()` (the stencil form of the edit page), `findMedia()`, `resource()`.

The details form is `Javaabu\Mediapicker\Stencil\MediaDetailsForm`; extend it and return yours from `detailsForm()` to add fields (save them in `update()`).

# Blade views

```bash
php artisan vendor:publish --tag=mediapicker-views
```

| View | |
| --- | --- |
| `media/index`, `create`, `edit`, `show` | The pages; they extend `config('mediapicker.layout')` |
| `media/partials/filters`, `grid`, `list`, `thumb`, `preview`, `alerts` | Their parts |
| `components/picker`, `components/image` | `<x-mediapicker::picker>` and `<x-mediapicker::image>` |
| `stencil/picker` | The stencil field |
| `blocks/image`, `gallery`, `file`, `media-video` | The editor blocks' markup |

# React pages and components

The pages (`resources/js/Pages/Media/*.tsx`) and components are shipped through javaabu/manifest. Override any single file:

```bash
php artisan manifest:override mediapicker js/Pages/Media/Index.tsx
```

The copy in `resources/vendor/mediapicker/js/Pages/Media/Index.tsx` wins, and its relative imports still resolve to the package. Page props are typed in `@javaabu/mediapicker` (`MediaIndexProps`, `MediaEditProps`, …).

# Styles

The package's stylesheet (`resources/css/mediapicker.css`) uses Onu's CSS variables when the page has them (`--onu-primary`, `--onu-radius`, …), with plain fallbacks. Override the `--mp-*` variables on `.mp-dialog`, `.mp-field`, `.mp-library` or `.mp-block` to restyle it.
