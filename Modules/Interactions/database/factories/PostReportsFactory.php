<?php

namespace Modules\Interactions\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Interactions\Models\PostReports;

class PostReportsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = PostReports::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [];
    }
}
