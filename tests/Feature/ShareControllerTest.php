<?php

namespace Tests\Feature;

use App\Models\Share;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    /** @test */
    public function user_can_create_share_link_for_their_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/shares", [
                'permission' => 'viewer',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'trip_id',
                    'user_id',
                    'permission',
                    'token',
                    'created_at',
                    'updated_at',
                ],
            ]);

        $this->assertDatabaseHas('shares', [
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'permission' => 'viewer',
        ]);
    }

    /** @test */
    public function user_can_create_share_link_with_editor_permission()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/shares", [
                'permission' => 'editor',
            ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'permission' => 'editor',
                ],
            ]);
    }

    /** @test */
    public function share_token_is_automatically_generated()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/shares", [
                'permission' => 'viewer',
            ]);

        $response->assertCreated();
        $token = $response->json('data.token');

        $this->assertNotNull($token);
        $this->assertIsString($token);
        $this->assertGreaterThan(20, strlen($token)); // Token should be reasonably long
    }

    /** @test */
    public function user_can_list_all_shares_for_their_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        Share::factory()->count(3)->create(['trip_id' => $trip->id, 'user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$trip->id}/shares");

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'trip_id', 'user_id', 'permission', 'token', 'created_at'],
                ],
            ]);
    }

    /** @test */
    public function user_can_access_shared_trip_via_token()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        $share = Share::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
            'permission' => 'viewer',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/shares/{$share->token}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $trip->id,
                    'title' => $trip->title,
                ],
            ]);
    }

    /** @test */
    public function user_can_delete_share_link()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $share = Share::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/shares/{$share->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('shares', ['id' => $share->id]);
    }

    /** @test */
    public function share_creation_requires_authentication()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson("/api/trips/{$trip->id}/shares", [
            'permission' => 'viewer',
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function share_creation_validates_permission_enum()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/shares", [
                'permission' => 'invalid_permission',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['permission']);
    }

    /** @test */
    public function share_permission_defaults_to_viewer()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/shares", []);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'permission' => 'viewer',
                ],
            ]);
    }

    /** @test */
    public function only_trip_owner_can_create_share_link()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/shares", [
                'permission' => 'viewer',
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function only_trip_owner_can_list_share_links()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        Share::factory()->count(3)->create(['trip_id' => $trip->id, 'user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$trip->id}/shares");

        $response->assertForbidden();
    }

    /** @test */
    public function only_share_creator_can_delete_share_link()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        $share = Share::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/shares/{$share->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function share_not_found_returns_404()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/shares/99999');

        $response->assertNotFound();
    }

    /** @test */
    public function invalid_token_returns_404()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/shares/invalid-token-that-does-not-exist');

        $response->assertNotFound();
    }

    /** @test */
    public function share_list_is_paginated()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        Share::factory()->count(20)->create(['trip_id' => $trip->id, 'user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$trip->id}/shares");

        $response->assertOk()
            ->assertJsonCount(15, 'data') // Default pagination
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    /** @test */
    public function share_list_can_filter_by_permission()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        Share::factory()->count(3)->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'permission' => 'viewer',
        ]);
        Share::factory()->count(2)->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'permission' => 'editor',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$trip->id}/shares?permission=viewer");

        $response->assertOk()
            ->assertJsonCount(3, 'data');

        foreach ($response->json('data') as $share) {
            $this->assertEquals('viewer', $share['permission']);
        }
    }

    /** @test */
    public function share_includes_trip_data_when_loaded()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        $share = Share::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
            'permission' => 'viewer',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/shares/{$share->token}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'destination_country',
                    'destination_city',
                    'start_date',
                    'end_date',
                ],
            ]);
    }

    /** @test */
    public function share_list_includes_user_data()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        Share::factory()->create(['trip_id' => $trip->id, 'user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$trip->id}/shares");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user' => ['id', 'name', 'email'],
                    ],
                ],
            ]);
    }

    /** @test */
    public function permission_accepts_valid_values()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        foreach (['viewer', 'editor'] as $permission) {
            $response = $this->actingAs($this->user, 'sanctum')
                ->postJson("/api/trips/{$trip->id}/shares", [
                    'permission' => $permission,
                ]);

            $response->assertCreated();
        }
    }

    /** @test */
    public function each_share_has_unique_token()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response1 = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/shares", ['permission' => 'viewer']);
        
        $response2 = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/shares", ['permission' => 'editor']);

        $token1 = $response1->json('data.token');
        $token2 = $response2->json('data.token');

        $this->assertNotEquals($token1, $token2);
    }

    /** @test */
    public function trip_validates_trip_exists_before_share()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/trips/99999/shares', [
                'permission' => 'viewer',
            ]);

        $response->assertNotFound();
    }
}
