# Mediapicker

[![Tests](https://github.com/n4ff4h/mediapicker/actions/workflows/tests.yml/badge.svg?branch=next-gen)](https://github.com/n4ff4h/mediapicker/actions/workflows/tests.yml?query=branch%3Anext-gen)
[![codecov](https://codecov.io/gh/n4ff4h/mediapicker/branch/next-gen/graph/badge.svg)](https://codecov.io/gh/n4ff4h/mediapicker/tree/next-gen)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/javaabu/mediapicker.svg)](https://packagist.org/packages/javaabu/mediapicker)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE.md)

A media library for Laravel on [spatie/laravel-medialibrary](https://github.com/spatie/laravel-medialibrary): people upload once and pick files wherever a model needs an image, a gallery or a document.

- Library pages in Blade ([javaabu/forms](https://github.com/Javaabu/forms) v2) or Inertia React, with uploads, search, filters, details and bulk delete, and a JSON API
- Picker fields: `<x-mediapicker::picker>`, React `MediaPickerField` and a [javaabu/stencil](https://github.com/Javaabu/stencil) field
- Attachments with their own conversions: queued, only for the collections that need them, shared and never made twice, WebP/AVIF and responsive widths, rendered as `<picture>`
- Non-destructive cropping to aspect-ratio presets
- Image, gallery, file and video blocks for [javaabu/block-editor](https://github.com/Javaabu/block-editor)
- A library per guard, with permissions and an overridable policy through [javaabu/permissions](https://github.com/Javaabu/permissions)
- Dhivehi and RTL, and optional translated details with [javaabu/translatable](https://github.com/Javaabu/translatable)

```php
Route::mediapicker(['guard' => 'web_admin', 'prefix' => 'admin/media', 'name' => 'admin.media']);
```

```blade
<x-mediapicker::picker name="gallery" :model="$post" type="image" multiple />
<x-mediapicker::image :model="$post" collection="featured_image" conversion="card" />
```

## Documentation

The docs are in [`docs/`](docs/intro.md) and on [docs.javaabu.com/mediapicker](https://docs.javaabu.com/mediapicker). AI assistants: start with [`llms.txt`](llms.txt); Laravel Boost picks up the package's guideline and skill.

Find yourself stuck using the package? Found a bug? Do you have general questions or suggestions for improving this package? Feel free to create an [issue](../../issues) on GitHub, we'll try to address it as soon as possible.

## Testing

```bash
composer test
pnpm install && pnpm test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email [info@javaabu.com](mailto:info@javaabu.com) instead of using the issue tracker.

## Credits

- [Javaabu Pvt. Ltd.](https://github.com/javaabu)
- [Arushad Ahmed (@dash8x)](http://arushad.com)
- [FlameXode (@WovenCoast)](https://github.com/WovenCoast)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
