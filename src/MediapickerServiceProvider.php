<?php

namespace Javaabu\Mediapicker;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Javaabu\Manifest\Concerns\RegistersAssets;
use Javaabu\Manifest\Package;
use Javaabu\Mediapicker\Assets\Assets;
use Javaabu\Mediapicker\AttachmentCollections\AttachmentRepository;
use Javaabu\Mediapicker\Commands\AttachmentsCleanCommand;
use Javaabu\Mediapicker\Commands\AttachmentsClearCommand;
use Javaabu\Mediapicker\Commands\AttachmentsRegenerateCommand;
use Javaabu\Mediapicker\Events\MediaCropped;
use Javaabu\Mediapicker\Listeners\RegenerateCroppedConversions;
use Javaabu\Mediapicker\Models\Attachment;
use Javaabu\Mediapicker\Models\Observers\AttachmentObserver;
use Javaabu\Mediapicker\Models\Observers\MediaObserver;
use Javaabu\Mediapicker\Policies\MediaPolicy;
use Javaabu\Mediapicker\Routing\MediapickerRoutes;
use Javaabu\Permissions\Permissions;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediapickerServiceProvider extends ServiceProvider
{
    use RegistersAssets;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/mediapicker.php', 'mediapicker');

        $this->registerAssets(Assets::PACKAGE, __DIR__ . '/../resources', fn (Package $package) => Assets::register($package));

        $this->app->singleton(AttachmentRepository::class, function () {
            $attachment_class = $this->getAttachmentClass();

            return new AttachmentRepository(new $attachment_class);
        });

        $this->commands([
            AttachmentsRegenerateCommand::class,
            AttachmentsClearCommand::class,
            AttachmentsCleanCommand::class,
        ]);
    }

    public function boot(): void
    {
        $this->loadJsonTranslationsFrom(__DIR__ . '/../lang');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'mediapicker');

        $this->registerObservers();
        $this->registerListeners();
        $this->registerComponents();
        $this->registerRouteMacros();
        $this->registerPermissions();

        if ($this->app->runningInConsole()) {
            $this->registerMigrations();
            $this->registerPublishing();
        }
    }

    protected function registerObservers(): void
    {
        $attachment_class = $this->getAttachmentClass();
        $attachment_observer = config('mediapicker.attachment_observer', AttachmentObserver::class);

        $attachment_class::observe(new $attachment_observer);

        $media_class = $this->getMediaClass();
        $media_observer = config('mediapicker.media_observer', MediaObserver::class);

        $media_class::observe(new $media_observer);
    }

    protected function registerListeners(): void
    {
        Event::listen(MediaCropped::class, RegenerateCroppedConversions::class);
    }

    /**
     * <x-mediapicker::image>, and the picker field
     */
    protected function registerComponents(): void
    {
        Blade::componentNamespace('Javaabu\\Mediapicker\\Views\\Components', 'mediapicker');
    }

    protected function registerRouteMacros(): void
    {
        Route::macro('mediapicker', function (array $options = []) {
            MediapickerRoutes::register($this, $options);
        });
    }

    /**
     * view_media, view_others_media, edit_media, edit_others_media, delete_media and
     * delete_others_media, and the default policy, which the app can override
     */
    protected function registerPermissions(): void
    {
        if (! config('mediapicker.register_permissions', true)) {
            return;
        }

        Permissions::register('media', [
            'view_media' => 'View the media library',
            'view_others_media' => 'View media uploaded by others',
            'edit_media' => 'Upload media and edit your own',
            'edit_others_media' => 'Edit media uploaded by others',
            'delete_media' => 'Delete your own media',
            'delete_others_media' => 'Delete media uploaded by others',
        ], label: 'Media');

        Permissions::policy(Mediapicker::mediaModel(), config('mediapicker.policy') ?: MediaPolicy::class);
    }

    protected function registerMigrations(): void
    {
        if (Mediapicker::$runs_migrations) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    protected function registerPublishing(): void
    {
        $this->publishes([
            __DIR__ . '/../config/mediapicker.php' => config_path('mediapicker.php'),
        ], 'mediapicker-config');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'mediapicker-migrations');

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/mediapicker'),
        ], 'mediapicker-views');

        $this->publishes([
            __DIR__ . '/../lang' => lang_path('vendor/mediapicker'),
        ], 'mediapicker-translations');
    }

    protected function getMediaClass(): string
    {
        return config('media-library.media_model', Media::class);
    }

    protected function getAttachmentClass(): string
    {
        return config('mediapicker.attachment_model', Attachment::class);
    }
}
