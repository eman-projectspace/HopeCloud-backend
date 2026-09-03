<?php

namespace Tests\Feature;

use App\Models\Donation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DonationApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_a_donation_with_optional_fields_omitted_or_blank(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/donations', [
            'title' => 'Winter coats',
            'description' => '',
            'category' => 'Clothing',
            'condition' => '',
            'location' => '',
            'quantity' => 3,
            'notes' => '',
            'status' => 'approved',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Donation created successfully')
            ->assertJsonPath('donation.title', 'Winter coats')
            ->assertJsonPath('donation.quantity', 3)
            ->assertJsonPath('donation.status', 'submitted')
            ->assertJsonPath('donation.description', null)
            ->assertJsonPath('donation.condition', null)
            ->assertJsonPath('donation.location', null);

        $this->assertDatabaseHas('donations', [
            'user_id' => $user->id,
            'title' => 'Winter coats',
            'status' => 'submitted',
            'description' => null,
            'condition' => null,
            'location' => null,
        ]);
    }

    public function test_authenticated_user_can_create_a_donation_with_an_image(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->post('/api/donations', [
            'title' => 'School supplies',
            'category' => 'Books',
            'quantity' => 2,
            'image' => UploadedFile::fake()->create('photo.jpg', 120, 'image/jpeg'),
        ]);

        $response->assertCreated();

        $imagePath = $response->json('donation.image');

        $this->assertStringStartsWith('donations/', $imagePath);
        Storage::disk('public')->assertExists($imagePath);
    }

    public function test_creation_validates_required_donation_fields(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/donations', [
            'title' => '',
            'category' => '',
            'quantity' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'category', 'quantity']);
    }

    public function test_unauthenticated_user_cannot_create_a_donation(): void
    {
        $this->postJson('/api/donations', [
            'title' => 'Blankets',
            'category' => 'Clothing',
            'quantity' => 1,
        ])->assertUnauthorized();
    }

    public function test_index_returns_only_the_authenticated_users_donations_in_newest_order(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $older = Donation::factory()->forUser($user)->create([
            'title' => 'Older donation',
            'created_at' => now()->subMinutes(2),
            'updated_at' => now()->subMinutes(2),
        ]);
        $newer = Donation::factory()->forUser($user)->create([
            'title' => 'Newer donation',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);
        Donation::factory()->forUser($otherUser)->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/donations')
            ->assertOk()
            ->assertJsonCount(2, 'donations')
            ->assertJsonPath('donations.0.id', $newer->id)
            ->assertJsonPath('donations.1.id', $older->id);
    }

    public function test_user_can_view_their_own_donation_but_not_another_users_donation(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $donation = Donation::factory()->forUser($user)->create();
        $otherDonation = Donation::factory()->forUser($otherUser)->create();

        Sanctum::actingAs($user);

        $this->getJson("/api/donations/{$donation->id}")
            ->assertOk()
            ->assertJsonPath('donation.id', $donation->id);

        $this->getJson("/api/donations/{$otherDonation->id}")->assertForbidden();
    }

    public function test_user_can_partially_update_quantity_date_and_notes(): void
    {
        $user = User::factory()->create();
        $donation = Donation::factory()->forUser($user)->create([
            'quantity' => 1,
            'preferred_date' => null,
            'notes' => null,
        ]);
        Sanctum::actingAs($user);

        $this->putJson("/api/donations/{$donation->id}", [
            'quantity' => 4,
            'preferred_date' => '2026-09-10',
            'notes' => 'Available after 5pm.',
        ])
            ->assertOk()
            ->assertJsonPath('donation.quantity', 4)
            ->assertJsonPath('donation.preferred_date', '2026-09-10')
            ->assertJsonPath('donation.notes', 'Available after 5pm.');

        $this->assertDatabaseHas('donations', [
            'id' => $donation->id,
            'quantity' => 4,
            'preferred_date' => '2026-09-10',
            'notes' => 'Available after 5pm.',
        ]);
    }

    public function test_multipart_post_replaces_an_existing_image_after_a_successful_update(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $oldImage = 'donations/old-photo.jpg';
        Storage::disk('public')->put($oldImage, 'old image');
        $donation = Donation::factory()->forUser($user)->create(['image' => $oldImage]);
        Sanctum::actingAs($user);

        $response = $this->post("/api/donations/{$donation->id}", [
            'image' => UploadedFile::fake()->create('replacement.jpg', 120, 'image/jpeg'),
        ]);

        $response->assertOk();

        $newImage = $response->json('donation.image');

        $this->assertStringStartsWith('donations/', $newImage);
        $this->assertNotSame($oldImage, $newImage);
        Storage::disk('public')->assertExists($newImage);
        Storage::disk('public')->assertMissing($oldImage);
    }

    public function test_update_rejects_invalid_fields_and_invalid_image_uploads(): void
    {
        $user = User::factory()->create();
        $donation = Donation::factory()->forUser($user)->create();
        Sanctum::actingAs($user);

        $this->putJson("/api/donations/{$donation->id}", [
            'title' => '',
            'quantity' => 0,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title', 'quantity']);

        $this->post("/api/donations/{$donation->id}", [
            'image' => UploadedFile::fake()->create('document.pdf', 120, 'application/pdf'),
        ], [
            'Accept' => 'application/json',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    public function test_user_cannot_update_another_users_donation(): void
    {
        $user = User::factory()->create();
        $otherDonation = Donation::factory()->forUser(User::factory()->create())->create();
        Sanctum::actingAs($user);

        $this->putJson("/api/donations/{$otherDonation->id}", [
            'quantity' => 5,
        ])->assertForbidden();
    }

    public function test_user_can_delete_their_own_donation_but_not_another_users_donation(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $donation = Donation::factory()->forUser($user)->create();
        $otherDonation = Donation::factory()->forUser($otherUser)->create();
        Sanctum::actingAs($user);

        $this->deleteJson("/api/donations/{$otherDonation->id}")->assertForbidden();

        $this->deleteJson("/api/donations/{$donation->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Donation deleted successfully');

        $this->assertDatabaseMissing('donations', ['id' => $donation->id]);
        $this->assertDatabaseHas('donations', ['id' => $otherDonation->id]);
    }
}
