<?php

namespace Javaabu\Mediapicker\Tests\Http;

use Illuminate\Support\Facades\Gate;
use Javaabu\Mediapicker\Models\Media;
use Javaabu\Mediapicker\Policies\MediaPolicy;
use Javaabu\Mediapicker\Tests\TestCase;
use Javaabu\Mediapicker\Tests\TestSupport\Policies\DenyAllPolicy;
use Javaabu\Permissions\Models\Permission;
use PHPUnit\Framework\Attributes\Test;

class AuthorizationTest extends TestCase
{
    use InteractsWithLibrary;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpLibrary();
    }

    #[Test]
    public function it_registers_the_media_permissions_for_every_guard(): void
    {
        foreach (['web', 'web_admin'] as $guard) {
            foreach (self::ALL as $permission) {
                $this->assertDatabaseHas('permissions', ['name' => $permission, 'guard_name' => $guard, 'model' => 'media']);
            }
        }

        $this->assertEquals('View the media library', Permission::findByName('view_media', 'web')->description);
    }

    #[Test]
    public function it_registers_the_media_policy(): void
    {
        $this->assertInstanceOf(MediaPolicy::class, Gate::getPolicyFor(Media::class));
    }

    #[Test]
    public function it_requires_signing_in(): void
    {
        $this->getJson('/media')->assertUnauthorized();
        $this->get('/media')->assertRedirect();
    }

    #[Test]
    public function it_requires_view_media_to_see_the_library(): void
    {
        $user = $this->userWith([]);

        $this->actingAs($user)->getJson('/media')->assertForbidden();
    }

    #[Test]
    public function it_requires_edit_media_to_upload(): void
    {
        $user = $this->userWith(['view_media']);

        $this->actingAs($user)
            ->postJson('/media', ['file' => $this->fakeUpload()])
            ->assertForbidden();

        $this->actingAs($user)->get('/media/create')->assertForbidden();

        $this->assertDatabaseEmpty('media');
    }

    #[Test]
    public function it_does_not_show_others_media_without_view_others_media(): void
    {
        $user = $this->userWith(['view_media']);
        $media = $this->libraryMedia($this->userWith([]));

        $this->actingAs($user)->getJson("/media/{$media->id}")->assertForbidden();
    }

    #[Test]
    public function it_shows_others_media_with_view_others_media(): void
    {
        $user = $this->userWith(['view_media', 'view_others_media']);
        $media = $this->libraryMedia($this->userWith([]));

        $this->actingAs($user)->getJson("/media/{$media->id}")->assertOk();
    }

    #[Test]
    public function it_only_lets_users_edit_their_own_media_without_edit_others_media(): void
    {
        $user = $this->userWith(['view_media', 'view_others_media', 'edit_media']);
        $own = $this->libraryMedia($user);
        $others = $this->libraryMedia($this->userWith([]));

        $this->actingAs($user)->patchJson("/media/{$own->id}", ['name' => 'Mine'])->assertOk();
        $this->actingAs($user)->patchJson("/media/{$others->id}", ['name' => 'Theirs'])->assertForbidden();
        $this->actingAs($user)->get("/media/{$others->id}/edit")->assertForbidden();
    }

    #[Test]
    public function it_lets_users_edit_others_media_with_edit_others_media(): void
    {
        $user = $this->userWith(['view_media', 'view_others_media', 'edit_others_media']);
        $others = $this->libraryMedia($this->userWith([]));

        $this->actingAs($user)->patchJson("/media/{$others->id}", ['name' => 'Theirs'])->assertOk();
    }

    #[Test]
    public function it_only_lets_users_delete_their_own_media_without_delete_others_media(): void
    {
        $user = $this->userWith(['view_media', 'view_others_media', 'delete_media']);
        $own = $this->libraryMedia($user);
        $others = $this->libraryMedia($this->userWith([]));

        $this->actingAs($user)->deleteJson("/media/{$others->id}")->assertForbidden();
        $this->actingAs($user)->deleteJson("/media/{$own->id}")->assertNoContent();

        $this->assertDatabaseHas('media', ['id' => $others->id]);
    }

    #[Test]
    public function it_does_not_bulk_delete_media_the_user_may_not_delete(): void
    {
        $user = $this->userWith(['view_media', 'view_others_media', 'delete_media']);
        $own = $this->libraryMedia($user);
        $others = $this->libraryMedia($this->userWith([]));

        $this->actingAs($user)
            ->putJson('/media', ['action' => 'delete', 'media' => [$own->id, $others->id]])
            ->assertForbidden();

        $this->assertDatabaseHas('media', ['id' => $own->id]);
        $this->assertDatabaseHas('media', ['id' => $others->id]);
    }

    #[Test]
    public function it_does_not_bulk_delete_media_the_user_cannot_see(): void
    {
        $user = $this->userWith(['view_media', 'delete_media', 'delete_others_media']);
        $others = $this->libraryMedia($this->userWith([]));

        $this->actingAs($user)
            ->putJson('/media', ['action' => 'delete', 'media' => [$others->id]])
            ->assertUnprocessable();

        $this->assertDatabaseHas('media', ['id' => $others->id]);
    }

    #[Test]
    public function it_keeps_each_guards_library_separate(): void
    {
        $user = $this->userWith(self::ALL);
        $admin = $this->adminWith(self::ALL);

        $user_media = $this->libraryMedia($user);
        $admin_media = $this->libraryMedia($admin);

        $this->actingAs($admin, 'web_admin')
            ->getJson('/admin/media')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $admin_media->id)
            ->assertJsonPath('data.0.links.edit', url("/admin/media/{$admin_media->id}/edit"));

        $this->actingAs($admin, 'web_admin')->getJson("/admin/media/{$user_media->id}")->assertNotFound();
        $this->actingAs($admin, 'web_admin')->deleteJson("/admin/media/{$user_media->id}")->assertNotFound();

        $this->actingAs($user)
            ->getJson('/media')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $user_media->id);
    }

    #[Test]
    public function it_checks_the_permissions_of_the_routes_guard(): void
    {
        $admin = $this->adminWith(['view_media', 'edit_media']);

        $this->actingAs($admin, 'web_admin')
            ->postJson('/admin/media', ['file' => $this->fakeUpload()])
            ->assertCreated();

        $media = Media::query()->sole();

        $this->assertEquals($admin->getMorphClass(), $media->model_type);
        $this->assertEquals($admin->id, $media->model_id);

    }

    #[Test]
    public function users_of_another_guard_cannot_use_a_library(): void
    {
        $user = $this->userWith(self::ALL);

        $this->actingAs($user)->getJson('/admin/media')->assertUnauthorized();
    }

    #[Test]
    public function the_app_can_replace_the_policy(): void
    {
        config(['permissions.policies' => [Media::class => DenyAllPolicy::class]]);

        Gate::policy(Media::class, DenyAllPolicy::class);

        $user = $this->userWith(self::ALL);

        $this->actingAs($user)->getJson('/media')->assertForbidden();
    }
}
