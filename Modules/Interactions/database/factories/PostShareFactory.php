<?php

namespace Modules\Interactions\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Interactions\Models\PostShare;

class PostShareFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = PostShare::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}
