<?php

namespace Javaabu\Mediapicker\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Javaabu\Mediapicker\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AttachmentsCleanCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_clean_orphaned_attachments(): void
    {
        $post = $this->getModel();
        $attachment = $this->getAttachment($post);

        DB::table('posts')
            ->where('id', $post->id)
            ->delete();

        $attachment_2 = $this->getAttachment();

        $this->assertDatabaseMissing('posts', [
            'id' => $post->id,
        ]);

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
        ]);

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment_2->id,
        ]);

        $this->artisan('mediapicker:clean', ['--delete-orphaned' => true])
            ->assertSuccessful();

        $this->assertDatabaseMissing('attachments', [
            'id' => $attachment->id,
        ]);

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment_2->id,
        ]);
    }

    #[Test]
    public function it_can_clean_deprecated_attachment_conversion_files(): void
    {
        $model = $this->getModelWithConversions();
        $media = $this->getMedia();

        $attachment = $model->addAttachment($media)
                            ->toAttachmentCollection();

        $path = $model->getFirstAttachmentPath(conversionName: 'test');

        $this->assertEquals($this->getMediaDirectory($media->getKey() . '/conversions/test-test.jpg'), $path);
        $this->assertFileExists($path);

        $new_path = $this->getMediaDirectory($media->getKey() . '/conversions/test-test2.jpg');
        File::copy($path, $new_path);

        $this->assertFileExists($new_path);

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
        ]);

        $this->artisan('mediapicker:clean')
            ->assertSuccessful();

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
        ]);

        $this->assertFileExists($path);
        $this->assertFileDoesNotExist($new_path);
    }
}
