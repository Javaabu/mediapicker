<?php

namespace Javaabu\Mediapicker\Tests\Feature\Fields;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Javaabu\Manifest\Facades\Manifest;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\Rules\LibraryMedia;
use Javaabu\Mediapicker\Stencil\MediaPickerField;
use Javaabu\Mediapicker\Tests\Http\InteractsWithLibrary;
use Javaabu\Mediapicker\Tests\TestCase;
use Javaabu\Mediapicker\Tests\TestSupport\Models\Article;
use Javaabu\Stencil\Form;
use PHPUnit\Framework\Attributes\Test;

class PickerFieldTest extends TestCase
{
    use InteractsWithLibrary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpLibrary();
        view()->share('errors', new ViewErrorBag);
    }

    protected function article(): Article
    {
        return Article::query()->forceCreate(['title' => 'ހުޅުމާލެ']);
    }

    /**
     * @return array<string, mixed>
     */
    protected function fieldConfig(string $html): array
    {
        preg_match('/data-mediapicker-field="([^"]*)"/', $html, $matches);

        return json_decode(html_entity_decode($matches[1] ?? '{}'), true);
    }

    #[Test]
    public function it_renders_a_single_picker_with_the_models_attachment(): void
    {
        Manifest::fake();

        $user = $this->userWith(['view_media']);
        $this->actingAs($user);

        $article = $this->article();
        $media = $this->libraryMedia($user, name: 'ބީޗް');
        $article->updateAttachmentMedia([$media->id], 'featured_image');

        $html = Blade::render('<x-mediapicker::picker name="featured_image" :model="$article" type="image" required />', ['article' => $article]);
        $config = $this->fieldConfig($html);

        $this->assertStringContainsString('name="featured_image" value="' . $media->id . '"', $html);
        $this->assertStringContainsString('ބީޗް', $html);
        $this->assertStringContainsString('Featured Image', $html);
        $this->assertEquals('featured_image', $config['name']);
        $this->assertFalse($config['multiple']);
        $this->assertEquals(['image'], $config['type']);
        $this->assertEquals(url('/media'), $config['url']);
        $this->assertEquals($media->id, $config['value'][0]['id']);
        $this->assertStringContainsString('image/webp', $config['accept']);
        $this->assertStringNotContainsString('application/pdf', $config['accept']);
        Manifest::assertAssetRequired('mediapicker::picker');
    }

    #[Test]
    public function it_renders_a_multiple_picker_in_order(): void
    {
        $user = $this->userWith(['view_media']);
        $this->actingAs($user);

        $article = $this->article();
        $first = $this->libraryMedia($user, name: 'First');
        $second = $this->libraryMedia($user, name: 'Second');
        $article->updateAttachmentMedia([$second->id, $first->id], 'gallery');

        $html = Blade::render('<x-mediapicker::picker name="gallery" :model="$article" multiple :label="__(\'ގެލަރީ\')" />', ['article' => $article]);

        $this->assertStringContainsString('ގެލަރީ', $html);
        $this->assertStringContainsString('name="gallery[]" value="' . $second->id . '"', $html);
        $this->assertEquals([$second->id, $first->id], array_column($this->fieldConfig($html)['value'], 'id'));
    }

    #[Test]
    public function an_empty_picker_still_submits_the_field(): void
    {
        $this->actingAs($this->userWith(['view_media']));

        $html = Blade::render('<x-mediapicker::picker name="gallery" multiple />');

        $this->assertStringContainsString('<input type="hidden" name="gallery" value="">', $html);
        $this->assertStringContainsString('Nothing chosen', $html);
    }

    #[Test]
    public function it_shows_the_old_input_after_a_failed_validation(): void
    {
        $user = $this->userWith(['view_media']);
        $this->actingAs($user);

        $media = $this->libraryMedia($user, name: 'Old');
        $hidden = $this->libraryMedia($this->userWith([]), name: 'Not mine');

        $this->session(['_old_input' => ['gallery' => [$hidden->id, $media->id]]]);
        request()->setLaravelSession(app('session.store'));

        $html = Blade::render('<x-mediapicker::picker name="gallery" multiple :value="[]" />');

        $this->assertEquals([$media->id], array_column($this->fieldConfig($html)['value'], 'id'));
        $this->assertStringNotContainsString('Not mine', $html);
    }

    #[Test]
    public function it_validates_the_chosen_media(): void
    {
        $user = $this->userWith(['view_media']);
        $this->actingAs($user);

        $image = $this->libraryMedia($user);
        $pdf = $this->libraryMedia($user, 'test.pdf');
        $others = $this->libraryMedia($this->userWith([]));

        $passes = fn (mixed $value, mixed $type = null) => Validator::make(['field' => $value], ['field' => [new LibraryMedia($type)]])->passes();

        $this->assertTrue($passes($image->id));
        $this->assertTrue($passes([$image->id, $pdf->id]));
        $this->assertTrue($passes(null));
        $this->assertTrue($passes($image->id, 'image'));
        $this->assertFalse($passes($pdf->id, 'image'));
        $this->assertFalse($passes($others->id));
        $this->assertFalse($passes(999));
        $this->assertFalse($passes('abc'));
    }

    #[Test]
    public function the_stencil_field_reads_validates_and_saves_attachments(): void
    {
        $user = $this->userWith(['view_media']);
        $this->actingAs($user);

        $article = $this->article();
        $first = $this->libraryMedia($user);
        $second = $this->libraryMedia($user);
        $article->updateAttachmentMedia([$first->id], 'gallery');

        $form = Form::make()->model($article)->schema([
            MediaPickerField::make('gallery')->mediaType('image')->multiple(),
            MediaPickerField::make('featured')->collection('featured_image')->mediaType('image'),
        ]);

        $this->assertEquals([$first->id], $form->getStateValue('gallery'));

        $rules = $form->rules();

        $this->assertEquals(['nullable', 'array'], $rules['gallery']);
        $this->assertInstanceOf(LibraryMedia::class, $rules['gallery.*'][0]);
        $this->assertInstanceOf(LibraryMedia::class, $rules['featured'][1]);

        $form->save($form->validate(['gallery' => [(string) $second->id, (string) $first->id], 'featured' => (string) $second->id]));

        $article->refresh();

        $this->assertEquals([$second->id, $first->id], $article->getAttachments('gallery')->pluck('media_id')->all());
        $this->assertEquals($second->id, $article->getFirstAttachmentMedia('featured_image')->id);
    }

    #[Test]
    public function the_stencil_field_renders_in_both_drivers(): void
    {
        $user = $this->userWith(['view_media']);
        $this->actingAs($user);

        $article = $this->article();
        $media = $this->libraryMedia($user, name: 'ބީޗް');
        $article->updateAttachmentMedia([$media->id], 'gallery');

        $form = Form::make()->model($article)->action('/articles/1')->schema([
            MediaPickerField::make('gallery')->label('ގެލަރީ')->mediaType('image')->multiple()->crop('wide'),
        ]);

        $html = $form->render('blade')->toHtml();

        $this->assertStringContainsString('data-mediapicker-field', $html);
        $this->assertStringContainsString('name="gallery[]" value="' . $media->id . '"', $html);
        $this->assertStringContainsString('ގެލަރީ', $html);

        $json = json_decode(json_encode($form->render('json')), true);
        $node = collect($json['components'] ?? $json['schema'] ?? [])->firstWhere('type', 'mediapicker');

        $this->assertNotNull($node, json_encode($json));
        $this->assertEquals('gallery[]', $node['name']);
        $this->assertTrue($node['props']['multiple']);
        $this->assertEquals('wide', $node['props']['crop']);
        $this->assertEquals($media->id, $node['props']['value'][0]['id']);
        $this->assertEquals(url('/media'), $node['props']['url']);
    }

    #[Test]
    public function it_gives_react_the_picker_config(): void
    {
        $config = Mediapicker::pickerConfig();

        $this->assertEquals(url('/media'), $config['url']);
        $this->assertEquals(url('/admin/media'), Mediapicker::pickerConfig('admin.media')['url']);
        $this->assertEquals(['image', 'video', 'audio', 'document', 'archive'], array_column($config['types'], 'value'));
        $this->assertEquals('Choose media', $config['strings']['choose_media']);
        $this->assertEquals(16 / 9, $config['crop_presets'][1]['aspect']);
    }
}
