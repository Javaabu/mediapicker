<?php

namespace Javaabu\Mediapicker\Tests\Feature\Blocks;

use Illuminate\Support\Facades\Validator;
use Javaabu\BlockEditor\Facades\BlockEditor;
use Javaabu\BlockEditor\Rules\BlockContent;
use Javaabu\Mediapicker\Blocks\File;
use Javaabu\Mediapicker\Blocks\Gallery;
use Javaabu\Mediapicker\Blocks\Image;
use Javaabu\Mediapicker\Blocks\MediaVideo;
use Javaabu\Mediapicker\Tests\Http\InteractsWithLibrary;
use Javaabu\Mediapicker\Tests\TestCase;
use Orchestra\Testbench\Attributes\DefineEnvironment;
use PHPUnit\Framework\Attributes\Test;

class MediaBlocksTest extends TestCase
{
    use InteractsWithLibrary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpLibrary();
    }

    /**
     * @param  array<int, array<string, mixed>>  $blocks
     * @return array<string, mixed>
     */
    protected function doc(array $blocks): array
    {
        return ['type' => 'doc', 'content' => $blocks];
    }

    #[Test]
    public function it_registers_the_blocks_and_replaces_the_core_image(): void
    {
        $this->assertInstanceOf(Image::class, BlockEditor::get('image'));
        $this->assertInstanceOf(Gallery::class, BlockEditor::get('gallery'));
        $this->assertInstanceOf(File::class, BlockEditor::get('file'));
        $this->assertInstanceOf(MediaVideo::class, BlockEditor::get('mediaVideo'));
        $this->assertTrue(BlockEditor::has('embed'));
    }

    #[Test]
    public function the_editor_gets_the_picker_config_and_script(): void
    {
        $config = collect(BlockEditor::editorConfig()['blocks'])->keyBy('name');

        $this->assertEquals(url('/media'), $config['gallery']['mediapicker']['url']);
        $this->assertEquals('Gallery', $config['gallery']['title']);
        $this->assertEquals('Video file', $config['mediaVideo']['title']);
        $this->assertContains('mediapicker::blocks', BlockEditor::scripts());
    }

    #[Test]
    public function it_renders_an_image_with_the_current_media(): void
    {
        $user = $this->userWith(['view_media']);
        $media = $this->libraryMedia($user, custom_properties: ['alt' => 'ކަނޑު', 'width' => 340, 'height' => 280]);

        $html = BlockEditor::render($this->doc([
            ['type' => 'image', 'attrs' => ['mediaId' => $media->id, 'src' => '/old.jpg', 'caption' => 'ހުޅުމާލެ', 'link' => 'https://example.com']],
        ]));

        $this->assertStringContainsString('src="' . $media->getUrl('mediapicker-large') . '"', $html);
        $this->assertStringContainsString('alt="ކަނޑު"', $html);
        $this->assertStringContainsString('width="340"', $html);
        $this->assertStringContainsString('<figcaption dir="auto">ހުޅުމާލެ</figcaption>', $html);
        $this->assertStringContainsString('href="https://example.com"', $html);
    }

    #[Test]
    public function it_still_renders_images_that_only_have_a_url(): void
    {
        $html = BlockEditor::render($this->doc([
            ['type' => 'image', 'attrs' => ['src' => 'https://example.com/a.jpg', 'alt' => 'A']],
        ]));

        $this->assertStringContainsString('src="https://example.com/a.jpg"', $html);
    }

    #[Test]
    public function it_renders_a_gallery_file_and_video(): void
    {
        $user = $this->userWith(['view_media']);
        $first = $this->libraryMedia($user);
        $report = $this->libraryMedia($user, 'test.pdf', 'ރިޕޯޓް');
        $video = $this->libraryMedia($user, 'test.mp4');

        $html = BlockEditor::render($this->doc([
            ['type' => 'gallery', 'attrs' => ['columns' => 2, 'items' => [
                ['id' => $first->id, 'src' => '/old.jpg'],
                ['id' => null, 'src' => 'https://example.com/b.jpg', 'alt' => 'B'],
            ]]],
            ['type' => 'file', 'attrs' => ['mediaId' => $report->id, 'href' => '/old.pdf']],
            ['type' => 'mediaVideo', 'attrs' => ['mediaId' => $video->id, 'src' => '/old.mp4', 'autoplay' => true]],
        ]));

        $this->assertStringContainsString('--mp-gallery-columns: 2', $html);
        $this->assertStringContainsString('href="' . $first->getUrl() . '"', $html);
        $this->assertStringContainsString('src="https://example.com/b.jpg"', $html);
        $this->assertStringContainsString('href="' . $report->getUrl() . '" download', $html);
        $this->assertStringContainsString('ރިޕޯޓް', $html);
        $this->assertStringContainsString('src="' . $video->getUrl() . '"', $html);
        $this->assertStringContainsString(' autoplay', $html);
        $this->assertStringContainsString(' muted', $html);
    }

    #[Test]
    public function it_drops_unsafe_urls(): void
    {
        $document = BlockEditor::sanitize($this->doc([
            ['type' => 'image', 'attrs' => ['src' => 'javascript:alert(1)']],
            ['type' => 'file', 'attrs' => ['href' => 'javascript:alert(1)']],
            ['type' => 'gallery', 'attrs' => ['items' => [['src' => 'javascript:alert(1)']]]],
        ]));

        $this->assertEquals([], $document->toArray()['content'] ?? []);
    }

    #[Test]
    public function it_only_accepts_media_the_editor_may_see(): void
    {
        $user = $this->userWith(['view_media']);
        $this->actingAs($user);

        $own = $this->libraryMedia($user);
        $others = $this->libraryMedia($this->userWith([]));

        $validate = fn (int $id) => Validator::make(
            ['body' => $this->doc([['type' => 'image', 'attrs' => ['mediaId' => $id, 'src' => '/storage/a.jpg']]])],
            ['body' => [new BlockContent]],
        )->passes();

        $this->assertTrue($validate($own->id));
        $this->assertFalse($validate($others->id));
    }

    protected function withoutBlocks($app): void
    {
        $app['config']->set('mediapicker.blocks', false);
    }

    #[Test]
    #[DefineEnvironment('withoutBlocks')]
    public function the_blocks_can_be_turned_off(): void
    {
        $this->assertFalse(BlockEditor::has('gallery'));
        $this->assertNotInstanceOf(Image::class, BlockEditor::get('image'));
    }
}
