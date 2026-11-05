<?php

namespace Javaabu\Mediapicker\Tests\Http;

use Inertia\Testing\AssertableInertia;
use Javaabu\Mediapicker\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class LibraryPagesTest extends TestCase
{
    use InteractsWithLibrary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpLibrary();
    }

    #[Test]
    public function it_shows_the_library_in_a_grid(): void
    {
        $user = $this->userWith(['view_media', 'edit_media', 'delete_media']);
        $media = $this->libraryMedia($user, name: 'ހުޅުމާލެ ބީޗް');

        $this->actingAs($user)
            ->get('/media')
            ->assertOk()
            ->assertSee('Media library')
            ->assertSee('ހުޅުމާލެ ބީޗް')
            ->assertSee("/media/{$media->id}/edit", false)
            ->assertSee('name="media[]"', false)
            ->assertSee('Upload media');
    }

    #[Test]
    public function it_shows_the_library_as_a_list(): void
    {
        $user = $this->userWith(['view_media']);
        $media = $this->libraryMedia($user, 'test.pdf', 'Annual report');

        $this->actingAs($user)
            ->get('/media?view=list')
            ->assertOk()
            ->assertSee('Annual report')
            ->assertSee('application/pdf')
            ->assertSee("/media/{$media->id}", false)
            ->assertDontSee('Upload media');
    }

    #[Test]
    public function it_says_when_nothing_matches_the_filters(): void
    {
        $user = $this->userWith(['view_media']);
        $this->libraryMedia($user, name: 'Logo');

        $this->actingAs($user)
            ->get('/media?search=' . urlencode('ބީޗް'))
            ->assertOk()
            ->assertSee('No media matches these filters.')
            ->assertSee('Media matching');
    }

    #[Test]
    public function it_sorts_with_the_combined_sort_field(): void
    {
        $user = $this->userWith(['view_media']);
        $this->libraryMedia($user, name: 'Bravo');
        $this->libraryMedia($user, name: 'Alpha');

        $this->actingAs($user)
            ->get('/media?sort=name:asc')
            ->assertOk()
            ->assertSeeInOrder(['Alpha', 'Bravo']);
    }

    #[Test]
    public function it_shows_the_upload_page(): void
    {
        $user = $this->userWith(['view_media', 'edit_media']);

        $this->actingAs($user)
            ->get('/media/create')
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="file"', false)
            ->assertSee('name="alt"', false);
    }

    #[Test]
    public function it_shows_the_edit_page_with_the_details_form_and_cropper(): void
    {
        $user = $this->userWith(['view_media', 'edit_media', 'delete_media']);
        $media = $this->libraryMedia($user, name: 'ބީޗް', custom_properties: ['alt' => 'ކަނޑު', 'width' => 340, 'height' => 280]);

        $this->actingAs($user)
            ->get("/media/{$media->id}/edit")
            ->assertOk()
            ->assertSee('value="ބީޗް"', false)
            ->assertSee('value="ކަނޑު"', false)
            ->assertSee('name="caption"', false)
            ->assertSee('data-mediapicker-cropper', false)
            ->assertSee('Wide (16:9)')
            ->assertSee('Delete');
    }

    #[Test]
    public function it_does_not_show_the_cropper_for_other_files(): void
    {
        $user = $this->userWith(['view_media', 'edit_media']);
        $media = $this->libraryMedia($user, 'test.pdf');

        $this->actingAs($user)
            ->get("/media/{$media->id}/edit")
            ->assertOk()
            ->assertDontSee('data-mediapicker-cropper', false);
    }

    #[Test]
    public function it_shows_a_media(): void
    {
        $user = $this->userWith(['view_media']);
        $media = $this->libraryMedia($user, name: 'ބީޗް', custom_properties: ['caption' => 'ފޮޓޯ: އަޙްމަދު', 'width' => 340, 'height' => 280]);

        $this->actingAs($user)
            ->get("/media/{$media->id}")
            ->assertOk()
            ->assertSee('ބީޗް')
            ->assertSee('ފޮޓޯ: އަޙްމަދު')
            ->assertSee('340 × 280')
            ->assertSee($media->getUrl())
            ->assertDontSee("/media/{$media->id}/edit", false);
    }

    #[Test]
    public function it_renders_the_inertia_pages(): void
    {
        config(['mediapicker.views' => 'inertia']);

        $user = $this->userWith(['view_media', 'edit_media']);
        $media = $this->libraryMedia($user, name: 'ބީޗް');

        $this->actingAs($user)
            ->get('/media')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('mediapicker::Media/Index')
                ->where('media_items.data.0.name', 'ބީޗް')
                ->where('filters.view', 'grid')
                ->where('can.create', true)
                ->where('urls.store', url('/media'))
                ->has('file_types', 5)
                ->has('crop_presets', 5)
                ->where('strings.media_library', 'Media library'));

        $this->actingAs($user)
            ->get("/media/{$media->id}/edit")
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('mediapicker::Media/Edit')
                ->where('media.id', $media->id)
                ->where('urls.crop', url("/media/{$media->id}/crops/{preset}"))
                ->has('form'));

        $this->actingAs($user)
            ->get("/media/{$media->id}")
            ->assertInertia(fn (AssertableInertia $page) => $page->component('mediapicker::Media/Show'));

        $this->actingAs($user)
            ->get('/media/create')
            ->assertInertia(fn (AssertableInertia $page) => $page->component('mediapicker::Media/Create'));
    }
}
