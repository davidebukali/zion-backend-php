<?php

namespace Modules\Interactions\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CommentLikeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = \Modules\Interactions\Models\CommentLike::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}

