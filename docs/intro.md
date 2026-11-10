---
title: Introduction
sidebar_position: 1.0
---

# Mediapicker

[javaabu/mediapicker](https://github.com/Javaabu/mediapicker) gives your Laravel app a media library: people upload files once, then pick them wherever a model needs an image, a gallery or a document. It's built on [spatie/laravel-medialibrary](https://spatie.be/docs/laravel-medialibrary/v11).

- **A library per user type.** Uploads belong to the user who made them (any guard, any user model). Who may see, edit and delete others' files is decided by permissions from [javaabu/permissions](https://github.com/Javaabu/permissions).
- **Library pages** in Blade ([javaabu/forms](https://github.com/Javaabu/forms) v2) or Inertia React ([@javaabu/forms](https://github.com/Javaabu/react-forms) and [Onu](https://github.com/Javaabu/onu)): grid and list views, search, type and date filters, uploads with progress, bulk delete, details and crops.
- **Picker fields.** `<x-mediapicker::picker>` in Blade, `<MediaPickerField>` in React and `MediaPickerField` for [javaabu/stencil](https://github.com/Javaabu/stencil) forms: one or several files, by type, with uploads from the dialog.
- **Attachments.** A post attaches library media to its own collections (`featured_image`, `gallery`), in order, without copying the files.
- **Conversions that do less work.** Each model declares the conversions its attachments need. They're queued, made only for the collections that need them, shared between models that ask for the same thing, never made twice, and can come in WebP and AVIF with responsive widths. `<x-mediapicker::image>` and `<Picture>` render them as `<picture>`.
- **An image cropper.** Crop to aspect-ratio presets on upload or later. Crops never touch the original; conversions that use a crop are made again from it.
- **Block editor blocks.** Image, gallery, file and video blocks for [javaabu/block-editor](https://github.com/Javaabu/block-editor) that pick from the library.
- **Dhivehi and RTL** throughout, and optional translated names, descriptions, alt text and captions with [javaabu/translatable](https://github.com/Javaabu/translatable).

```php
// routes/web.php
Route::mediapicker(['guard' => 'web_admin', 'prefix' => 'admin/media', 'name' => 'admin.media']);
```

```blade
<x-mediapicker::picker name="featured_image" :model="$post" type="image" required />
```

```php
$request->validate(['featured_image' => ['required', new LibraryMedia(type: 'image')]]);

$post->syncAttachmentsFromRequest('featured_image');
```

```blade
<x-mediapicker::image :model="$post" collection="featured_image" conversion="card" sizes="(min-width: 768px) 50vw, 100vw" />
```

Start with [Installation and setup](./installation-and-setup.md).
