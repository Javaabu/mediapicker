---
title: Requirements
sidebar_position: 1.1
---

- PHP 8.4 or 8.5
- Laravel 12 or 13
- [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary) v11 (11.17 or later), installed with this package
- [javaabu/permissions](https://github.com/Javaabu/permissions) v3, [javaabu/forms](https://github.com/Javaabu/forms) v2, [javaabu/stencil](https://github.com/Javaabu/stencil) v1, [javaabu/manifest](https://github.com/Javaabu/manifest) v1 and [javaabu/block-editor](https://github.com/Javaabu/block-editor) v1, installed with this package
- GD or Imagick for image conversions. AVIF copies need a GD or ImageMagick built with AVIF; formats the driver can't write are skipped with a warning.

Optional:

- [inertiajs/inertia-laravel](https://github.com/inertiajs/inertia-laravel) v2, for the Inertia React pages (`mediapicker.views = inertia`)
- [javaabu/translatable](https://github.com/Javaabu/translatable) v3, to translate media names, descriptions, alt text and captions
- A queue worker: attachment conversions are queued by default (spatie's `queue_conversions_by_default`)
