<?php

namespace Javaabu\Mediapicker\Tests\Http;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Javaabu\Mediapicker\Models\Media;
use Javaabu\Mediapicker\Tests\TestSupport\Models\Admin;
use Javaabu\Mediapicker\Tests\TestSupport\Models\User;

trait InteractsWithLibrary
{
    use RefreshDatabase;

    /** Every media permission */
    protected const array ALL = [
        'view_media',
        'view_others_media',
        'edit_media',
        'edit_others_media',
        'delete_media',
        'delete_others_media',
    ];

    protected function setUpLibrary(): void
    {
        $this->artisan('permissions:sync');
    }

    protected function defineRoutes($router): void
    {
        $router->get('/login', fn () => 'login')->name('login');

        $router->middleware('web')->group(function ($router) {
            $router->mediapicker();
            $router->mediapicker(['guard' => 'web_admin', 'prefix' => 'admin/media', 'name' => 'admin.media']);
        });
    }

    /**
     * @param  array<int, string>  $permissions
     * @param  array<string, mixed>  $attributes
     */
    protected function userWith(array $permissions, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);

        if ($permissions) {
            $user->givePermissionTo($permissions);
        }

        return $user;
    }

    /**
     * @param  array<int, string>  $permissions
     */
    protected function adminWith(array $permissions): Admin
    {
        $admin = Admin::factory()->create();

        if ($permissions) {
            $admin->givePermissionTo($permissions);
        }

        return $admin;
    }

    /**
     * Upload a test file to an owner's library
     *
     * @param  array<string, mixed>  $custom_properties
     */
    protected function libraryMedia(Model $owner, string $file = 'test.jpg', ?string $name = null, array $custom_properties = []): Media
    {
        /** @var Media $media */
        $media = $owner->addMedia($this->getTestFilesDirectory($file))
            ->preservingOriginal()
            ->usingName($name ?? pathinfo($file, PATHINFO_FILENAME))
            ->withCustomProperties($custom_properties)
            ->toMediaCollection('mediapicker');

        return $media;
    }

    protected function fakeUpload(string $file = 'test.jpg', ?string $name = null): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name ?? $file,
            file_get_contents($this->getTestFilesDirectory($file)),
        );
    }
}
