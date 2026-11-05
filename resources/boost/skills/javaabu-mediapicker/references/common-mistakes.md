# Common mistakes

| Don't | Do |
| --- | --- |
| `$post->addMedia($request->file('image'))` for an image picked in a form | Attach library media: `$post->syncAttachmentsFromRequest('featured_image')` |
| Save picked ids without validating them | `new LibraryMedia(type: 'image')` on the field (and `field.*` for several) |
| `$post->getFirstAttachmentUrl('featured_image', 'card')` in a page | `<x-mediapicker::image>` or `$attachment->getAvailableUrl('card')`, which works before a queued conversion is made |
| Crop by saving a new file | `Cropper::crop()` and `->usingCrop()` on the conversion |
| `use Javaabu\Mediapicker\Stencil\MediaPicker` | The class is `MediaPickerField` (`MediaPicker` would clash with `Mediapicker`) |
| Hard-code `/admin/media/...` | `Mediapicker::url('edit', $media)` or `$media->libraryUrl('edit')` |
| `Route::resource('media', ...)` or `Mediapicker::registerRoutes()` | `Route::mediapicker([...])` |
| Check `$user->can('edit_media')` for a file | `$user->can('update', $media)`: ownership and the user's library matter |
| `Media::query()->where('model_id', $user->id)` to list a user's files | `Media::visibleTo($user)` (or `ownedBy($user)`) |
| javaabu/helpers' `AllowedMimeTypes` | `Javaabu\Mediapicker\Support\FileTypes` and `mediapicker.file_types` |
| `$media->url('edit')` | `$media->libraryUrl('edit')` |
| Forget the worker | Attachment conversions are queued: run `php artisan queue:work` |
