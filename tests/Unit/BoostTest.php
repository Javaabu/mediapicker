<?php

namespace Javaabu\Mediapicker\Tests\Unit;

use Javaabu\Mediapicker\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class BoostTest extends TestCase
{
    #[Test]
    public function it_ships_a_boost_guideline_and_skill(): void
    {
        $root = __DIR__ . '/../../resources/boost';

        $this->assertFileExists("{$root}/guidelines/core.blade.php");

        $skill = file_get_contents("{$root}/skills/javaabu-mediapicker/SKILL.md");

        $this->assertStringStartsWith("---\nname: javaabu-mediapicker\n", $skill);
        $this->assertStringContainsString('description: ', $skill);

        preg_match_all('/\]\((references\/[^)]+)\)/', $skill, $references);

        $this->assertNotEmpty($references[1]);

        foreach ($references[1] as $reference) {
            $this->assertFileExists("{$root}/skills/javaabu-mediapicker/{$reference}");
        }
    }
}
