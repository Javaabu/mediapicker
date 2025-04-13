<?php

namespace Javaabu\Mediapicker\Tests\Unit\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Javaabu\Mediapicker\AttachmentCollections\Events\AttachmentCollectionHasBeenClearedEvent;
use Javaabu\Mediapicker\AttachmentCollections\Exceptions\AttachmentCannotBeDeleted;
use Javaabu\Mediapicker\AttachmentCollections\Exceptions\AttachmentCannotBeUpdated;
use Javaabu\Mediapicker\AttachmentCollections\MediaAdder;
use Javaabu\Mediapicker\AttachmentCollections\MediaAdderFactory;
use Javaabu\Mediapicker\Models\Attachment;
use Javaabu\Mediapicker\Models\Media;
use Javaabu\Mediapicker\Tests\TestCase;
use Javaabu\Mediapicker\Tests\TestSupport\Models\Post;
use PHPUnit\Framework\Attributes\Test;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\MediaCollections\MediaCollection;

class InteractsWithAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_deletes_the_attachment_when_the_model_is_deleted(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $this->getAttachment($post);

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

    #[Test]
    public function it_can_get_first_attachment_url(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $media = $this->getMedia();

        $attachment = $post->addAttachment($media)
            ->toAttachmentCollection();

        $url = $post->getFirstAttachmentUrl();

        $this->assertEquals('/media/' . $media->getKey() . '/test.jpg', $url);
    }

    #[Test]
    public function it_returns_empty_string_when_no_attachment_for_url(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $url = $post->getFirstAttachmentUrl();

        $this->assertEquals('', $url);
    }

    #[Test]
    public function it_returns_empty_string_when_no_attachment_for_temporary_url(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $url = $post->getFirstAttachmentTemporaryUrl(now()->addHour());

        $this->assertEquals('', $url);
    }

    #[Test]
    public function it_can_get_first_attachment_path(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $media = $this->getMedia();

        $attachment = $post->addAttachment($media)
            ->toAttachmentCollection();

        $path = $post->getFirstAttachmentPath();

        $this->assertEquals($this->getMediaDirectory($media->getKey() . '/test.jpg'), $path);
    }

    #[Test]
    public function it_returns_empty_string_when_no_attachment_for_path(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $path = $post->getFirstAttachmentPath();

        $this->assertEquals('', $path);
    }

    #[Test]
    public function it_can_update_attachments(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $attachment1 = $this->getAttachment($post);
        $attachment2 = $this->getAttachment($post);
        $attachment3 = $this->getAttachment($post);

        // Reorder and remove one attachment
        $updatedAttachments = $post->updateAttachments([
            ['id' => $attachment3->id],
            ['id' => $attachment1->id],
        ]);

        $this->assertCount(2, $updatedAttachments);
        $this->assertEquals($attachment3->id, $updatedAttachments[0]->id);
        $this->assertEquals(1, $updatedAttachments[0]->order_column);
        $this->assertEquals($attachment1->id, $updatedAttachments[1]->id);
        $this->assertEquals(2, $updatedAttachments[1]->order_column);

        // Attachment2 should be deleted
        $this->assertDatabaseMissing('attachments', [
            'id' => $attachment2->id,
        ]);
    }

    #[Test]
    public function it_throws_exception_when_updating_attachments_from_different_collection(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $attachment = $this->getAttachment($post, 'other_collection');

        $this->expectException(AttachmentCannotBeUpdated::class);

        $post->updateAttachments([
            ['id' => $attachment->id],
        ]);
    }

    #[Test]
    public function it_can_clear_attachment_collection(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        Event::fake();

        $attachment1 = $this->getAttachment($post);
        $attachment2 = $this->getAttachment($post);

        $this->assertCount(2, $post->getAttachments());

        $post->clearAttachmentCollection();

        $this->assertCount(0, $post->getAttachments());
        $this->assertDatabaseMissing('attachments', ['id' => $attachment1->id]);
        $this->assertDatabaseMissing('attachments', ['id' => $attachment2->id]);

        Event::assertDispatched(AttachmentCollectionHasBeenClearedEvent::class, function ($event) use ($post) {
            return $event->model->is($post) && $event->collectionName === 'default';
        });
    }

    #[Test]
    public function it_can_update_attachment_media(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $media1 = $this->getMedia();
        $media2 = $this->getMedia();
        $media3 = $this->getMedia();

        // Add initial attachments
        $post->addAttachment($media1)->toAttachmentCollection();
        $post->addAttachment($media2)->toAttachmentCollection();

        // Update with a new set of media IDs (remove one, keep one, add one)
        $result = $post->updateAttachmentMedia([$media1->id, $media3->id]);

        $this->assertCount(1, $result);
        $this->assertEquals($media3->id, $result->first()->media_id);

        // Check database
        $this->assertDatabaseHas('attachments', [
            'model_id' => $post->id,
            'model_type' => 'post',
            'media_id' => $media1->id,
        ]);
        $this->assertDatabaseHas('attachments', [
            'model_id' => $post->id,
            'model_type' => 'post',
            'media_id' => $media3->id,
        ]);
        $this->assertDatabaseMissing('attachments', [
            'model_id' => $post->id,
            'model_type' => 'post',
            'media_id' => $media2->id,
        ]);
    }

    #[Test]
    public function it_can_clear_attachment_collection_except(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $attachment1 = $this->getAttachment($post);
        $attachment2 = $this->getAttachment($post);
        $attachment3 = $this->getAttachment($post);

        // Clear all attachments except the first one
        $post->clearAttachmentCollectionExcept('default', $attachment1);

        $this->assertCount(1, $post->getAttachments());
        $this->assertDatabaseHas('attachments', ['id' => $attachment1->id]);
        $this->assertDatabaseMissing('attachments', ['id' => $attachment2->id]);
        $this->assertDatabaseMissing('attachments', ['id' => $attachment3->id]);
    }

    #[Test]
    public function it_can_delete_an_attachment(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $attachment = $this->getAttachment($post);

        $this->assertDatabaseHas('attachments', ['id' => $attachment->id]);

        $post->deleteAttachment($attachment->id);

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
    }

    #[Test]
    public function it_throws_exception_when_deleting_non_existent_attachment(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        /** @var Post $post */
        $another_post = Post::factory()->create();
        $attachment = $this->getAttachment($another_post);

        $this->expectException(AttachmentCannotBeDeleted::class);

        $post->deleteAttachment($attachment);
    }

    #[Test]
    public function it_can_add_attachment_collection(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $mediaCollection = $post->addAttachmentCollection('test-collection');

        $this->assertInstanceOf(MediaCollection::class, $mediaCollection);
        $this->assertEquals('test-collection', $mediaCollection->name);
        $this->assertCount(1, $post->attachmentCollections);
    }

    #[Test]
    public function it_can_load_attachments(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $attachment1 = $this->getAttachment($post);
        $attachment2 = $this->getAttachment($post, 'other_collection');

        $defaultAttachments = $post->loadAttachments('default');
        $otherAttachments = $post->loadAttachments('other_collection');
        $allAttachments = $post->loadAttachments('');

        $this->assertCount(1, $defaultAttachments);
        $this->assertEquals($attachment1->id, $defaultAttachments->first()->id);

        $this->assertCount(1, $otherAttachments);
        $this->assertEquals($attachment2->id, $otherAttachments->first()->id);

        $this->assertCount(2, $allAttachments);
    }

    #[Test]
    public function it_can_add_attachment_conversion(): void
    {
        /** @var Post $post */
        $post = Post::factory()->create();

        $conversion = $post->addAttachmentConversion('thumbnail');

        $this->assertInstanceOf(Conversion::class, $conversion);
        $this->assertEquals('thumbnail', $conversion->getName());
        $this->assertCount(1, $post->attachmentConversions);
    }

    #[Test]
    public function it_can_update_single_attachment(): void
    {
        $this->withoutExceptionHandling();

        /** @var Post $post */
        $post = Post::factory()->create();
        $media = $this->getMedia();

        // Create a request with media_id
        $request = new Request([
            'avatar' => $media->id
        ]);

        // Update the attachment
        $result = $post->updateSingleAttachment('avatar', $request);

        $this->assertInstanceOf(Attachment::class, $result);
        $this->assertEquals($media->id, $result->media_id);
        $this->assertEquals('avatar', $result->collection_name);

        // Test clearing the attachment
        $clearRequest = new Request([
            'avatar' => null,
            'clear_file' => true
        ]);

        $clearResult = $post->updateSingleAttachment('avatar', $clearRequest);

        $this->assertEquals(0, $clearResult);
        $this->assertCount(0, $post->getAttachments('avatar'));
    }

    #[Test]
    public function it_can_scope_query_with_attachments(): void
    {
        // Create post with attachment
        $post = Post::factory()->create();
        $this->getAttachment($post);

        // Test the scope
        $query = Post::withAttachments()->toSql();

        $this->assertStringContainsString('select * from', strtolower($query));
        // The actual query will vary by Laravel version, but we can confirm
        // the scope is applied by checking the eager loading after execution

        $result = Post::withAttachments()->first();
        $this->assertTrue($result->relationLoaded('attachments'));
    }
}
