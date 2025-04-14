<?php

namespace Javaabu\Mediapicker\Tests\Feature\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Javaabu\Mediapicker\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AttachmentsRegenerateCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_regenerate_missing_attachment_conversions(): void
    {
        $model = $this->getModelWithConversions();
        $media = $this->getMedia();

        $attachment = $model->addAttachment($media)
                            ->toAttachmentCollection();

        $path = $model->getFirstAttachmentPath(conversionName: 'test');

        $this->assertEquals($this->getMediaDirectory($media->getKey() . '/conversions/test-test.jpg'), $path);

        File::delete($path);
        $this->assertFileDoesNotExist($path);

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
        ]);

        $this->artisan('mediapicker:regenerate')
            ->assertSuccessful();

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
        ]);

        $this->assertFileExists($path);
    }

    #[Test]
    public function it_can_regenerate_only_specific_attachment_conversions(): void
    {
        $model = $this->getModelWithMultipleConversions();
        $media = $this->getMedia();

        $attachment = $model->addAttachment($media)
            ->toAttachmentCollection();

        $path_1 = $model->getFirstAttachmentPath(conversionName: 'test');
        $path_2 = $model->getFirstAttachmentPath(conversionName: 'test2');

        $this->assertEquals($this->getMediaDirectory($media->getKey() . '/conversions/test-test.jpg'), $path_1);
        $this->assertEquals($this->getMediaDirectory($media->getKey() . '/conversions/test-test2.jpg'), $path_2);

        File::delete($path_1);
        File::delete($path_2);

        $this->assertFileDoesNotExist($path_1);
        $this->assertFileDoesNotExist($path_2);

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
        ]);

        $this->artisan('mediapicker:regenerate', ['--only' => 'test'])
            ->assertSuccessful();

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->id,
        ]);

        $this->assertFileExists($path_1);
        $this->assertFileDoesNotExist($path_2);
    }

    #[Test]
    public function it_can_regenerate_by_model_type(): void
    {
        // Create two different model types
        $model1 = $this->getModelWithConversions();
        $model2 = $this->getModelWithMultipleConversions();

        $media1 = $this->getMedia();
        $media2 = $this->getMedia();

        $attachment1 = $model1->addAttachment($media1)->toAttachmentCollection();
        $attachment2 = $model2->addAttachment($media2)->toAttachmentCollection();

        $path1 = $model1->getFirstAttachmentPath(conversionName: 'test');
        $path2 = $model2->getFirstAttachmentPath(conversionName: 'test');

        File::delete($path1);
        File::delete($path2);

        $this->assertFileDoesNotExist($path1);
        $this->assertFileDoesNotExist($path2);

        // Only regenerate first model type
        $this->artisan('mediapicker:regenerate', [
            'modelType' => $model1->getMorphClass(),
        ])->assertSuccessful();

        $this->assertFileExists($path1);
        $this->assertFileDoesNotExist($path2);
    }

    #[Test]
    public function it_can_regenerate_by_ids(): void
    {
        $model = $this->getModelWithConversions();

        $media1 = $this->getMedia();
        $media2 = $this->getMedia();

        $attachment1 = $model->addAttachment($media1)->toAttachmentCollection();
        $attachment2 = $model->addAttachment($media2)->toAttachmentCollection();

        $path1 = $attachment1->getPath('test');
        $path2 = $attachment2->getPath('test');

        File::delete($path1);
        File::delete($path2);

        $this->assertFileDoesNotExist($path1);
        $this->assertFileDoesNotExist($path2);

        // Only regenerate first attachment
        $this->artisan('mediapicker:regenerate', [
            '--ids' => [$attachment1->id]
        ])->assertSuccessful();

        $this->assertFileExists($path1);
        $this->assertFileDoesNotExist($path2);
    }

    #[Test]
    public function it_can_regenerate_starting_from_id(): void
    {
        $model = $this->getModelWithConversions();

        $media1 = $this->getMedia();
        $media2 = $this->getMedia();
        $media3 = $this->getMedia();

        $attachment1 = $model->addAttachment($media1)->toAttachmentCollection();
        $attachment2 = $model->addAttachment($media2)->toAttachmentCollection();
        $attachment3 = $model->addAttachment($media3)->toAttachmentCollection();

        $path1 = $attachment1->getPath('test');
        $path2 = $attachment2->getPath('test');
        $path3 = $attachment3->getPath('test');

        File::delete($path1);
        File::delete($path2);
        File::delete($path3);

        $this->assertFileDoesNotExist($path1);
        $this->assertFileDoesNotExist($path2);
        $this->assertFileDoesNotExist($path3);

        // Regenerate from attachment2 onwards
        $this->artisan('mediapicker:regenerate', [
            '--starting-from-id' => $attachment2->id
        ])->assertSuccessful();

        $this->assertFileDoesNotExist($path1);
        $this->assertFileExists($path2);
        $this->assertFileExists($path3);
    }

    #[Test]
    public function it_can_exclude_starting_id_when_regenerating(): void
    {
        $model = $this->getModelWithConversions();

        $media1 = $this->getMedia();
        $media2 = $this->getMedia();
        $media3 = $this->getMedia();

        $attachment1 = $model->addAttachment($media1)->toAttachmentCollection();
        $attachment2 = $model->addAttachment($media2)->toAttachmentCollection();
        $attachment3 = $model->addAttachment($media3)->toAttachmentCollection();

        $path1 = $attachment1->getPath('test');
        $path2 = $attachment2->getPath('test');
        $path3 = $attachment3->getPath('test');

        File::delete($path1);
        File::delete($path2);
        File::delete($path3);

        $this->assertFileDoesNotExist($path1);
        $this->assertFileDoesNotExist($path2);
        $this->assertFileDoesNotExist($path3);

        // Regenerate from attachment2 onwards but exclude attachment2
        $this->artisan('mediapicker:regenerate', [
            '--starting-from-id' => $attachment2->id,
            '--exclude-starting-id' => true
        ])->assertSuccessful();

        $this->assertFileDoesNotExist($path1);
        $this->assertFileDoesNotExist($path2);
        $this->assertFileExists($path3);
    }

    #[Test]
    public function it_can_regenerate_only_missing_conversions(): void
    {
        $model = $this->getModelWithMultipleConversions();
        $media = $this->getMedia();
        $attachment = $model->addAttachment($media)->toAttachmentCollection();

        $path1 = $attachment->getPath('test');
        $path2 = $attachment->getPath('test2');

        // Only delete the first conversion
        File::delete($path1);

        $this->assertFileDoesNotExist($path1);
        $this->assertFileExists($path2);

        // Get last modified time of path2 before regeneration
        $lastModified = File::lastModified($path2);

        // Wait a moment to ensure the timestamps would be different if regenerated
        sleep(1);

        $this->artisan('mediapicker:regenerate', [
            '--only-missing' => true
        ])->assertSuccessful();

        $this->assertFileExists($path1);
        $this->assertFileExists($path2);

        // Verify that path2 wasn't regenerated (last modified time shouldn't change)
        $this->assertEquals($lastModified, File::lastModified($path2));
    }

    #[Test]
    public function it_can_combine_options_model_type_and_starting_from_id(): void
    {
        // Create models of different types
        $model1 = $this->getModelWithConversions();
        $model2 = $this->getModelWithMultipleConversions();

        // Create attachments for each model
        $media1 = $this->getMedia();
        $media2 = $this->getMedia();
        $media3 = $this->getMedia();

        $attachment1 = $model1->addAttachment($media1)->toAttachmentCollection();
        $attachment2 = $model1->addAttachment($media2)->toAttachmentCollection();
        $attachment3 = $model2->addAttachment($media3)->toAttachmentCollection();

        $path1 = $attachment1->getPath('test');
        $path2 = $attachment2->getPath('test');
        $path3 = $attachment3->getPath('test');

        File::delete($path1);
        File::delete($path2);
        File::delete($path3);

        $this->assertFileDoesNotExist($path1);
        $this->assertFileDoesNotExist($path2);
        $this->assertFileDoesNotExist($path3);

        // Regenerate only model1 attachments with id >= attachment2
        $this->artisan('mediapicker:regenerate', [
            'modelType' => $model1->getMorphClass(),
            '--starting-from-id' => $attachment2->id
        ])->assertSuccessful();

        $this->assertFileDoesNotExist($path1);
        $this->assertFileExists($path2);
        $this->assertFileDoesNotExist($path3);
    }
}
