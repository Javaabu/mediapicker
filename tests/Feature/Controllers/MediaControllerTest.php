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


}
