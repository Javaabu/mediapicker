<?php

namespace Javaabu\Mediapicker\Tests\Feature\Conversions;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Queue;
use Javaabu\Mediapicker\Conversions\FormatSupport;
use Javaabu\Mediapicker\Conversions\Jobs\PerformAttachmentConversionsJob;
use Javaabu\Mediapicker\Conversions\StaleConversions;
use Javaabu\Mediapicker\Cropping\Cropper;
use Javaabu\Mediapicker\Models\Media;
use Javaabu\Mediapicker\Tests\TestCase;
use Javaabu\Mediapicker\Tests\TestSupport\Models\Article;
use PHPUnit\Framework\Attributes\Test;

class AttachmentConversionsTest extends TestCase
{
    use RefreshDatabase;

    protected function article(): Article
    {
        return Article::query()->forceCreate(['title' => 'ހުޅުމާލެ']);
    }

    protected function attachedMedia(Article $article, string $collection = 'featured'): Media
    {
        $media = $this->getMedia();

        $article->addAttachment($media)->toAttachmentCollection($collection);

        return $media->refresh();
    }

    #[Test]
    public function it_generates_the_conversion_in_extra_formats(): void
    {
        $article = $this->article();
        $this->attachedMedia($article);

        $attachment = $article->getFirstAttachment('featured');

        $this->assertEquals(['jpg', 'webp'], $attachment->conversionMetadata('card')['formats']);

        $webp = $attachment->getFormatUrl('card', 'webp');

        $this->assertStringEndsWith('.webp', $webp);
        $this->assertFileExists($this->getTempDirectory() . parse_url($webp, PHP_URL_PATH));
        $this->assertNull($attachment->getFormatUrl('card', 'avif'));
    }

    #[Test]
    public function it_skips_formats_the_image_driver_cannot_write(): void
    {
        config(['media-library.image_driver' => 'gd']);

        $article = $this->article();
        $this->attachedMedia($article);

        $formats = $article->getFirstAttachment('featured')->conversionMetadata('card')['formats'];

        $this->assertEquals(FormatSupport::supports('webp') ? ['jpg', 'webp'] : ['jpg'], $formats);
        $this->assertFalse(FormatSupport::supports('bmp'));
    }

    #[Test]
    public function it_records_the_size_of_each_conversion(): void
    {
        $article = $this->article();
        $this->attachedMedia($article);

        $metadata = $article->getFirstAttachment('featured')->conversionMetadata('card');

        $this->assertEquals(160, $metadata['width']);
        $this->assertEquals(90, $metadata['height']);
    }

    #[Test]
    public function it_generates_responsive_widths_for_srcset(): void
    {
        $article = $this->article();
        $article->addAttachment($this->getMedia($this->getTestPng()))->toAttachmentCollection('hero');

        $attachment = $article->getFirstAttachment('hero');
        $metadata = $attachment->conversionMetadata('hero');
        $srcset = $attachment->getSrcset('hero');

        $this->assertEquals(['png'], $metadata['formats']);
        $this->assertStringContainsString($attachment->getUrl('hero') . ' 340w', $srcset);
        $this->assertNotEmpty($metadata['widths']);

        foreach ($metadata['widths'] as $width) {
            $this->assertLessThan(340, $width);
            $this->assertStringContainsString("___{$width}.png {$width}w", $srcset);
            $this->assertFileExists(str_replace('.png', "___{$width}.png", $attachment->getPath('hero')));
        }
    }

    #[Test]
    public function small_conversions_get_no_extra_widths(): void
    {
        $article = $this->article();
        $this->attachedMedia($article);

        $attachment = $article->getFirstAttachment('featured');

        $this->assertEquals([], $attachment->conversionMetadata('card')['widths']);
        $this->assertEquals($attachment->getUrl('card') . ' 160w', $attachment->getSrcset('card'));
        $this->assertStringContainsString('.webp 160w', $attachment->getSrcset('card', 'webp'));
    }

    #[Test]
    public function it_queues_conversions_that_are_queued(): void
    {
        Queue::fake();

        $article = $this->article();
        $this->attachedMedia($article);

        $attachment = $article->getFirstAttachment('featured');

        Queue::assertPushed(PerformAttachmentConversionsJob::class, 1);

        $this->assertTrue($attachment->hasGeneratedConversion('card'));
        $this->assertFalse($attachment->hasGeneratedConversion('large'));
        $this->assertEquals($attachment->getUrl(), $attachment->getAvailableUrl('large'));
        $this->assertEquals($attachment->getUrl('card'), $attachment->getAvailableUrl(['large', 'card']));
    }

    #[Test]
    public function it_runs_queued_conversions_in_the_job(): void
    {
        $article = $this->article();
        $this->attachedMedia($article);

        $attachment = $article->getFirstAttachment('featured');

        $this->assertTrue($attachment->hasGeneratedConversion('large'));
        $this->assertEquals(200, $attachment->conversionMetadata('large')['width']);
    }

    #[Test]
    public function it_only_generates_the_conversions_of_the_attachments_collection(): void
    {
        $article = $this->article();
        $this->attachedMedia($article, 'gallery');

        $attachment = $article->getFirstAttachment('gallery');

        $this->assertTrue($attachment->hasGeneratedConversion('gallery-thumb'));
        $this->assertNull($attachment->conversionMetadata('card'));
        $this->assertCount(1, glob(dirname($attachment->getPath('gallery-thumb')) . '/*'));
    }

