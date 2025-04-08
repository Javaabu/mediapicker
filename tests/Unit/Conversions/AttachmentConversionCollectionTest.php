<?php

namespace Javaabu\Mediapicker\Tests\Unit\Conversions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Mediapicker\Conversions\AttachmentConversionCollection;
use Javaabu\Mediapicker\Models\Attachment;
use Javaabu\Mediapicker\Tests\TestCase;
use Javaabu\Mediapicker\Tests\TestSupport\Models\Post;
use Javaabu\Mediapicker\Tests\TestSupport\Models\User;
use PHPUnit\Framework\Attributes\Test;

class AttachmentConversionCollectionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_create_an_attachment_conversion_for_an_attachment(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $user->addMedia($this->getTestJpg())
             ->toMediaCollection('mediapicker');

        $media = $user->getFirstMedia('mediapicker');

        /** @var Post $post */
        $post = Post::factory()->create();


        $attachment = new Attachment();
        $attachment->media()->associate($media);
        $attachment->model()->associate($post);

        $conversion_collection = AttachmentConversionCollection::createForAttachment($attachment);

        $this->assertInstanceOf(AttachmentConversionCollection::class, $conversion_collection);
    }
}
