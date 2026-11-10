<?php

namespace Javaabu\Mediapicker\Tests\Http;

use Javaabu\Mediapicker\Models\Media;
use Javaabu\Mediapicker\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class MediaApiTest extends TestCase
{
    use InteractsWithLibrary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpLibrary();
    }

    #[Test]
    public function it_lists_the_library_as_json(): void
    {
        $user = $this->userWith(['view_media']);
        $media = $this->libraryMedia($user, name: 'މާލެ ބީޗް', custom_properties: ['alt' => 'ކަނޑު']);

        $this->actingAs($user)
            ->getJson('/media')
            ->assertOk()
            ->assertJsonPath('data.0.id', $media->id)
            ->assertJsonPath('data.0.name', 'މާލެ ބީޗް')
            ->assertJsonPath('data.0.alt', 'ކަނޑު')
            ->assertJsonPath('data.0.type', 'image')
            ->assertJsonPath('data.0.icon', 'image')
            ->assertJsonPath('data.0.links.edit', url("/media/{$media->id}/edit"))
            ->assertJsonStructure([
                'data' => [['id', 'uuid', 'name', 'file_name', 'mime_type', 'size', 'url', 'thumb', 'preview', 'width', 'height', 'crops', 'can', 'links']],
                'links',
                'meta' => ['current_page', 'last_page', 'total'],
            ]);
    }

    #[Test]
    public function it_only_lists_the_users_own_media_without_view_others_media(): void
    {
        $user = $this->userWith(['view_media']);
        $other = $this->userWith([]);

        $own = $this->libraryMedia($user);
        $this->libraryMedia($other);

        $this->actingAs($user)
            ->getJson('/media')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id);
    }

    #[Test]
    public function it_lists_others_media_with_view_others_media(): void
    {
        $user = $this->userWith(['view_media', 'view_others_media']);
        $other = $this->userWith([]);

        $this->libraryMedia($user);
        $this->libraryMedia($other);

        $this->actingAs($user)
            ->getJson('/media')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    #[Test]
    public function it_can_list_only_the_users_own_uploads(): void
    {
        $user = $this->userWith(['view_media', 'view_others_media']);
        $other = $this->userWith([]);

        $own = $this->libraryMedia($user);
        $this->libraryMedia($other);

        $this->actingAs($user)
            ->getJson('/media?owner=mine')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id);
    }

    #[Test]
    public function it_filters_the_library_by_search_and_type(): void
    {
        $user = $this->userWith(['view_media']);

        $beach = $this->libraryMedia($user, 'test.jpg', 'ހުޅުމާލެ ބީޗް');
        $this->libraryMedia($user, 'test.png', 'Logo');
        $report = $this->libraryMedia($user, 'test.pdf', 'Annual report');

        $this->actingAs($user)
            ->getJson('/media?search=' . urlencode('ބީޗް'))
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $beach->id);

        $this->actingAs($user)
            ->getJson('/media?type=document')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $report->id);

        $this->actingAs($user)
            ->getJson('/media?type[]=image&type[]=document')
            ->assertJsonCount(3, 'data');
    }

    #[Test]
    public function it_sorts_and_paginates_the_library(): void
    {
        $user = $this->userWith(['view_media']);

        $this->libraryMedia($user, name: 'B');
        $this->libraryMedia($user, name: 'A');
        $this->libraryMedia($user, name: 'C');

        $this->actingAs($user)
            ->getJson('/media?orderby=name&order=asc&per_page=2')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'A')
            ->assertJsonPath('data.1.name', 'B')
            ->assertJsonPath('meta.last_page', 2);
    }

    #[Test]
    public function it_uploads_a_file_to_the_users_library(): void
    {
        $user = $this->userWith(['view_media', 'edit_media']);

        $this->actingAs($user)
            ->postJson('/media', [
                'file' => $this->fakeUpload('test.jpg'),
                'name' => 'ރީތި ފޮޓޯ',
                'description' => 'ހުޅުމާލޭގެ ބީޗް',
                'alt' => 'ބީޗް',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'ރީތި ފޮޓޯ')
            ->assertJsonPath('data.description', 'ހުޅުމާލޭގެ ބީޗް')
            ->assertJsonPath('data.width', 340)
            ->assertJsonPath('data.height', 280);

        $media = Media::query()->sole();

        $this->assertEquals($user->getMorphClass(), $media->model_type);
        $this->assertEquals($user->id, $media->model_id);
        $this->assertEquals('mediapicker', $media->collection_name);
        $this->assertEquals('ބީޗް', $media->alt);
    }

    #[Test]
    public function it_names_uploads_after_the_file_by_default(): void
    {
        $user = $this->userWith(['edit_media']);

        $this->actingAs($user)
            ->postJson('/media', ['file' => $this->fakeUpload('test.pdf', 'Annual report.pdf')])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Annual report')
            ->assertJsonPath('data.type', 'document')
            ->assertJsonPath('data.width', null);
    }

    #[Test]
    public function it_redirects_to_the_edit_page_after_an_upload_from_a_form(): void
    {
        $user = $this->userWith(['view_media', 'edit_media']);

        $response = $this->actingAs($user)
            ->post('/media', ['file' => $this->fakeUpload()]);

        $media = Media::query()->sole();

        $response->assertRedirect("/media/{$media->id}/edit")
            ->assertSessionHas('success');
    }

    #[Test]
    public function it_limits_uploads_to_the_requested_type(): void
    {
        $user = $this->userWith(['edit_media']);

        $this->actingAs($user)
            ->postJson('/media', ['file' => $this->fakeUpload('test.pdf'), 'type' => 'image'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->actingAs($user)
            ->postJson('/media', ['file' => $this->fakeUpload('test.pdf'), 'type' => ['image', 'document']])
            ->assertCreated();
    }

    #[Test]
    public function it_rejects_files_the_library_does_not_accept(): void
    {
        $user = $this->userWith(['edit_media']);

        $this->actingAs($user)
            ->postJson('/media', ['file' => $this->fakeUpload('test.svg')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseEmpty('media');
    }

    #[Test]
    public function it_saves_crops_sent_with_an_upload(): void
    {
        $user = $this->userWith(['edit_media']);

        $this->actingAs($user)
            ->postJson('/media', [
                'file' => $this->fakeUpload(),
                'crops' => ['wide' => ['x' => 0, 'y' => 45.4, 'width' => 340, 'height' => 191.25]],
            ])
            ->assertCreated()
            ->assertJsonPath('data.crops.wide', ['x' => 0, 'y' => 45, 'width' => 340, 'height' => 191, 'rotation' => 0]);

        $this->actingAs($user)
            ->postJson('/media', [
                'file' => $this->fakeUpload(),
                'crops' => ['unknown' => ['x' => 0, 'y' => 0, 'width' => 10, 'height' => 10]],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('crops');
    }

    #[Test]
    public function it_shows_a_media_as_json(): void
    {
        $user = $this->userWith(['view_media']);
        $media = $this->libraryMedia($user);

        $this->actingAs($user)
            ->getJson("/media/{$media->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $media->id)
            ->assertJsonPath('data.can.update', false);
    }

    #[Test]
    public function it_updates_the_details_of_a_media(): void
    {
        $user = $this->userWith(['view_media', 'edit_media']);
        $media = $this->libraryMedia($user);

        $this->actingAs($user)
            ->patchJson("/media/{$media->id}", [
                'name' => 'ހުޅުމާލެ',
                'description' => 'ތަފްސީލު',
                'alt' => 'ބީޗް',
                'caption' => 'ފޮޓޯ: އަޙްމަދު',
            ])
            ->assertOk()
            ->assertJsonPath('data.name', 'ހުޅުމާލެ')
            ->assertJsonPath('data.caption', 'ފޮޓޯ: އަޙްމަދު');

        $media->refresh();

        $this->assertEquals('ހުޅުމާލެ', $media->name);
        $this->assertEquals('ތަފްސީލު', $media->description);
        $this->assertEquals('ބީޗް', $media->alt);
    }

    #[Test]
    public function it_redirects_back_to_the_edit_page_after_saving_details(): void
    {
        $user = $this->userWith(['view_media', 'edit_media']);
        $media = $this->libraryMedia($user);

        $this->actingAs($user)
            ->patch("/media/{$media->id}", ['name' => 'New name'])
            ->assertRedirect("/media/{$media->id}/edit")
            ->assertSessionHas('success');
    }

    #[Test]
    public function it_requires_a_name_when_one_is_sent(): void
    {
        $user = $this->userWith(['edit_media']);
        $media = $this->libraryMedia($user);

        $this->actingAs($user)
            ->patchJson("/media/{$media->id}", ['name' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
    }

    #[Test]
    public function it_deletes_a_media(): void
    {
        $user = $this->userWith(['view_media', 'delete_media']);
        $media = $this->libraryMedia($user);

        $this->actingAs($user)
            ->deleteJson("/media/{$media->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    #[Test]
    public function it_redirects_to_the_library_after_deleting_from_a_page(): void
    {
        $user = $this->userWith(['view_media', 'delete_media']);
        $media = $this->libraryMedia($user);

        $this->actingAs($user)
            ->delete("/media/{$media->id}")
            ->assertRedirect('/media');
    }

    #[Test]
    public function it_bulk_deletes_media(): void
    {
        $user = $this->userWith(['view_media', 'delete_media']);
        $first = $this->libraryMedia($user);
        $second = $this->libraryMedia($user);
        $kept = $this->libraryMedia($user);

        $this->actingAs($user)
            ->put('/media?view=list', ['action' => 'delete', 'media' => [$first->id, $second->id]])
            ->assertRedirect('/media?view=list')
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('media', ['id' => $first->id]);
        $this->assertDatabaseMissing('media', ['id' => $second->id]);
        $this->assertDatabaseHas('media', ['id' => $kept->id]);
    }

    #[Test]
    public function it_returns_the_deleted_ids_of_a_bulk_delete_as_json(): void
    {
        $user = $this->userWith(['view_media', 'delete_media']);
        $media = $this->libraryMedia($user);

        $this->actingAs($user)
            ->putJson('/media', ['action' => 'delete', 'media' => [$media->id]])
            ->assertOk()
            ->assertJson(['deleted' => [$media->id]]);
    }

    #[Test]
    public function it_validates_bulk_actions(): void
    {
        $user = $this->userWith(['view_media', 'delete_media']);

        $this->actingAs($user)
            ->putJson('/media', ['action' => 'archive', 'media' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['action', 'media']);
    }
}
