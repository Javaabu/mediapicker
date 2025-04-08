<?php

namespace Javaabu\Mediapicker;

use Illuminate\Support\ServiceProvider;
use Javaabu\Mediapicker\AttachmentCollections\AttachmentRepository;
use Javaabu\Mediapicker\Commands\AttachmentsCleanCommand;
use Javaabu\Mediapicker\Commands\AttachmentsClearCommand;
use Javaabu\Mediapicker\Commands\AttachmentsRegenerateCommand;

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
        //$attachmentClass::observe(new AttachmentObserver());
        // TODO
    }

    protected function getAttachmentClass(): string
    {
        return $this->app['config']['mediapicker.attachment_model'];
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
        // TODO
/*
        $this->app->bind('command.attachments:regenerate', AttachmentsRegenerateCommand::class);
        $this->app->bind('command.attachments:clear', AttachmentsClearCommand::class);
        $this->app->bind('command.attachments:clean', AttachmentsCleanCommand::class);

        $this->commands([
            'command.attachments:regenerate',
            'command.attachments:clear',
            'command.attachments:clean',
        ]);*/
    }
}
