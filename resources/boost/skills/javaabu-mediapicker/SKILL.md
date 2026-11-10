---
name: javaabu-mediapicker
description: Add a media library, picker fields, attached images and galleries, image conversions and cropping to a Laravel app with javaabu/mediapicker (on spatie/laravel-medialibrary). Use whenever the project depends on javaabu/mediapicker and the task adds an image, gallery or file to a model or form, saves or validates picked media, renders attached images or responsive pictures, declares or regenerates conversions, crops images, registers or customizes the media library pages or JSON API, uses media blocks in javaabu/block-editor, or changes who may see, upload, edit or delete media.
license: MIT
metadata:
  author: Javaabu
---

# Media with javaabu/mediapicker

People upload files once to their **library**; models **attach** library media to their own collections (`featured_image`, `gallery`) through the `attachments` table, without copying files. The docs ship with the package: `vendor/javaabu/mediapicker/llms.txt` lists every page in `vendor/javaabu/mediapicker/docs/`.

## Setup in an app

```php
// config/media-library.php
'media_model' => Javaabu\Mediapicker\Models\Media::class,

// the user model(s) that upload
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

// routes/web.php, inside the app's group
Route::mediapicker(['guard' => 'web_admin', 'prefix' => 'admin/media', 'name' => 'admin.media']);
```

Then `php artisan permissions:sync` and give roles `view_media`, `edit_media`, `delete_media` (and the `*_others_media` ones to see or change everyone's uploads).

## A model with an image and a gallery

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Mediapicker\Concerns\InteractsWithAttachments;
use Javaabu\Mediapicker\Contracts\HasAttachments;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Post extends Model implements HasAttachments
{
    use InteractsWithAttachments;

    public function registerAttachmentCollections(): void
    {
        $this->addAttachmentCollection('featured_image')->singleFile();
        $this->addAttachmentCollection('gallery');
    }

    public function registerAttachmentConversions(?Media $media = null): void
    {
        $this->addAttachmentConversion('card')
            ->usingCrop('wide')
            ->fit(Fit::Crop, 800, 450)
            ->formats(['avif', 'webp'])
            ->withResponsiveImages()
            ->performOnCollections('featured_image');

        $this->addAttachmentConversion('thumb')
            ->fit(Fit::Crop, 300, 300)
            ->performOnCollections('gallery');
    }
}
```

## The form and the controller

```blade
<x-forms::form method="PATCH" :action="route('admin.posts.update', $post)" :model="$post">
    <x-forms::text name="title" required />
    <x-mediapicker::picker name="featured_image" type="image" required />
    <x-mediapicker::picker name="gallery" type="image" multiple />
    <x-forms::submit />
</x-forms::form>
```

```php
use Javaabu\Mediapicker\Rules\LibraryMedia;

public function update(Request $request, Post $post): RedirectResponse
{
    $this->authorize('update', $post);

    $validated = $request->validate([
        'title' => ['required', 'string', 'max:255'],
        'featured_image' => ['required', new LibraryMedia(type: 'image')],
        'gallery' => ['nullable', 'array', 'max:20'],
        'gallery.*' => [new LibraryMedia(type: 'image')],
    ]);

    $post->update(['title' => $validated['title']]);

    $post->syncAttachmentsFromRequest('featured_image');
    $post->syncAttachmentsFromRequest('gallery');

    return redirect()->route('admin.posts.edit', $post);
}
```

## Showing images

```blade
<x-mediapicker::image :model="$post" collection="featured_image" conversion="card" sizes="(min-width: 768px) 50vw, 100vw" />

@foreach($post->getAttachments('gallery') as $attachment)
    <x-mediapicker::image :attachment="$attachment" conversion="thumb" />
@endforeach
```

React: pass `Mediapicker::image($post, 'featured_image', 'card')` as a prop and render `<Picture image={image} />`. In code: `$attachment->getAvailableUrl('card')` (the original until a queued conversion is made).

## More

- [React and stencil pickers](references/react-and-stencil.md)
- [Conversions, crops and blocks](references/conversions-and-crops.md)
- [Common mistakes](references/common-mistakes.md)
