<?php

namespace Modules\Interactions\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Interactions\Models\Bookmark;

class BookmarkFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Bookmark::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}
