<?php

use Javaabu\Mediapicker\Conversions\Jobs\PerformAttachmentConversionsJob;
use Javaabu\Mediapicker\FileRemover\AttachmentFileRemover;
use Javaabu\Mediapicker\Http\Controllers\MediaController;
use Javaabu\Mediapicker\Models\Attachment;
use Javaabu\Mediapicker\Models\Media;
use Javaabu\Mediapicker\Models\Observers\AttachmentObserver;
use Javaabu\Mediapicker\Models\Observers\MediaObserver;

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
    | Observers, jobs and file removal
    |--------------------------------------------------------------------------
    */

    'attachment_observer' => AttachmentObserver::class,

    'media_observer' => MediaObserver::class,

    'jobs' => [
        'perform_conversions' => PerformAttachmentConversionsJob::class,
    ],

    'file_remover_class' => AttachmentFileRemover::class,

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    */

    'framework' => 'material-admin-26',

    'default_layout' => 'layouts.admin',

    'picker_layout' => 'layouts.admin-blank',

    'scripts_stack' => 'scripts',
];
