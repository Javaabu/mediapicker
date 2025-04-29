<?php

namespace Javaabu\Mediapicker\Tests\Feature\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\Tests\TestCase;
use Javaabu\Mediapicker\Tests\TestSupport\Models\User;
use PHPUnit\Framework\Attributes\Test;

class MediaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        Mediapicker::registerRoutes(middleware: ['web']);
    }

    #[Test]
    public function it_can_upload_a_file_to_the_media_library(): void
    {
        $this->withoutExceptionHandling();

        Gate::define('edit_media', function (User $user) {
            return true;
        });

        $user = User::factory()->create();

        $this->actingAs($user);

        $file = UploadedFile::fake()->createWithContent(
            'test.jpg',
            file_get_contents($this->getTestJpg()),
        );

        $this->assertDatabaseEmpty('media');

        $this->post('/media', [
            'file' => $file,
        ])
            ->assertRedirect()
            ->assertSessionMissing('errors');


        $this->assertDatabaseHas('media', [
            'model_id' => $user->id,
            'model_type' => $user->getMorphClass(),
            'collection_name' => 'mediapicker',
            'file_name' => 'test.jpg',
            'custom_properties' => json_encode([
                'width' => 340,
                'height' => 280,
            ])
        ]);
    }

    #[Test]
    public function it_can_upload_a_file_to_the_media_library_and_return_json(): void
    {
        $this->withoutExceptionHandling();

        Gate::define('edit_media', function (User $user) {
            return true;
        });

        $user = User::factory()->create();

        $this->actingAs($user);

        $file = UploadedFile::fake()->createWithContent(
            'test.jpg',
            file_get_contents($this->getTestJpg()),
        );

        $this->assertDatabaseEmpty('media');

        $this->postJson('/media', [
            'file' => $file,
        ])
            ->assertSuccessful()
            ->assertJsonStructure([
                'success',
                'id',
                'uuid',
                'thumb',
                'large',
                'file_type',
                'icon',
                'name',
                'file_name',
                'url',
                'edit_url'
            ])
            ->assertJson([
                'success' => true,
                'file_name' => 'test.jpg',
                'file_type' => 'image',
            ]);

        $this->assertDatabaseHas('media', [
            'model_id' => $user->id,
            'model_type' => $user->getMorphClass(),
            'collection_name' => 'mediapicker',
            'file_name' => 'test.jpg',
            'custom_properties' => json_encode([
                'width' => 340,
                'height' => 280,
            ])
        ]);
    }

    #[Test]
    public function it_does_not_allow_viewing_media_index_page_for_unauthorized_users(): void
    {
        Gate::define('view_media', function (User $user) {
            return false;
        });

        $user = User::factory()->create();

        $this->actingAs($user);

        $media = $this->getMedia(user: $user);

        $this->get('/media')
            ->assertStatus(403)
            ->assertDontSee($media->name);
    }

    #[Test]
    public function it_does_not_show_other_users_media_to_unauthorized_users_on_the_index_page(): void
    {
        $this->withoutExceptionHandling();

        Gate::define('view_media', function (User $user) {
            return true;
        });

        Gate::define('view_others_media', function (User $user) {
            return false;
        });

        $user = User::factory()->create();

        $this->actingAs($user);

        $media = $this->getMedia(user: $user);

        $other_user = User::factory()->create();


        $other_file = $this->getTestImageEndingWithUnderscore();
        $other_media = $this->getMedia($other_file, user: $other_user);

        $this->get('/media')
            ->assertSuccessful()
            ->assertDontSee($other_media->name)
            ->assertSee($media->name);
    }

    #[Test]
    public function it_can_show_others_media_to_authorized_users_on_the_index_page(): void
    {
        $this->withoutExceptionHandling();

        Gate::define('view_media', function (User $user) {
            return true;
        });

        Gate::define('view_others_media', function (User $user) {
            return true;
        });

        $user = User::factory()->create();

        $this->actingAs($user);

        $media = $this->getMedia(user: $user);

        $other_user = User::factory()->create();


        $other_file = $this->getTestImageEndingWithUnderscore();
        $other_media = $this->getMedia($other_file, user: $other_user);

        $this->get('/media')
            ->assertSuccessful()
            ->assertSee($other_media->name)
            ->assertSee($media->name);
    }


}
