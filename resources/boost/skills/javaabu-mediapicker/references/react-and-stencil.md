# React and stencil pickers

## Inertia React

Share the picker config once and wrap the layout:

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return [...parent::share($request), 'mediapicker' => fn () => \Javaabu\Mediapicker\Mediapicker::pickerConfig()];
}
```

```tsx
import { MediapickerProvider } from "@javaabu/mediapicker"

<MediapickerProvider config={usePage().props.mediapicker}>{children}</MediapickerProvider>
```

`MediaPickerField` is controlled: bind `value` and `onChange` by hand (it isn't bound to `@javaabu/forms` state yet, Javaabu/react-forms#23). The value is `MediaItem` objects; it submits their ids as `name` or `name[]`.

```tsx
import { MediaPickerField, type MediaItem } from "@javaabu/mediapicker"

const [image, setImage] = useState<MediaItem | null>(props.featured)

<MediaPickerField name="featured_image" type="image" required crop="wide" value={image} onChange={(value) => setImage(value as MediaItem | null)} />
```

Send current media from the controller with `MediaResource`:

```php
'featured' => ($media = $post->getFirstAttachmentMedia('featured_image')) ? MediaResource::make($media)->resolve() : null,
'gallery' => MediaResource::collection($post->getAttachmentMedia('gallery'))->resolve(),
```

## Stencil

```php
use Javaabu\Mediapicker\Stencil\MediaPickerField;

MediaPickerField::make('featured_image')->mediaType('image')->required()->crop('wide'),
MediaPickerField::make('gallery')->mediaType('image')->multiple(),
```

It reads and saves the record's attachments (collection = field name, or `->collection()`) and validates with `LibraryMedia`. In React, register it once: `<StencilProvider components={{ mediapicker: StencilMediaPicker }}>`.

## Without a framework

```js
const chosen = await window.Mediapicker.open({ url: "/admin/media", type: "image", multiple: true })
```
