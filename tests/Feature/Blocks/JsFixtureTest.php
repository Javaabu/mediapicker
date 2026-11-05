<?php

namespace Javaabu\Mediapicker\Tests\Feature\Blocks;

use Javaabu\BlockEditor\Facades\BlockEditor;
use Javaabu\Mediapicker\Tests\Http\InteractsWithLibrary;
use Javaabu\Mediapicker\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The editor config the JS block tests use. Run with MEDIAPICKER_UPDATE_FIXTURES=1 to write it.
 */
class JsFixtureTest extends TestCase
{
    use InteractsWithLibrary;

    protected const string PATH = __DIR__ . '/../../js/fixtures/editor-config.json';

    #[Test]
    public function the_js_editor_config_fixture_is_up_to_date(): void
    {
        $json = json_encode(BlockEditor::editorConfig(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

        if (getenv('MEDIAPICKER_UPDATE_FIXTURES')) {
            file_put_contents(static::PATH, $json);
        }

        $this->assertJsonStringEqualsJsonString($json, file_get_contents(static::PATH));
    }
}
