<?php

namespace Javaabu\Mediapicker\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Mediapicker\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_test_the_feature_is_true(): void
    {
        $this->assertTrue(true);
    }
}
