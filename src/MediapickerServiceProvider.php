<?php

namespace Javaabu\Mediapicker;

use Illuminate\Support\ServiceProvider;
use Javaabu\Mediapicker\AttachmentCollections\AttachmentRepository;
use Javaabu\Mediapicker\Commands\AttachmentsCleanCommand;
use Javaabu\Mediapicker\Commands\AttachmentsClearCommand;
use Javaabu\Mediapicker\Commands\AttachmentsRegenerateCommand;
use Javaabu\Mediapicker\Models\Attachment;
use Javaabu\Mediapicker\Models\Observers\AttachmentObserver;
use Javaabu\Mediapicker\Models\Observers\MediaObserver;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediapickerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        // declare publishes
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/mediapicker.php' => config_path('mediapicker.php'),
            ], 'mediapicker-config');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'mediapicker-migrations');
        }

        $attachmentClass = $this->getAttachmentClass();
        $attachmentObserverClass = config('mediapicker.attachment_observer', AttachmentObserver::class);

        $attachmentClass::observe(new $attachmentObserverClass);

        $mediaClass = $this->getMediaClass();
        $mediaObserverClass = config('mediapicker.media_observer', MediaObserver::class);

        $mediaClass::observe(new $mediaObserverClass);
    }

    protected function getMediaClass(): string
    {
        return config('media-library.media_model', Media::class);
    }

    protected function getAttachmentClass(): string
    {
        return config('mediapicker.attachment_model', Attachment::class);
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        // merge package config with user defined config
        $this->mergeConfigFrom(__DIR__ . '/../config/mediapicker.php', 'mediapicker');

        $this->app->singleton(AttachmentRepository::class, function () {
            $attachmentClass = $this->getAttachmentClass();
            return new AttachmentRepository(new $attachmentClass);
        });

        $this->app->bind('command.mediapicker:regenerate', AttachmentsRegenerateCommand::class);
        $this->app->bind('command.mediapicker:clear', AttachmentsClearCommand::class);
        $this->app->bind('command.mediapicker:clean', AttachmentsCleanCommand::class);

        $this->commands([
            'command.mediapicker:regenerate',
            'command.mediapicker:clear',
            'command.mediapicker:clean',
        ]);
    }
}
