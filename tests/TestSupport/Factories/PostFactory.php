<?php

namespace Javaabu\Mediapicker\Tests\TestSupport\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Javaabu\Mediapicker\Tests\TestSupport\Models\Post;

class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence,
            'body' => fake()->optional()->paragraph,
        ];
    }
}
