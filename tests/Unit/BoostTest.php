<?php

namespace Javaabu\Mediapicker\Tests\Unit;

use FilesystemIterator;
use Javaabu\Mediapicker\Tests\TestCase;
use Laravel\Boost\Concerns\RendersBladeGuidelines;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class BoostTest extends TestCase
{
    /**
     * Renders a guideline the way Laravel Boost does for an app's AGENTS.md/CLAUDE.md.
     * Boost leaves out a file it can't render, so a failure renders as ''.
     */
    protected function renderLikeBoost(string $path): string
    {
        $renderer = new class
        {
            use RendersBladeGuidelines;

            public function render(string $path): string
            {
                return $this->renderBladeFile($path);
            }
        };

        return $renderer->render($path);
    }

    /** @return array<int, string> */
    protected function boostFiles(string $pattern): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/../../resources/boost', FilesystemIterator::SKIP_DOTS));

        foreach ($iterator as $file) {
            if (preg_match($pattern, $file->getFilename())) {
                $files[] = $file->getRealPath();
            }
        }

        sort($files);

        return $files;
    }

    #[Test]
    public function boost_renders_the_guideline_with_its_blade_examples_intact(): void
    {
        $guideline = $this->renderLikeBoost(__DIR__ . '/../../resources/boost/guidelines/core.blade.php');

        $this->assertStringContainsString('## javaabu/mediapicker', $guideline);
        $this->assertStringContainsString('`<x-mediapicker::picker>` (Blade)', $guideline);
        $this->assertStringContainsString('`<x-mediapicker::image>`', $guideline);
        $this->assertStringContainsString('`@javaabu/mediapicker` (React)', $guideline);
        $this->assertStringContainsString("`\$model->syncAttachmentsFromRequest('collection')`", $guideline);
        $this->assertStringNotContainsString('javaabu/mediapicker guidelines for AI code assistants', $guideline);
        $this->assertStringNotContainsString('@verbatim', $guideline);
    }

    #[Test]
    public function boost_renders_every_blade_file_the_package_ships(): void
    {
        $files = $this->boostFiles('/\.blade\.php$/');

        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $this->assertNotSame('', trim($this->renderLikeBoost($file)), "Boost can't render {$file}");
        }
    }

    #[Test]
    public function the_skill_ships_as_markdown_so_boost_copies_its_blade_examples_as_written(): void
    {
        $root = __DIR__ . '/../../resources/boost';
        $skill = "{$root}/skills/javaabu-mediapicker";

        // Boost renders only .blade.php skill files; .md files are copied unchanged.
        $this->assertSame([], $this->boostFiles('/^(?!core\.blade\.php$).*\.blade\.php$/'));
        $this->assertFileExists("{$skill}/SKILL.md");

        $contents = file_get_contents("{$skill}/SKILL.md");

        $this->assertStringStartsWith("---\nname: javaabu-mediapicker\n", $contents);
        $this->assertStringContainsString('description: ', $contents);
        $this->assertStringContainsString('<x-mediapicker::picker', $contents);

        preg_match_all('/\]\((references\/[^)]+)\)/', $contents, $references);

        $this->assertNotEmpty($references[1]);

        foreach ($references[1] as $reference) {
            $this->assertFileExists("{$skill}/{$reference}");
        }
    }
}
