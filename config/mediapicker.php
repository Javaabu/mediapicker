<?php

use Javaabu\Mediapicker\Conversions\Jobs\PerformAttachmentConversionsJob;
use Javaabu\Mediapicker\FileRemover\AttachmentFileRemover;
use Javaabu\Mediapicker\Http\Controllers\MediaController;
use Javaabu\Mediapicker\Models\Attachment;
use Javaabu\Mediapicker\Models\Media;
use Javaabu\Mediapicker\Models\Observers\AttachmentObserver;
use Javaabu\Mediapicker\Models\Observers\MediaObserver;
use Javaabu\Mediapicker\Policies\MediaPolicy;

return [

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | The media model must extend Javaabu\Mediapicker\Models\Media. Set it as
    | spatie's media-library.media_model too. The attachment model must
    | implement Javaabu\Mediapicker\Contracts\Attachment.
    |
    */

    'media_model' => Media::class,

    'attachment_model' => Attachment::class,

    /*
    |--------------------------------------------------------------------------
    | Library collection
    |--------------------------------------------------------------------------
    |
    | The media collection on the owner (the user model) that holds its
    | library uploads.
    |
    */

    'collection_name' => 'mediapicker',

    /*
    |--------------------------------------------------------------------------
    | File types
    |--------------------------------------------------------------------------
    |
    | What the library accepts, by type. Pickers and uploads can be limited to
    | some types (`type="image"`). Each type has its mime types, a Lucide icon
    | and an optional max_size in KB. SVG isn't in the defaults: an uploaded
    | SVG can run scripts when it's opened from your domain.
    |
    */

    'file_types' => [
        'image' => [
            'mime_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'],
            'icon' => 'image',
            'max_size' => null,
        ],
        'video' => [
            'mime_types' => ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'],
            'icon' => 'film',
            'max_size' => null,
        ],
        'audio' => [
            'mime_types' => ['audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/x-wav', 'audio/ogg', 'audio/mp4', 'audio/aac', 'audio/webm'],
            'icon' => 'music',
            'max_size' => null,
        ],
        'document' => [
            'mime_types' => [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv',
            ],
            'icon' => 'file-text',
            'max_size' => null,
        ],
        'archive' => [
            'mime_types' => ['application/zip', 'application/x-zip-compressed'],
            'icon' => 'file-archive',
            'max_size' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Upload size
    |--------------------------------------------------------------------------
    |
    | The largest upload in KB, for types without their own max_size. Null
    | uses spatie's media-library.max_file_size.
    |
    */

    'max_upload_size' => null,

    /*
    |--------------------------------------------------------------------------
    | Crop presets
    |--------------------------------------------------------------------------
    |
    | The aspect ratios images can be cropped to (width / height; null crops
    | freely). Crops are saved per preset on the media and never change the
    | original. Attachment conversions use one with ->usingCrop('wide').
    |
    */

    'crop_presets' => [
        'square' => ['label' => 'Square', 'aspect' => 1],
        'wide' => ['label' => 'Wide (16:9)', 'aspect' => 16 / 9],
        'landscape' => ['label' => 'Landscape (4:3)', 'aspect' => 4 / 3],
        'portrait' => ['label' => 'Portrait (3:4)', 'aspect' => 3 / 4],
        'free' => ['label' => 'Free', 'aspect' => null],
    ],

    /*
    |--------------------------------------------------------------------------
    | Block editor
    |--------------------------------------------------------------------------
    |
    | Register the image, gallery, file and mediaVideo blocks with
    | javaabu/block-editor. The image block replaces the core URL image.
    |
    */

    'blocks' => true,

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | views: `blade` (javaabu/forms) or `inertia` (React pages through
    | javaabu/manifest). The Blade pages extend `layout` and fill
    | `layout_section`. per_page is the library's page size.
    |
    */

    'views' => 'blade',

    'layout' => 'layouts.app',

    'layout_section' => 'content',

    'per_page' => 24,

    /*
    |--------------------------------------------------------------------------
    | Controllers
    |--------------------------------------------------------------------------
    |
    | Extend the package controller to change how a page works. The
    | `controller` option of Route::mediapicker() wins over this.
    |
    */

    'media_controller' => MediaController::class,

    /*
    |--------------------------------------------------------------------------
    | Permissions and policy
    |--------------------------------------------------------------------------
    |
    | The media permissions are registered with javaabu/permissions (run
    | `php artisan permissions:sync`). The policy can be swapped here, in
    | config('permissions.policies') or with Gate::policy().
    |
    */

    'register_permissions' => true,

    'policy' => MediaPolicy::class,

    /*
    |--------------------------------------------------------------------------
    | Observers, jobs and file removal
    |--------------------------------------------------------------------------
    */

    'attachment_observer' => AttachmentObserver::class,

    'media_observer' => MediaObserver::class,

    'jobs' => [
        'perform_conversions' => PerformAttachmentConversionsJob::class,
    ],

    'file_remover_class' => AttachmentFileRemover::class,
];
