<?php

namespace Javaabu\Mediapicker\Tests\Http;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\Routing\MediapickerRoutes;
use Javaabu\Mediapicker\Tests\TestCase;
use Javaabu\Mediapicker\Tests\TestSupport\Http\CustomMediaController;
use PHPUnit\Framework\Attributes\Test;

class OverridingTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_registers_the_library_routes_with_a_macro(): void
    {
        Route::mediapicker();
        Route::getRoutes()->refreshNameLookups();

        foreach (['index', 'create', 'store', 'bulk', 'show', 'edit', 'update', 'destroy', 'crop'] as $name) {
            $this->assertTrue(Route::has("media.{$name}"), "media.{$name} is missing");
        }

        $this->assertEquals(['auth'], Route::getRoutes()->getByName('media.index')->gatherMiddleware());
    }

    #[Test]
    public function it_takes_a_prefix_name_guard_and_middleware(): void
    {
        Route::mediapicker([
            'guard' => 'web_admin',
            'prefix' => 'admin/files',
            'name' => 'admin.files',
            'middleware' => ['web', 'auth:web_admin'],
        ]);
        Route::getRoutes()->refreshNameLookups();

        $route = Route::getRoutes()->getByName('admin.files.edit');

        $this->assertEquals('admin/files/{media}/edit', $route->uri());
        $this->assertEquals(['web', 'auth:web_admin'], $route->gatherMiddleware());
        $this->assertEquals('web_admin', $route->getAction(MediapickerRoutes::GUARD_ACTION));
        $this->assertEquals('admin.files', MediapickerRoutes::nameFor('web_admin'));
    }

    #[Test]
    public function it_uses_the_controller_from_the_config(): void
    {
        config(['mediapicker.media_controller' => CustomMediaController::class]);

        Route::mediapicker(['middleware' => []]);

        $this->getJson('/media')->assertExactJson(['custom' => true]);
    }

    #[Test]
    public function it_uses_the_controller_given_to_the_macro(): void
    {
        Route::mediapicker(['middleware' => [], 'controller' => CustomMediaController::class]);

        $this->getJson('/media')->assertExactJson(['custom' => true]);
    }

    #[Test]
    public function it_builds_urls_for_the_default_library(): void
    {
        Route::mediapicker(['prefix' => 'files', 'name' => 'files']);
        Route::getRoutes()->refreshNameLookups();

        $this->assertEquals(url('/files/create'), Mediapicker::url('create'));
        $this->assertEquals('files', Mediapicker::routeName());
    }

    #[Test]
    public function it_explains_a_missing_route(): void
    {
        $this->expectExceptionMessage('Add Route::mediapicker() to your routes.');

        Mediapicker::url('index');
    }
}
