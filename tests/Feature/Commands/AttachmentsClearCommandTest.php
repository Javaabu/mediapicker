<?php

namespace Javaabu\Mediapicker\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Mediapicker\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AttachmentsClearCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_clear_all_attachments(): void
    {
        $attachment = $this->getAttachment();

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
        ]);

        $this->artisan('mediapicker:clear')
            ->assertSuccessful();

        $this->assertDatabaseEmpty('attachments');
    }

    #[Test]
    public function it_can_clear_attachments_of_a_specific_model_type(): void
    {
        $post = $this->getModel();
        $attachment = $this->getAttachment($post);

        $other_model = $this->getModelWithConversions();
        $attachment_2 = $this->getAttachment($other_model);

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
        ]);

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment_2->id,
        ]);

        $this->artisan('mediapicker:clear', ['modelType' => 'post'])
            ->assertSuccessful();

        $this->assertDatabaseMissing('attachments', [
            'id' => $attachment->id,
        ]);

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment_2->id,
        ]);
    }

    #[Test]
    public function it_can_clear_attachments_of_a_specific_collection(): void
    {
        $post = $this->getModel();
        $attachment = $this->getAttachment($post, 'test1');

        $other_model = $this->getModelWithConversions();
        $attachment_2 = $this->getAttachment($other_model, 'test');

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
        ]);

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment_2->id,
        ]);

        $this->artisan('mediapicker:clear', ['collectionName' => 'test1'])
            ->assertSuccessful();

        $this->assertDatabaseMissing('attachments', [
            'id' => $attachment->id,
        ]);

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment_2->id,
        ]);
    }
}
