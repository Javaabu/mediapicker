<?php

namespace Javaabu\Mediapicker\Tests\Unit\AttachmentCollections;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Javaabu\Mediapicker\AttachmentCollections\MediaAdder;
use Javaabu\Mediapicker\AttachmentCollections\MediaAdderFactory;
use Javaabu\Mediapicker\Tests\TestCase;
use Javaabu\Mediapicker\Tests\TestSupport\Models\Post;
use PHPUnit\Framework\Attributes\Test;

class MediaAdderFactoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_a_media_adder_from_an_id(): void
    {
        $model = $this->getModel();
        $media = $this->getMedia();


        $media_adder = MediaAdderFactory::create($model, $media->getKey());

        $this->assertInstanceOf(MediaAdder::class, $media_adder);
    }

    #[Test]
    public function it_can_create_a_media_adder_from_a_media_object(): void
    {
        $model = $this->getModel();
        $media = $this->getMedia();


        $media_adder = MediaAdderFactory::create($model, $media);

        $this->assertInstanceOf(MediaAdder::class, $media_adder);
    }

    #[Test]
    public function it_can_create_a_media_adder_from_a_request(): void
    {
        $this->withoutExceptionHandling();

        $model = $this->getModel();
        $media = $this->getMedia();


        Route::post('/add-media', function () {
            $model = Post::first();

            $media_adder = MediaAdderFactory::createFromRequest($model, 'media');

            return true;
        });

        $this->post('/add-media', [
            'media' => $media->getKey(),
        ])->assertSuccessful();
    }

    #[Test]
    public function it_can_create_multiple_media_adders_from_a_request(): void
    {
        $this->withoutExceptionHandling();

        $model = $this->getModel();
        $media_1 = $this->getMedia();
        $media_2 = $this->getMedia();

        Route::post('/add-medias', function () {
            $model = Post::first();

            $media_adder = MediaAdderFactory::createFromRequest($model, 'media');

            return true;
        });

        $this->post('/add-medias', [
            'media' => [
                $media_1->getKey(),
                $media_2->getKey(),
            ]
        ])->assertSuccessful();
    }
}
