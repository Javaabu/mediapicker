<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Media Model
    |--------------------------------------------------------------------------
    |
    | Which model to use for media
    |
    */

    'media_model' => \Javaabu\Mediapicker\Models\Media::class,

    /*
    |--------------------------------------------------------------------------
    | Attachment Model
    |--------------------------------------------------------------------------
    |
    | Which model to use for Attachments.
    | It should implement the Javaabu\Mediapicker\Contracts\Attachment interface
    | and extend Illuminate\Database\Eloquent\Model.
    |
    */

    'attachment_model' => \Javaabu\Mediapicker\Models\Attachment::class,

    /*
    |--------------------------------------------------------------------------
    | Attachment Observer
    |--------------------------------------------------------------------------
    |
    | Class used to observe changes to attachments
    |
    */

    'attachment_observer' => \Javaabu\Mediapicker\Models\Observers\AttachmentObserver::class,

    /*
    |--------------------------------------------------------------------------
    | Media Observer
    |--------------------------------------------------------------------------
    |
    | Class used to observe changes to medias
    |
    */

    'media_observer' => \Javaabu\Mediapicker\Models\Observers\MediaObserver::class,

    /*
     * Here you can override the class names of the jobs used by this package. Make sure
     * your custom jobs extend the ones provided by the package.
     */
    'jobs' => [
        'perform_conversions' => \Javaabu\Mediapicker\Conversions\Jobs\PerformAttachmentConversionsJob::class,
    ],

    /*
     * The class that contains the strategy for determining how to remove files.
     */
    'file_remover_class' => \Javaabu\Mediapicker\FileRemover\AttachmentFileRemover::class,
];
