---
title: Picker fields
sidebar_position: 2
---

A picker field chooses media from the library (and uploads to it) and submits their ids. You validate them with `LibraryMedia` and save them to an attachment collection.

# Blade

```blade
<x-forms::form method="PATCH" :action="route('posts.update', $post)" :model="$post">
    <x-mediapicker::picker name="featured_image" type="image" required />
    <x-mediapicker::picker name="gallery" type="image" multiple :label="__('ގެލަރީ')" />
    <x-mediapicker::picker name="attachments" collection="files" type="document" multiple />

    <x-forms::submit />
</x-forms::form>
```

| Attribute | Default | |
| --- | --- | --- |
| `name` | | The input name. Several are submitted as `name[]` |
| `label` | The name, headlined | |
| `model` | The form's bound model | The model whose attachments are the value |
| `collection` | The name | The attachment collection to read |
| `type` | any | `image`, `video`, `audio`, `document`, `archive`, or a list |
| `multiple` | `false` | Choose several, in order |
| `required`, `disabled`, `help`, `show-label` | | As other javaabu/forms fields |
| `upload` | `true` | Allow uploads from the dialog |
| `route` | The signed-in guard's library | The route name prefix of the library (`admin.media`) |
| `url` | | The library's index URL, instead of `route` |
| `value` | | Media, ids or a collection, instead of the model's |

The value is the old input after a failed validation, else `value`, else the model's attachments. Only media the user may see are shown. The field shows the chosen media with their thumbnails; the dialog searches, filters by type, loads more, and uploads with progress. People reorder and remove media in the field. It submits an empty value when nothing is chosen, so clearing it is saved too.

The script (`mediapicker::picker`) is required by the component; manifest adds it to the page. Fields added to the page later (modals, Livewire) are mounted on their own. From your own scripts:

```js
const media = await window.Mediapicker.open({ url: "/admin/media", type: "image", multiple: true })
window.Mediapicker.field(element).set(media)
```

# Inertia React

`MediaPickerField` is a controlled field: bind it with `value` and `onChange` (see [Javaabu/react-forms#23](https://github.com/Javaabu/react-forms/issues/23)). Its value is media items (`MediaItem`), and it submits their ids.

```tsx
import { MediaPickerField, type MediaItem } from "@javaabu/mediapicker"
import { Form, Submit } from "@javaabu/forms"

export default function Edit({ post, featured, gallery }: Props) {
  const [image, setImage] = useState<MediaItem | null>(featured)
  const [images, setImages] = useState<MediaItem[]>(gallery)

  return (
    <Form action={`/posts/${post.id}`} method="PATCH" model={post}>
      <MediaPickerField name="featured_image" type="image" required value={image} onChange={(value) => setImage(value as MediaItem | null)} crop="wide" />
      <MediaPickerField name="gallery" type="image" multiple label="ގެލަރީ" value={images} onChange={(value) => setImages(value as MediaItem[])} />
      <Submit />
    </Form>
  )
}
```

Send the current media from the controller as `MediaResource`s:

```php
use Javaabu\Mediapicker\Http\Resources\MediaResource;

'featured' => ($media = $post->getFirstAttachmentMedia('featured_image')) ? MediaResource::make($media)->resolve() : null,
'gallery' => MediaResource::collection($post->getAttachmentMedia('gallery'))->resolve(),
```

| Prop | | |
| --- | --- | --- |
| `name`, `label`, `help`, `required`, `disabled` | | As other @javaabu/forms fields; errors on `name` and `name.*` show under it |
| `value`, `onChange` | | One `MediaItem` (or null), or a list with `multiple` |
| `multiple`, `type` | | As in Blade |
| `crop` | | A crop preset: new images are cropped to it before they're uploaded |
| `url`, `types`, `accept`, `strings` | From `MediapickerProvider` | |
| `upload` | `true` | |

`MediaPicker` is the same without the form group, and `MediaLibraryDialog` is the dialog alone (`open`, `onOpenChange`, `onChoose`).

# Stencil

```php
use Javaabu\Mediapicker\Stencil\MediaPickerField;

MediaPickerField::make('featured_image')->mediaType('image')->required()->crop('wide'),
MediaPickerField::make('gallery')->mediaType('image')->multiple(),
MediaPickerField::make('files')->collection('documents')->mediaType('document')->multiple()->library('admin.media'),
```

It reads the record's attachments, validates the ids with `LibraryMedia`, and saves them with `updateAttachmentMedia()` after the record is saved. Blade forms render `<x-mediapicker::picker>`; React forms render `StencilMediaPicker` (register it as the `mediapicker` type).

| Method | |
| --- | --- |
| `collection($name)` | The attachment collection; default: the field name |
| `mediaType($types)` | Only these file types |
| `multiple()` | Several, in order |
| `crop($preset)` | Crop new images before uploading (React) |
| `library($route_name)` | Pick from this library |
| `upload(false)` | No uploads from the dialog |

# Validating and saving

Always validate picked ids with `LibraryMedia`: it only accepts media the user may see, of the given types. Without it, anyone could attach any file by its id.

```php
use Javaabu\Mediapicker\Rules\LibraryMedia;

$request->validate([
    'featured_image' => ['required', new LibraryMedia(type: 'image')],
    'gallery' => ['nullable', 'array', 'max:20'],
    'gallery.*' => [new LibraryMedia(type: 'image')],
]);

$post->update($request->safe()->except(['featured_image', 'gallery']));

$post->syncAttachmentsFromRequest('featured_image');
$post->syncAttachmentsFromRequest('gallery');
$post->syncAttachmentsFromRequest('files', key: 'attachments');
```

`syncAttachmentsFromRequest($collection, $key = $collection)` leaves the collection alone when the request doesn't have the field, clears it for an empty value, and otherwise makes it hold exactly those media, in order.
