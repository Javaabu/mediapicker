<?php

namespace Javaabu\Mediapicker\Tests\Unit\AttachmentCollections;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Javaabu\Mediapicker\AttachmentCollections\Exceptions\MediaIsTooBig;
use Javaabu\Mediapicker\AttachmentCollections\Exceptions\MediaUnacceptableForCollection;
use Javaabu\Mediapicker\AttachmentCollections\Exceptions\UnknownType;
use Javaabu\Mediapicker\AttachmentCollections\MediaAdder;
use Javaabu\Mediapicker\AttachmentCollections\MediaAdderFactory;
use Javaabu\Mediapicker\Tests\TestCase;
use Javaabu\Mediapicker\Tests\TestSupport\Models\Post;
use PHPUnit\Framework\Attributes\Test;

class MediaAdderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_set_media_adder_subject(): void
    {
        $model = $this->getModel();

        $media_adder = new MediaAdder();
        $media_adder = $media_adder->setSubject($model);

        $this->assertInstanceOf(MediaAdder::class, $media_adder);
    }

    #[Test]
    public function it_can_set_media_adder_media_from_an_id(): void
    {
        $media = $this->getMedia();

        $media_adder = new MediaAdder();

        $media_adder = $media_adder->setMedia($media->getKey());

        $this->assertInstanceOf(MediaAdder::class, $media_adder);
    }

    #[Test]
    public function it_can_set_media_adder_media_from_an_media_object(): void
    {
        $media = $this->getMedia();

        $media_adder = new MediaAdder();

        $media_adder = $media_adder->setMedia($media);

        $this->assertInstanceOf(MediaAdder::class, $media_adder);
    }

    #[Test]
    public function it_throws_an_error_when_setting_media_to_a_missing_media(): void
    {
        $media_adder = new MediaAdder();

        $this->expectException(UnknownType::class);

        $media_adder->setMedia(-1);
    }

    #[Test]
    public function it_throws_an_error_when_trying_to_attach_too_big_media(): void
    {
        $model = $this->getModel();
        $media = $this->getMedia();

        $media_adder = new MediaAdder();
        $media_adder->setSubject($model)
                    ->setMedia($media);

        config()->set('media-library.max_file_size', 1);

        $this->expectException(MediaIsTooBig::class);

        $media_adder->toAttachmentCollection();

        $this->assertDatabaseEmpty('attachments');
    }

    #[Test]
    public function it_throws_an_error_when_trying_to_attach_an_unaccepted_file(): void
    {
        $model = $this->getModelWithUnacceptedFile();
        $media = $this->getMedia();

        $media_adder = new MediaAdder();
        $media_adder->setSubject($model)
            ->setMedia($media);

        $this->expectException(MediaUnacceptableForCollection::class);

        $media_adder->toAttachmentCollection('test');

        $this->assertDatabaseEmpty('attachments');
    }

    #[Test]
    public function it_throws_an_error_when_trying_to_attach_an_unaccepted_mime_type(): void
    {
        $model = $this->getModelWithUnacceptedMimeType();
        $media = $this->getMedia();

        $media_adder = new MediaAdder();
        $media_adder->setSubject($model)
            ->setMedia($media);

        $this->expectException(MediaUnacceptableForCollection::class);

        $media_adder->toAttachmentCollection('test');

        $this->assertDatabaseEmpty('attachments');
    }

    #[Test]
    public function it_can_add_to_the_default_media_collection(): void
    {
        $model = $this->getModel();
        $media = $this->getMedia();

        $media_adder = new MediaAdder();
        $media_adder->setSubject($model)
            ->setMedia($media);

        $attachment = $media_adder->toAttachmentCollection();

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->getKey(),
            'media_id' => $media->getKey(),
            'model_id' => $model->getKey(),
            'model_type' => 'post',
            'order_column' => 1,
            'collection_name' => 'default'
        ]);
    }

    #[Test]
    public function it_can_add_to_a_specific_media_collection(): void
    {
        $model = $this->getModel();
        $media = $this->getMedia();

        $media_adder = new MediaAdder();
        $media_adder->setSubject($model)
            ->setMedia($media);

        $attachment = $media_adder->toAttachmentCollection('test');

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->getKey(),
            'media_id' => $media->getKey(),
            'model_id' => $model->getKey(),
            'model_type' => 'post',
            'order_column' => 1,
            'collection_name' => 'test'
        ]);
    }

    #[Test]
    public function it_increments_the_order_number_when_adding_media(): void
    {
        $model = $this->getModel();
        $media = $this->getMedia();

        $media_adder = new MediaAdder();
        $media_adder->setSubject($model)
            ->setMedia($media);

        $attachment = $media_adder->toAttachmentCollection();

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->getKey(),
            'media_id' => $media->getKey(),
            'model_id' => $model->getKey(),
            'model_type' => 'post',
            'order_column' => 1,
            'collection_name' => 'default'
        ]);

        $media = $this->getMedia();

        $media_adder = new MediaAdder();
        $media_adder->setSubject($model)
            ->setMedia($media);

        $attachment = $media_adder->toAttachmentCollection();

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->getKey(),
            'media_id' => $media->getKey(),
            'model_id' => $model->getKey(),
            'model_type' => 'post',
            'order_column' => 2,
            'collection_name' => 'default'
        ]);
    }

    #[Test]
    public function it_can_set_order_when_adding_media_to_a_collection(): void
    {
        $model = $this->getModel();
        $media = $this->getMedia();

        $media_adder = new MediaAdder();
        $media_adder->setSubject($model)
            ->setMedia($media)
            ->setOrder(3);

        $attachment = $media_adder->toAttachmentCollection();

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment->getKey(),
            'media_id' => $media->getKey(),
            'model_id' => $model->getKey(),
            'model_type' => 'post',
            'order_column' => 3,
            'collection_name' => 'default'
        ]);
    }

    #[Test]
    public function it_removes_previous_attachments_when_adding_media_to_a_single_collection(): void
    {
        $model = $this->getModelWithSingleFile();
        $media = $this->getMedia();

        $media_adder = new MediaAdder();
        $media_adder->setSubject($model)
            ->setMedia($media);

        $attachment_1 = $media_adder->toAttachmentCollection('test');

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment_1->getKey(),
            'media_id' => $media->getKey(),
            'model_id' => $model->getKey(),
            'model_type' => $model->getMorphClass(),
            'order_column' => 1,
            'collection_name' => 'test'
        ]);

        $media_2 = $this->getMedia();

        $media_adder = new MediaAdder();
        $media_adder->setSubject($model)
            ->setMedia($media_2);

        $attachment_2 = $media_adder->toAttachmentCollection('test');

        $this->assertDatabaseMissing('attachments', [
            'id' => $attachment_1->getKey(),
        ]);

        $this->assertDatabaseHas('attachments', [
            'id' => $attachment_2->getKey(),
            'media_id' => $media_2->getKey(),
            'model_id' => $model->getKey(),
            'model_type' => $model->getMorphClass(),
            'order_column' => 2,
            'collection_name' => 'test'
        ]);
    }
}
