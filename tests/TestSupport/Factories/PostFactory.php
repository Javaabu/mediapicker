<?php

namespace Javaabu\Mediapicker\Tests\TestSupport\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Javaabu\Mediapicker\Tests\TestSupport\Models\Post;

class PostFactory extends Factory
{
    protected $model = Post::class;

    protected function newInstance(array $arguments = [])
    {
        $new_instance = parent::newInstance($arguments);

        $new_instance->setModel($this->model);

        return $new_instance;
    }

    /**
     * @param class-string<Model> $model_class
     */
    public function setModel(string $model_class): self
    {
        $this->model = $model_class;

        return $this;
    }

    public function definition(): array
    {
        return [
            'title' => fake()->sentence,
            'body' => fake()->optional()->paragraph,
        ];
    }
}
