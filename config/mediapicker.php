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
];
