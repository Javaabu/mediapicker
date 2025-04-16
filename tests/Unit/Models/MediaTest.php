<?php

namespace Javaabu\Mediapicker\Tests\Unit\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Javaabu\Mediapicker\Models\Media;
use Javaabu\Mediapicker\Tests\TestCase;
use Javaabu\Mediapicker\Tests\TestSupport\Models\User;
use PHPUnit\Framework\Attributes\Test;

class MediaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_set_media_description(): void
    {
        $media = new Media();
        $media->description = 'test description';

        $this->assertEquals('test description', $media->description);
    }

    #[Test]
    public function it_can_set_the_image_dimensions(): void
    {
        $media = new Media();
        $media->width = 200;
        $media->height = 100;

        $this->assertEquals(200, $media->width);
        $this->assertEquals(100, $media->height);
    }

    #[Test]
    public function it_can_get_the_media_file_type(): void
    {
        $media_1 = $this->getMedia();

        $media = new Media();
        $media->forceFill($media_1->attributesToArray());

        $this->assertEquals('image', $media->file_type);
    }

    #[Test]
    public function it_can_get_the_media_icon(): void
    {
        $media_1 = $this->getMedia();

        $media = new Media();
        $media->forceFill($media_1->attributesToArray());

        $this->assertEquals('fa-regular fa-file-image', $media->getIcon());
    }

    #[Test]
    public function it_can_filter_media_by_file_type(): void
    {
        $media_1 = $this->getMedia($this->getTestJpg());
        $media_2 = $this->getMedia($this->getTestPdf());

        $media = Media::hasFileType('pdf')->get();

        $this->assertCount(1, $media);
        $this->assertEquals($media_2->id, $media->first()->id);
    }

    #[Test]
    public function it_can_search_media(): void
    {
        $media_1 = $this->getMedia($this->getTestJpg());
        $media_1 = Media::find($media_1->id);
        $media_1->description = 'test description';
        $media_1->save();

        $media_2 = $this->getMedia($this->getTestPdf());

        $media = Media::search('test description')->get();

        $this->assertCount(1, $media);
        $this->assertEquals($media_1->id, $media->first()->id);
    }

    #[Test]
    public function it_can_filter_user_visible_media(): void
    {
        $user = $this->getUserWithMedia(name: 'Ahmed');
        $media_1 = $user->getFirstMedia('mediapicker');

        $other_user = $this->getUserWithMedia(name: 'Mohamed');
        $media_2 = $other_user->getFirstMedia('mediapicker');

        Gate::define('view_media', function (User $user) {
            return true;
        });

        Gate::define('view_others_media', function (User $user) {
            return $user->name == 'Mohamed';
        });

        Auth::login($user);

        $media = Media::userVisible()->get();

        $this->assertCount(1, $media);
        $this->assertEquals($media_1->id, $media->first()->id);

        Auth::login($other_user);

        $media = Media::userVisible()->get();

        $this->assertCount(2, $media);
        $this->assertContains($media_1->id, $media->pluck('id')->all());
        $this->assertContains($media_2->id, $media->pluck('id')->all());
    }
}
