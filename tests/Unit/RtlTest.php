<?php

namespace Javaabu\Mediapicker\Tests\Unit;

use Javaabu\Mediapicker\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class RtlTest extends TestCase
{
    /**
     * @return iterable<int, string>
     */
    protected function files(string $directory): iterable
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . "/../../{$directory}", RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            yield $file->getPathname();
        }
    }

    #[Test]
    public function the_views_and_components_use_only_logical_utilities(): void
    {
        $physical = '/(?<![\w-])(ml|mr|pl|pr|left|right|text-left|text-right|rounded-l|rounded-r|border-l|border-r|float-left|float-right)-[\w\[]*/';

        foreach (['resources/views', 'resources/js'] as $directory) {
            foreach ($this->files($directory) as $path) {
                preg_match_all('/class(?:Name)?="([^"]*)"/', file_get_contents($path), $classes);

                foreach ($classes[1] as $class_list) {
                    $this->assertDoesNotMatchRegularExpression($physical, $class_list, "Use logical utilities (ms-, pe-, start-) in {$path}");
                }
            }
        }
    }

    #[Test]
    public function the_stylesheet_uses_only_logical_properties(): void
    {
        $css = file_get_contents(__DIR__ . '/../../resources/css/mediapicker.css');

        $this->assertDoesNotMatchRegularExpression('/(?<![\w-])(margin|padding|border)-(left|right)\s*:|(?<![\w-])(left|right)\s*:|text-align:\s*(left|right)|float:\s*(left|right)/', $css);
    }

    #[Test]
    public function free_text_inputs_support_thaana(): void
    {
        $create = file_get_contents(__DIR__ . '/../../resources/views/media/create.blade.php');

        $this->assertEquals(4, substr_count($create, ' thaana'));
        $this->assertStringContainsString('dir="auto"', file_get_contents(__DIR__ . '/../../resources/views/media/partials/filters.blade.php'));
    }
}
