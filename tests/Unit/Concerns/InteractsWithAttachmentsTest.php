<?php

namespace Javaabu\Mediapicker\Tests\Unit\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Mediapicker\Models\Attachment;
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

}
