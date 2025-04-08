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
        $attachment = $this->getAttachment();

        $conversion_collection = AttachmentConversionCollection::createForAttachment($attachment);

        $this->assertInstanceOf(AttachmentConversionCollection::class, $conversion_collection);
    }
}