    #[Test]
    public function it_does_not_process_media_again_when_it_is_attached_again(): void
    {
        $article = $this->article();
        $media = $this->attachedMedia($article);

        $path = $article->getFirstAttachmentPath('featured', 'card');
        $modified = filemtime($path);
        touch($path, $modified - 100);

        $other = $this->article();
        $other->addAttachment($media)->toAttachmentCollection('featured');

        clearstatcache();

        $this->assertEquals($modified - 100, filemtime($path));
    }

    #[Test]
    public function it_applies_the_crop_a_conversion_uses(): void
    {
        $article = $this->article();
        $media = $this->getMedia();

        app(Cropper::class)->crop($media, 'wide', ['x' => 10, 'y' => 20, 'width' => 320, 'height' => 180]);

        $article->addAttachment($media)->toAttachmentCollection('featured');

        $attachment = $article->getFirstAttachment('featured');

        $this->assertEquals(['x' => 10, 'y' => 20, 'width' => 320, 'height' => 180, 'rotation' => 0], $article->cardConversion()->getCrop($attachment->media));
        $this->assertEquals(['width' => 160, 'height' => 90], $this->getImageDimensions($attachment->getPath('card')));
    }

    #[Test]
    public function a_new_crop_regenerates_only_the_conversions_that_use_it(): void
    {
        $article = $this->article();
        $media = $this->attachedMedia($article);

        $attachment = $article->getFirstAttachment('featured');
        $old_card = $attachment->getPath('card');
        $large = $attachment->getPath('large');
        $large_modified = filemtime($large);
        touch($large, $large_modified - 100);

        app(Cropper::class)->crop($media, 'wide', ['x' => 0, 'y' => 0, 'width' => 160, 'height' => 90]);

        $attachment = $article->fresh()->getFirstAttachment('featured');
        $new_card = $attachment->getPath('card');

        clearstatcache();

        $this->assertNotEquals($old_card, $new_card);
        $this->assertFileExists($new_card);
        $this->assertTrue($attachment->hasGeneratedConversion('card'));
        $this->assertEquals($large, $attachment->getPath('large'));
        $this->assertEquals($large_modified - 100, filemtime($large));
    }

    #[Test]
    public function stale_conversion_files_can_be_cleaned(): void
    {
        $article = $this->article();
        $media = $this->attachedMedia($article);

        $old_card = $article->getFirstAttachment('featured')->getPath('card');

        app(Cropper::class)->crop($media, 'wide', ['x' => 0, 'y' => 0, 'width' => 160, 'height' => 90]);

        $this->assertFileExists($old_card);

        $removed = app(StaleConversions::class)->clean($media->refresh());

        $this->assertNotEmpty($removed);
        $this->assertFileDoesNotExist($old_card);
        $this->assertFileExists($article->fresh()->getFirstAttachment('featured')->getPath('card'));
        $this->assertFileExists($article->fresh()->getFirstAttachment('featured')->getPath('large'));
    }

    #[Test]
    public function the_library_thumbnail_is_made_right_away(): void
    {
        config(['media-library.queue_conversions_by_default' => true]);
        Queue::fake();

        $media = $this->getMedia();

        $this->assertTrue($media->hasGeneratedConversion('mediapicker-thumb'));
        $this->assertFalse($media->hasGeneratedConversion('mediapicker-large'));
    }

    #[Test]
    public function it_renders_an_attached_image_as_a_picture(): void
    {
        $article = $this->article();
        $media = $this->attachedMedia($article);
        $media->alt = 'ހުޅުމާލޭގެ ބީޗް';
        $media->save();

        $html = Blade::render('<x-mediapicker::image :model="$article" collection="featured" conversion="card" sizes="100vw" class="rounded" />', ['article' => $article->fresh()]);

        $this->assertStringContainsString('<picture>', $html);
        $this->assertStringContainsString('<source type="image/webp" srcset="', $html);
        $this->assertStringContainsString('width="160"', $html);
        $this->assertStringContainsString('height="90"', $html);
        $this->assertStringContainsString('alt="ހުޅުމާލޭގެ ބީޗް"', $html);
        $this->assertStringContainsString('sizes="100vw"', $html);
        $this->assertStringContainsString('class="rounded"', $html);
        $this->assertStringContainsString('loading="lazy"', $html);
    }

    #[Test]
    public function it_renders_nothing_without_an_image(): void
    {
        $html = Blade::render('<x-mediapicker::image :model="$article" collection="featured" conversion="card" />', ['article' => $this->article()]);

        $this->assertEquals('', trim($html));
    }

    #[Test]
    public function it_renders_a_library_image(): void
    {
        $media = $this->getMedia();

        $html = Blade::render('<x-mediapicker::image :media="$media" alt="" />', ['media' => $media]);

        $this->assertStringNotContainsString('<picture>', $html);
        $this->assertStringContainsString('src="' . $media->getUrl('mediapicker-large') . '"', $html);
        $this->assertStringContainsString('alt=""', $html);
    }
}
