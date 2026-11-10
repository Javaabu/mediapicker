---
title: Translatable
sidebar_position: 5
---

With [javaabu/translatable](https://github.com/Javaabu/translatable) v3 installed and languages set up, media names, descriptions, alt text and captions can be translated. Without it, everything works in one language; nothing references translatable's classes.

```bash
composer require javaabu/translatable
php artisan migrate   # the package's migration adds nullable translations and lang columns to media
```

# The model

When `mediapicker.media_model` is null (the default) and translatable is on, the package uses `Javaabu\Mediapicker\Models\Translatable\Media`, which uses translatable's JSON storage. Set spatie's model to it too, so uploads are created as it:

```php
// config/media-library.php
'media_model' => Javaabu\Mediapicker\Models\Translatable\Media::class,
```

To use your own, extend it (or the base model with `IsJsonTranslatable`) and set `mediapicker.media_model`.

# Reading and writing

```php
$media->name;         // in the current locale, falling back to the default
$media->alt_dv;       // the Dhivehi alt text, or null
$media->fill(['name_en' => 'Beach', 'name_dv' => 'ބީޗް', 'caption_dv' => 'ހުޅުމާލެ'])->save();
```

`description`, `alt` and `caption` are custom properties in the default language, and translated values go in `translations`, like `name`.

# Forms and the API

- The edit page's details form (stencil) shows one input per language, in both stacks.
- Uploads and updates take values per locale (`name_en`, `name_dv`, …): the primary language keeps the rules, the others are optional.
- `MediaResource` has `translations`: every translatable field in every locale.
- Inertia pages get `translatable: true`.

`<x-mediapicker::image>`, `<Picture>` and the editor blocks use the alt text in the current locale.
