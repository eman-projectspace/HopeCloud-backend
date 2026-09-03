<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MyImpactTest extends TestCase
{
    use RefreshDatabase;

    public function test_impact_aggregates_only_the_authenticated_users_donations(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $oldestDonation = Donation::factory()->forUser($user)->inCategory('Food')->withQuantity(4)->create([
            'created_at' => now()->subMinutes(6),
            'updated_at' => now()->subMinutes(6),
        ]);
        Donation::factory()->forUser($user)->inCategory('Food')->withQuantity(3)->create([
            'created_at' => now()->subMinutes(5),
            'updated_at' => now()->subMinutes(5),
        ]);
        Donation::factory()->forUser($user)->inCategory('Clothing')->withQuantity(5)->create([
            'created_at' => now()->subMinutes(4),
            'updated_at' => now()->subMinutes(4),
        ]);
        Donation::factory()->forUser($user)->inCategory('Books')->withQuantity(1)->create([
            'created_at' => now()->subMinutes(3),
            'updated_at' => now()->subMinutes(3),
        ]);
        Donation::factory()->forUser($user)->inCategory('Books')->withQuantity(2)->create([
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);
        Donation::factory()->forUser($user)->inCategory('Clothing')->withQuantity(1)->create([
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);
        $otherDonation = Donation::factory()->forUser($otherUser)->inCategory('Food')->withQuantity(99)->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/my-impact')
            ->assertOk()
            ->assertJsonPath('impact.total_donations', 6)
            ->assertJsonPath('impact.total_items', 16)
            ->assertJsonPath('impact.impact_score', 100)
            ->assertJsonPath('impact.impact_growth', 0)
            ->assertJsonCount(5, 'impact.recent_donations')
            ->assertJsonFragment(['category' => 'Food', 'items' => 7])
            ->assertJsonFragment(['category' => 'Clothing', 'items' => 6])
            ->assertJsonFragment(['category' => 'Books', 'items' => 3])
            ->assertJsonMissing(['id' => $oldestDonation->id])
            ->assertJsonMissing(['id' => $otherDonation->id]);
    }

    public function test_impact_returns_zero_values_for_a_user_without_donations(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/my-impact')
            ->assertOk()
            ->assertJsonPath('impact.total_donations', 0)
            ->assertJsonPath('impact.total_items', 0)
            ->assertJsonPath('impact.impact_score', 0)
            ->assertJsonPath('impact.impact_growth', 0)
            ->assertJsonPath('impact.impact_areas', [])
            ->assertJsonPath('impact.recent_donations', []);
    }

    public function test_unauthenticated_user_cannot_view_impact(): void
    {
        $this->getJson('/api/my-impact')->assertUnauthorized();
    }
}
