<?php

namespace Javaabu\Mediapicker\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Javaabu\Manifest\Enums\NpmScopes;
use Javaabu\Manifest\Manifest;
use Javaabu\Mediapicker\Assets\Assets;
use Javaabu\Mediapicker\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Blade-only apps install what the Blade scripts import; the React and Inertia
 * packages are asked for only by apps that use Inertia (javaabu/manifest#17).
 */
class ManifestCheckTest extends TestCase
{
    /** What resources/js/blade, resources/js/cropper and resources/js/blocks import */
    protected const array BLADE = [
        'cropperjs' => '1.6.2',
        '@tiptap/core' => '3.32.3',
        'vite' => '8.3.5',
        'laravel-vite-plugin' => '3.2.0',
    ];

    protected const array REACT = [
        '@javaabu/forms' => '2.0.0',
        '@javaabu/onu' => '0.2.1',
        'react-easy-crop' => '6.2.4',
        'signature_pad' => '5.1.4',
        'media-chrome' => '4.19.3',
        'yet-another-react-lightbox' => '3.32.2',
        'uqr' => '0.1.3',
        'react' => '19.3.1',
        'react-dom' => '19.3.1',
        '@inertiajs/react' => '2.3.0',
    ];

    protected function tearDown(): void
    {
        File::delete(base_path('package.json'));
        File::deleteDirectory(base_path('node_modules'));

        parent::tearDown();
    }

    /** @param  array<string, string>  $installed */
    protected function app(array $installed): void
    {
        File::put(base_path('package.json'), json_encode(['devDependencies' => $installed]));

        foreach ($installed as $name => $version) {
            File::ensureDirectoryExists(base_path("node_modules/{$name}"));
            File::put(base_path("node_modules/{$name}/package.json"), json_encode(['name' => $name, 'version' => $version]));
        }
    }

    /**
     * The check's rows for what mediapicker needs. Other packages' rows are theirs to scope.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function check(): array
    {
        Artisan::call('manifest:check', ['--json' => true]);

        return collect(json_decode(Artisan::output(), true))
            ->filter(fn (array $row) => in_array(Assets::PACKAGE, $row['needed_by'], true))
            ->keyBy('name')
            ->all();
    }

    #[Test]
    public function it_asks_blade_only_apps_for_just_what_the_blade_scripts_import(): void
    {
        $this->app(self::BLADE);

        $rows = $this->check();

        $this->assertEqualsCanonicalizing(array_keys(self::BLADE), array_keys($rows));

        foreach ($rows as $name => $row) {
            $this->assertSame('ok', $row['status'], "{$name} isn't ok");
            $this->assertSame([NpmScopes::ASSETS->value], $row['scopes']);
        }
    }

    #[Test]
    public function it_asks_inertia_apps_for_the_react_packages_too(): void
    {
        $this->app(['@inertiajs/react' => '2.3.0', ...self::BLADE]);

        $rows = $this->check();

        $this->assertEqualsCanonicalizing([...array_keys(self::BLADE), ...array_keys(self::REACT)], array_keys($rows));
        $this->assertSame('missing', $rows['@javaabu/forms']['status']);
        $this->assertSame('missing', $rows['react-easy-crop']['status']);

        File::deleteDirectory(base_path('node_modules'));
        $this->app([...self::BLADE, ...self::REACT]);

        foreach ($this->check() as $name => $row) {
            $this->assertContains($row['status'], ['ok', 'unchecked'], "{$name} isn't ok");
        }
    }

    #[Test]
    public function it_declares_only_the_blade_imports_for_the_assets(): void
    {
        $package = app(Manifest::class)->packages()[Assets::PACKAGE];

        $this->assertSame(['cropperjs', '@tiptap/core'], array_keys($package->npmDependencies(NpmScopes::ASSETS)));
        $this->assertSame([], $package->npmPeerDependencies(NpmScopes::ASSETS));
        $this->assertSame(['react', 'react-dom', '@inertiajs/react'], array_keys($package->npmPeerDependencies(NpmScopes::PAGES)));
        $this->assertArrayHasKey('@javaabu/forms', $package->npmDependencies(NpmScopes::PAGES));
    }
}
