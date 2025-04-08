<?php

namespace Javaabu\Mediapicker\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Javaabu\Mediapicker\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AttachmentTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_can_test_it_is_true(): void
    {
        $this->assertTrue(true);
    }
}
