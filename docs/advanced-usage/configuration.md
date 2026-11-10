---
title: Configuration
sidebar_position: 1
---

Publish the config with `php artisan vendor:publish --tag=mediapicker-config`. Everything has a default; the common case needs no changes.

| Key | Default | |
| --- | --- | --- |
| `media_model` | `null` | The media model (extends `Models\Media`). Null: `Models\Translatable\Media` when javaabu/translatable is on, else `Models\Media`. Set spatie's `media-library.media_model` to the same class |
| `attachment_model` | `Models\Attachment` | Implements `Contracts\Attachment` |
| `collection_name` | `mediapicker` | The owners' library collection |
| `file_types` | image, video, audio, document, archive | Per type: `mime_types`, a Lucide `icon`, and `max_size` in KB (null: `max_upload_size`) |
| `max_upload_size` | `null` | KB, for types without their own. Null: spatie's `max_file_size` |
| `crop_presets` | square, wide, landscape, portrait, free | Per preset: `label` and `aspect` (width / height, or null for free) |
| `blocks` | `true` | Register the javaabu/block-editor blocks |
| `views` | `blade` | `blade` or `inertia` |
| `layout` | `layouts.app` | The layout the Blade pages extend |
| `layout_section` | `content` | The section they fill |
| `per_page` | `24` | The library's page size (requests may ask for up to 100) |
| `media_controller` | `Http\Controllers\MediaController` | The library's controller |
| `register_permissions` | `true` | Register the media permissions and policy with javaabu/permissions |
| `policy` | `Policies\MediaPolicy` | The media policy |
| `attachment_observer` | `Models\Observers\AttachmentObserver` | Orders new attachments and makes their conversions |
| `media_observer` | `Models\Observers\MediaObserver` | Removes attachment conversion files before media is deleted |
| `jobs.perform_conversions` | `Conversions\Jobs\PerformAttachmentConversionsJob` | The job for queued conversions (extend the package's) |
| `file_remover_class` | `FileRemover\AttachmentFileRemover` | How attachment conversion files are removed |

Queueing follows spatie's config: `queue_conversions_by_default`, `queue_connection_name`, `queue_name` and `queue_conversions_after_database_commit`. Responsive widths use spatie's `responsive_images.width_calculator`, and conversions spatie's `image_driver`.
