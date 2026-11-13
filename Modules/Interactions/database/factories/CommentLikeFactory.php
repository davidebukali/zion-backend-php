<?php

namespace Modules\Interactions\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Interactions\Models\CommentLike;

class CommentLikeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = CommentLike::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}
