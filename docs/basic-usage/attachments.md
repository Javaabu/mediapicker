---
title: Attachments
sidebar_position: 3
---

An attachment links a library media to a model, in one of the model's attachment collections, with an order. The file isn't copied: one upload can be the featured image of one post and part of another's gallery. Deleting the model deletes its attachments; deleting the media deletes every attachment of it.

# Collections

Collections work like spatie's media collections. Declare them when you need limits; any name works without declaring it.

```php
use Javaabu\Mediapicker\Concerns\InteractsWithAttachments;
use Javaabu\Mediapicker\Contracts\HasAttachments;
use Javaabu\Mediapicker\Support\FileTypes;
use Spatie\MediaLibrary\MediaCollections\File;

class Post extends Model implements HasAttachments
{
    use InteractsWithAttachments;

    public function registerAttachmentCollections(): void
    {
        $this->addAttachmentCollection('featured_image')
            ->singleFile()
            ->acceptsFile(fn (File $file) => FileTypes::isAllowed($file->mimeType, 'image'));

        $this->addAttachmentCollection('gallery')
            ->onlyKeepLatest(20);

        $this->addAttachmentCollection('documents')
            ->acceptsMimeTypes(['application/pdf']);
    }
}
```

Attaching media a collection doesn't accept throws `MediaUnacceptableForCollection`.

# Attaching

```php
$post->updateAttachmentMedia([$media_3->id, $media_1->id], 'gallery'); // exactly these, in this order
$post->updateAttachmentMedia([], 'gallery');                          // clear it
$post->syncAttachmentsFromRequest('gallery');                        // from a picker field (see Picker fields)

$post->addAttachment($media)->toAttachmentCollection('gallery');      // add one
$post->addAttachment($media_id)->setOrder(1)->toAttachmentCollection('gallery');
$post->addAttachmentFromRequest('featured_image')->toAttachmentCollection('featured_image');

$post->deleteAttachment($attachment);
$post->clearAttachmentCollection('gallery');
$post->clearAttachmentCollectionExcept('gallery', $keep);
```

On a model that isn't saved yet, attachments are saved after it's created.

# Reading

```php
$post->hasAttachments('gallery');
$post->getAttachments('gallery');          // Collection<Attachment>, in order
$post->getAttachmentMedia('gallery');      // Collection<Media>, in order
$post->getFirstAttachment('featured_image');
$post->getFirstAttachmentMedia('featured_image');

$post->getFirstAttachmentUrl('featured_image');           // the original
$post->getFirstAttachmentUrl('featured_image', 'card');   // a conversion
$post->getFirstAttachmentPath('featured_image', 'card');
$post->getFirstAttachmentTemporaryUrl(now()->addHour(), 'featured_image', 'card');

Post::query()->withAttachments()->get(); // eager loads attachments.media
```

On an attachment:

| Method | |
| --- | --- |
| `getUrl($conversion = '')`, `getPath()`, `getTemporaryUrl()` | The original or a conversion, whether or not it's made yet |
| `getAvailableUrl(['card', 'thumb'])` | The first of these conversions that's made, else the original. Use it in pages, so queued conversions never show a broken image |
| `getFormatUrl('card', 'webp')` | The conversion's copy in another format, once made |
| `getSrcset('card', $format = null)` | A srcset of the conversion's responsive widths and its full width |
| `conversionMetadata('card')` | `width`, `height`, `formats` and `widths` of what was made, or null |
| `hasGeneratedConversion('card')` | Whether it's made for the current media and crop |
| `conversion('card')`, `conversions()` | The model's conversions for this collection |

# Commands

| Command | |
| --- | --- |
| `php artisan mediapicker:regenerate {modelType?} {--ids=*} {--only=*} {--starting-from-id=} {--exclude-starting-id} {--only-missing} {--queue-all} {--force}` | Make the conversions of attachments again |
| `php artisan mediapicker:clean {modelType?} {collectionName?} {--dry-run} {--delete-orphaned} {--skip-conversions} {--rate-limit=} {--force}` | Delete conversion files no attachment uses any more (after you change a conversion, or a crop), and with `--delete-orphaned`, attachments whose model is gone |
| `php artisan mediapicker:clear {modelType?} {collectionName?} {--force}` | Delete attachments |

Schedule `mediapicker:clean` (say daily) to keep the disk tidy.
