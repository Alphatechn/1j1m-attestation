<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ParticipantIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_participants_index_returns_a_paginated_datatables_response(): void
    {
        $user = User::factory()->create([
            'login' => 'pagination-test',
            'password' => Hash::make('password'),
            'is_active' => true,
            'is_delete' => false,
        ]);

        Participant::factory()->count(3)->create();

        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/participants?serverSide=1&draw=1&start=0&length=2');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('recordsTotal', 3)
            ->assertJsonPath('recordsFiltered', 3)
            ->assertJsonCount(2, 'data');
    }

    public function test_participants_index_applies_datatables_search_before_pagination(): void
    {
        $user = User::factory()->create([
            'login' => 'search-test',
            'password' => Hash::make('password'),
            'is_active' => true,
            'is_delete' => false,
        ]);

        Participant::factory()->create(['name' => 'Alice Test']);
        Participant::factory()->create(['name' => 'Bob Test']);

        $response = $this->actingAs($user)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->getJson('/participants?serverSide=1&draw=2&start=0&length=10&search[value]=Alice');

        $response->assertOk()
            ->assertJsonPath('recordsTotal', 2)
            ->assertJsonPath('recordsFiltered', 1)
            ->assertJsonPath('data.0.name', 'Alice Test');
    }
}
