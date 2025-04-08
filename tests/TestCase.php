<?php

namespace Javaabu\Mediapicker\Tests;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Javaabu\Helpers\HelpersServiceProvider;
use Javaabu\Mediapicker\Tests\TestSupport\Models\Post;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Javaabu\Mediapicker\MediapickerServiceProvider;
use Javaabu\Mediapicker\Tests\TestSupport\Providers\TestServiceProvider;
use Javaabu\Activitylog\ActivitylogServiceProvider;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

abstract class TestCase extends BaseTestCase
{

    public function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('app.key', 'base64:yWa/ByhLC/GUvfToOuaPD7zDwB64qkc/QkaQOrT5IpE=');

        $this->app['config']->set('session.serialization', 'php');

        View::addLocation(__DIR__ . '/TestSupport/views');

        $this->setUpTempTestFiles();

        if (empty(glob($this->app->databasePath('migrations/*_create_media_table.php')))) {
            Artisan::call('vendor:publish', [
                '--provider' => 'Spatie\\MediaLibrary\\MediaLibraryServiceProvider',
                '--tag' => 'medialibrary-migrations',
            ]);

            Artisan::call('migrate');
        }

    }

    protected function getPackageProviders($app)
    {
        return [
            MediapickerServiceProvider::class,
            MediaLibraryServiceProvider::class,
            TestServiceProvider::class,
            HelpersServiceProvider::class,
            ActivitylogServiceProvider::class,
        ];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    public function getEnvironmentSetUp($app)
    {
        $this->initializeDirectory($this->getTempDirectory());

        config()->set('filesystems.disks.public', [
            'driver' => 'local',
            'root' => $this->getMediaDirectory(),
            'url' => '/media',
        ]);

        config()->set('filesystems.disks.secondMediaDisk', [
            'driver' => 'local',
            'root' => $this->getTempDirectory('media2'),
            'url' => '/media2',
        ]);

        $app->bind('path.public', fn () => $this->getTempDirectory());

        $this->setUpMorphMap();
    }

    protected function setUpTempTestFiles()
    {
        $this->initializeDirectory($this->getTestFilesDirectory());
        File::copyDirectory(__DIR__.'/TestSupport/testfiles', $this->getTestFilesDirectory());
    }

    protected function initializeDirectory($directory)
    {
        if (File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }

        File::makeDirectory($directory);
    }

    public function getTestsPath($suffix = ''): string
    {
        if ($suffix !== '') {
            $suffix = "/{$suffix}";
        }

        return __DIR__.$suffix;
    }

    public function getTempDirectory(string $suffix = ''): string
    {
        return __DIR__.'/TestSupport/temp'.($suffix == '' ? '' : '/'.$suffix);
    }

    public function getMediaDirectory(string $suffix = ''): string
    {
        return $this->getTempDirectory().'/media'.($suffix == '' ? '' : '/'.$suffix);
    }

    public function getTestFilesDirectory(string $suffix = ''): string
    {
        return $this->getTempDirectory().'/testfiles'.($suffix == '' ? '' : '/'.$suffix);
    }

    public function getTestJpg(): string
    {
        return $this->getTestFilesDirectory('test.jpg');
    }

    public function getSmallTestJpg(): string
    {
        return $this->getTestFilesDirectory('smallTest.jpg');
    }

    public function getTestPng(): string
    {
        return $this->getTestFilesDirectory('test.png');
    }

    public function getUppercaseExtensionTestPng(): string
    {
        return $this->getTestFilesDirectory('uppercaseExtensionTest.PNG');
    }

    public function getTestTiff(): string
    {
        return $this->getTestFilesDirectory('test.tiff');
    }

    public function getTestWebm(): string
    {
        return $this->getTestFilesDirectory('test.webm');
    }

    public function getTestPdf(): string
    {
        return $this->getTestFilesDirectory('test.pdf');
    }

    public function getTestSvg(): string
    {
        return $this->getTestFilesDirectory('test.svg');
    }

    public function getTestWebp(): string
    {
        return $this->getTestFilesDirectory('test.webp');
    }

    public function getTestAvif(): string
    {
        return $this->getTestFilesDirectory('test.avif');
    }

    public function getTestHeic(): string
    {
        return $this->getTestFilesDirectory('test.heic');
    }

    public function getTestMp4(): string
    {
        return $this->getTestFilesDirectory('test.mp4');
    }

    public function getTestImageWithoutExtension(): string
    {
        return $this->getTestFilesDirectory('image');
    }

    public function getTestImageEndingWithUnderscore(): string
    {
        return $this->getTestFilesDirectory('test_.jpg');
    }

    public function getAntaresThumbJpgWithAccent(): string
    {
        return $this->getTestFilesDirectory('antarèsthumb.jpg');
    }

    private function setUpMorphMap(): void
    {
        Relation::morphMap([
            'post' => Post::class,
        ]);
    }
}
