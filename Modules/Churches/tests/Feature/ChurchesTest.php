<?php

namespace Modules\Churches\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Churches\Database\Seeders\ChurchSeeder;
use Tests\TestCase;

class ChurchesTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_list_churches_paginated(): void
    {
        $this->seed(ChurchSeeder::class);

        $response = $this->getJson(route('api.churches.index'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name', 'location', 'created_at', 'updated_at'],
                ],
                'meta' => ['current_page', 'per_page', 'total'],
            ]);

        $this->assertGreaterThanOrEqual(43, count($response->json('data')));
    }

    public function test_can_filter_churches_by_search_term(): void
    {
        $this->seed(ChurchSeeder::class);

        $response = $this->getJson(route('api.churches.index', ['search' => 'Phaneroo']));

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertNotEmpty($data);
        $this->assertEquals('Phaneroo Ministries International', $data[0]['name']);
    }
}
