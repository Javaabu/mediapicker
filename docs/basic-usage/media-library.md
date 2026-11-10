---
title: The media library
sidebar_position: 1
---

# What's in a library

A library is the media in an owner's library collection (`config('mediapicker.collection_name')`, default `mediapicker`) for one kind of user. An admin's library holds the files admins uploaded; a user's, the files users uploaded. Each upload remembers who uploaded it.

| A user with | Sees | Edits | Deletes |
| --- | --- | --- | --- |
| `view_media` | Their uploads | | |
| `view_others_media` | Everyone's | | |
| `edit_media` | | Their uploads, and may upload | |
| `edit_others_media` | | Everyone's | |
| `delete_media` | | | Their uploads |
| `delete_others_media` | | | Everyone's |

# The pages

`Route::mediapicker()` adds the library pages (see [Installation](../installation-and-setup.md#routes)):

| Route | Page |
| --- | --- |
| `media.index` (`GET /media`) | The library: grid or list, search, type, uploader and date filters, sorting, pages, bulk delete |
| `media.create` (`GET /media/create`) | Upload. Blade uploads one file with its details; React uploads several at once with progress |
| `media.store` (`POST /media`) | Saves an upload and goes to its edit page |
| `media.show` (`GET /media/{id}`) | A file with its details, URL and download |
| `media.edit` (`GET /media/{id}/edit`) | The details form, the preview and the cropper |
| `media.update` (`PATCH /media/{id}`) | Saves the details |
| `media.destroy` (`DELETE /media/{id}`) | Deletes the file and its attachments |
| `media.crop` (`PUT /media/{id}/crops/{preset}`) | Saves a crop (see [Cropping](cropping.md)) |
| `media.bulk` (`PUT /media`) | `action=delete` with `media[]` |

The index takes `search`, `type` (one or `type[]`), `owner=mine`, `date_from`, `date_to` (`Y-m-d`), `orderby` (`created_at`, `name`, `size`, `id`) and `order`, or `sort=name:asc`, `per_page` (up to 100) and `view` (`grid` or `list`).

Every route also answers JSON, which the pickers use: see [JSON API](../advanced-usage/json-api.md).

# Linking to the library

```php
use Javaabu\Mediapicker\Mediapicker;

Mediapicker::url('index');                 // the library of the current request, else of the signed-in guard, else the default
Mediapicker::url('edit', $media);
Mediapicker::url('index', route_name: 'admin.media');
$media->libraryUrl('show');
```

# Media details

| Attribute | Stored in |
| --- | --- |
| `name` | spatie's `name` column. Uploads are named after the file unless a name is given |
| `description`, `alt`, `caption` | Custom properties |
| `width`, `height` | Custom properties, read from images on upload |
| `crops` | Custom properties, per preset |

```php
$media->alt = 'ހުޅުމާލޭގެ ބީޗް';
$media->save();

$media->altText();      // the alt text, else the name
$media->file_type;      // image, video, audio, document, archive or null
$media->getIcon();      // a Lucide icon name for the type
$media->isOwnedBy($user);

Media::query()->visibleTo($user)->hasFileType('image')->search('ބީޗް')->get();
```

# File types

`config('mediapicker.file_types')` lists what the library accepts, by type, with mime types, an icon and an optional `max_size` (KB). Uploads larger than the type's size, or `mediapicker.max_upload_size`, or spatie's `max_file_size`, are refused. SVG isn't accepted by default, because an uploaded SVG can run scripts when it's opened from your domain.

```php
'file_types' => [
    'image' => ['mime_types' => ['image/jpeg', 'image/png', 'image/webp', 'image/avif', 'image/gif'], 'icon' => 'image', 'max_size' => 5120],
    // ...
],
```

`Javaabu\Mediapicker\Support\FileTypes` reads it: `typeOf($mime)`, `mimeTypes($types)`, `maxSize($types)`, `rules($types)`.
