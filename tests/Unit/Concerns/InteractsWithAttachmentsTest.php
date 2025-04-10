<?php

namespace Javaabu\Mediapicker\Tests\Unit\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Javaabu\Mediapicker\AttachmentCollections\MediaAdder;
use Javaabu\Mediapicker\AttachmentCollections\MediaAdderFactory;
use Javaabu\Mediapicker\Models\Attachment;
use Javaabu\Mediapicker\Models\Media;
use Javaabu\Mediapicker\Tests\TestCase;
use Javaabu\Mediapicker\Tests\TestSupport\Models\Post;
use PHPUnit\Framework\Attributes\Test;

class InteractsWithAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_the_attachment_when_the_model_is_deleted(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $attachment = $this->getAttachment($post);

        $this->assertDatabaseHas('attachments', [
            'model_type' => 'post',
            'model_id' => $post->id,
        ]);

        $this->assertDatabaseHas('posts', [
            'id' => $post->id
        ]);

        $post->delete();

        $this->assertDatabaseMissing('posts', [
            'id' => $post->id
        ]);

        $this->assertDatabaseMissing('attachments', [
            'model_type' => 'post',
            'model_id' => $post->id,
        ]);
    }

    #[Test]
    public function it_can_get_the_attachment_model(): void
    {
        $post = new Post();

        $this->assertEquals(Attachment::class, $post->getAttachmentModel());
    }

    #[Test]
    public function it_can_query_model_attachments(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $attachment = $this->getAttachment($post);

        $this->assertEquals($post->attachments()->count(), 1);
    }

    #[Test]
    public function it_can_add_an_attachment(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $media = $this->getMedia();

        $post->addAttachment($media)
            ->toAttachmentCollection();

        $this->assertDatabaseHas('attachments', [
            'model_type' => 'post',
            'model_id' => $post->id,
            'media_id' => $media->id,
        ]);
    }

    #[Test]
    public function it_can_add_attachment_from_a_request(): void
    {
        $this->withoutExceptionHandling();

        $model = $this->getModel();
        $media = $this->getMedia();


        Route::post('/add-attachment', function () {
            /** @var Post $model */
            $model = Post::first();

            $model->addAttachmentFromRequest('media')
                ->toAttachmentCollection();

            return true;
        });

        $this->assertDatabaseEmpty('attachments');

        $this->post('/add-attachment', [
            'media' => $media->getKey(),
        ])->assertSuccessful();

        $this->assertDatabaseHas('attachments', [
            'media_id' => $media->getKey(),
            'model_id' => $model->getKey(),
            'model_type' => 'post',
            'order_column' => 1,
            'collection_name' => 'default'
        ]);
    }

    #[Test]
    public function it_can_add_multiple_attachments_from_a_request(): void
    {
        $this->withoutExceptionHandling();

        $model = $this->getModel();
        $media_1 = $this->getMedia();
        $media_2 = $this->getMedia();


        Route::post('/add-attachments', function () {
            /** @var Post $model */
            $model = Post::first();

            $model->addMultipleAttachmentsFromRequest(['media_1', 'media_2'])
                ->each(function (MediaAdder $adder) {
                    $adder->toAttachmentCollection();
                });

            return true;
        });

        $this->assertDatabaseEmpty('attachments');

        $this->post('/add-attachments', [
            'media_1' => $media_1->getKey(),
            'media_2' => $media_2->getKey()
        ])->assertSuccessful();

        $this->assertDatabaseHas('attachments', [
            'media_id' => $media_1->getKey(),
            'model_id' => $model->getKey(),
            'model_type' => 'post',
            'order_column' => 1,
            'collection_name' => 'default'
        ]);

        $this->assertDatabaseHas('attachments', [
            'media_id' => $media_2->getKey(),
            'model_id' => $model->getKey(),
            'model_type' => 'post',
            'order_column' => 2,
            'collection_name' => 'default'
        ]);
    }

    #[Test]
    public function it_can_check_if_a_model_has_attachments(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $media = $this->getMedia();

        $post->addAttachment($media)
            ->toAttachmentCollection();

        $this->assertTrue($post->hasAttachments());
        $this->assertFalse($post->hasAttachments('test'));
    }

    #[Test]
    public function it_can_get_model_attachments(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $media = $this->getMedia();

        $attachment = $post->addAttachment($media)
            ->toAttachmentCollection();

        $collection = $post->getAttachments();

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(1, $collection);
        $this->assertTrue($collection->contains($attachment));
    }

    #[Test]
    public function it_can_get_attachment_media(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $media = $this->getMedia();

        $attachment = $post->addAttachment($media)
            ->toAttachmentCollection();

        $collection = $post->getAttachmentMedia();

        $this->assertInstanceOf(Collection::class, $collection);
        $this->assertCount(1, $collection);

        $this->assertInstanceOf(Media::class, $collection->first());
        $this->assertEquals($media->getKey(), $collection->first()->getKey());
    }

    #[Test]
    public function it_can_get_first_attachment(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $media = $this->getMedia();

        $attachment = $post->addAttachment($media)
            ->toAttachmentCollection();

        $first_attachment = $post->getFirstAttachment();

        $this->assertInstanceOf(Attachment::class, $first_attachment);
        $this->assertEquals($first_attachment->getKey(), $first_attachment->getKey());
    }

    #[Test]
    public function it_can_get_first_attachment_media(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $media = $this->getMedia();

        $attachment = $post->addAttachment($media)
            ->toAttachmentCollection();

        $first_media = $post->getFirstAttachmentMedia();

        $this->assertInstanceOf(Media::class, $first_media);
        $this->assertEquals($media->getKey(), $first_media->getKey());
    }

}
