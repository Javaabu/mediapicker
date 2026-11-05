<?php

namespace Javaabu\Mediapicker\Tests\Translatable;

use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\Models\Media;
use Javaabu\Mediapicker\Models\Translatable\Media as TranslatableMedia;
use Javaabu\Mediapicker\Support\Translations;
use Javaabu\Mediapicker\Tests\Http\InteractsWithLibrary;
use Javaabu\Mediapicker\Tests\TestCase;
use Javaabu\Translatable\Facades\Languages;
use Javaabu\Translatable\Testing\InteractsWithLanguages;
use Javaabu\Translatable\TranslatableServiceProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

#[Group('translatable')]
class TranslatableMediaTest extends TestCase
{
    use InteractsWithLanguages;
    use InteractsWithLibrary;

    protected function getPackageProviders($app): array
    {
        return [...parent::getPackageProviders($app), TranslatableServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpLibrary();
        $this->setUpLanguages(['en', 'dv'], 'en');
    }

    #[Test]
    public function the_migration_adds_the_translation_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('media', ['translations', 'lang']));
    }

    #[Test]
    public function it_uses_the_translatable_model_when_translatable_is_on(): void
    {
        $this->assertEquals(TranslatableMedia::class, Mediapicker::mediaModel());

        $this->withoutTranslatable(fn () => $this->assertEquals(Media::class, Mediapicker::mediaModel()));

        config(['mediapicker.media_model' => Media::class]);
        $this->assertEquals(Media::class, Mediapicker::mediaModel());
    }

    #[Test]
    public function it_translates_the_details_including_custom_properties(): void
    {
        $user = $this->userWith(['view_media', 'edit_media']);
        $media = TranslatableMedia::query()->findOrFail($this->libraryMedia($user)->id);

        $media->fill([
            'name_en' => 'Beach',
            'name_dv' => 'ބީޗް',
            'alt_en' => 'The sea',
            'alt_dv' => 'ކަނޑު',
            'caption_dv' => 'ފޮޓޯ: އަޙްމަދު',
        ])->save();

        $media = TranslatableMedia::query()->findOrFail($media->id);

        $this->assertEquals('Beach', $media->name);
        $this->assertEquals('The sea', $media->alt);
        $this->assertEquals('ބީޗް', $media->name_dv);
        $this->assertEquals('ކަނޑު', $media->alt_dv);
        $this->assertEquals('ފޮޓޯ: އަޙްމަދު', $media->caption_dv);

        Languages::setCurrentLocale('dv');
        $this->assertEquals('ބީޗް', $media->name);
        $this->assertEquals('ކަނޑު', $media->altText());
    }

    #[Test]
    public function uploads_and_updates_take_a_value_per_language(): void
    {
        $user = $this->userWith(['view_media', 'edit_media']);

        $id = $this->actingAs($user)
            ->postJson('/media', ['file' => $this->fakeUpload(), 'name_en' => 'Beach', 'name_dv' => 'ބީޗް', 'alt_dv' => 'ކަނޑު'])
            ->assertCreated()
            ->assertJsonPath('data.translations.name_dv', 'ބީޗް')
            ->json('data.id');

        $this->actingAs($user)
            ->patchJson("/media/{$id}", ['name_en' => 'Beach', 'caption_dv' => 'ހުޅުމާލެ'])
            ->assertOk()
            ->assertJsonPath('data.translations.caption_dv', 'ހުޅުމާލެ')
            ->assertJsonPath('data.translations.alt_dv', 'ކަނޑު');

        $this->actingAs($user)
            ->patchJson("/media/{$id}", ['name_en' => '', 'name_dv' => 'ބީޗް'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name_en');
    }

    #[Test]
    public function the_edit_page_has_an_input_per_language(): void
    {
        $user = $this->userWith(['view_media', 'edit_media']);
        $media = $this->libraryMedia($user, name: 'Beach');

        $this->actingAs($user)
            ->get("/media/{$media->id}/edit")
            ->assertOk()
            ->assertSee('name="name_en"', false)
            ->assertSee('name="name_dv"', false)
            ->assertSee('name="alt_dv"', false);

        config(['mediapicker.views' => 'inertia']);

        $this->actingAs($user)
            ->get("/media/{$media->id}/edit")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('translatable', true)
                ->where('media.translations.name_en', 'Beach')
                ->has('media.translations.name_dv'));
    }

    #[Test]
    public function it_is_off_without_languages(): void
    {
        $this->withoutTranslatable(function () {
            $this->assertFalse(Translations::enabled());
            $this->assertNull(Translations::forMedia(new TranslatableMedia));
        });
    }
}
