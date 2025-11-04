<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TripParticipantControllerTest extends TestCase
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
    public function trip_owner_can_add_participant()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/participants", [
                'user_id' => $this->otherUser->id,
                'role' => 'editor',
            ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'trip_id' => $trip->id,
                    'user_id' => $this->otherUser->id,
                    'role' => 'editor',
                ],
            ]);

        $this->assertDatabaseHas('trip_participants', [
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
            'role' => 'editor',
        ]);
    }

    /** @test */
    public function trip_owner_can_list_all_participants()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
        ]);

        TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$trip->id}/participants");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function trip_editor_can_view_participants()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
            'role' => 'editor',
        ]);

        $response = $this->actingAs($this->otherUser, 'sanctum')
            ->getJson("/api/trips/{$trip->id}/participants");

        $response->assertOk();
    }

    /** @test */
    public function trip_viewer_can_view_participants()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($this->otherUser, 'sanctum')
            ->getJson("/api/trips/{$trip->id}/participants");

        $response->assertOk();
    }

    /** @test */
    public function non_participant_cannot_view_participants()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->otherUser, 'sanctum')
            ->getJson("/api/trips/{$trip->id}/participants");

        $response->assertForbidden();
    }

    /** @test */
    public function trip_owner_can_update_participant_role()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $participant = TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/trips/{$trip->id}/participants/{$participant->id}", [
                'role' => 'editor',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'role' => 'editor',
                ],
            ]);

        $this->assertDatabaseHas('trip_participants', [
            'id' => $participant->id,
            'role' => 'editor',
        ]);
    }

    /** @test */
    public function trip_editor_cannot_update_participant_role()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
            'role' => 'editor',
        ]);

        $thirdUser = User::factory()->create();
        $participant = TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $thirdUser->id,
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($this->otherUser, 'sanctum')
            ->putJson("/api/trips/{$trip->id}/participants/{$participant->id}", [
                'role' => 'editor',
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function trip_owner_can_remove_participant()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $participant = TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/trips/{$trip->id}/participants/{$participant->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('trip_participants', [
            'id' => $participant->id,
        ]);
    }

    /** @test */
    public function trip_editor_cannot_remove_participant()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
            'role' => 'editor',
        ]);

        $thirdUser = User::factory()->create();
        $participant = TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $thirdUser->id,
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($this->otherUser, 'sanctum')
            ->deleteJson("/api/trips/{$trip->id}/participants/{$participant->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function participant_can_leave_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $participant = TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($this->otherUser, 'sanctum')
            ->deleteJson("/api/trips/{$trip->id}/participants/{$participant->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('trip_participants', [
            'id' => $participant->id,
        ]);
    }

    /** @test */
    public function adding_participant_requires_authentication()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson("/api/trips/{$trip->id}/participants", [
            'user_id' => $this->otherUser->id,
            'role' => 'viewer',
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function adding_participant_validates_required_fields()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/participants", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id', 'role']);
    }

    /** @test */
    public function adding_participant_validates_role_enum()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/participants", [
                'user_id' => $this->otherUser->id,
                'role' => 'invalid_role',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    /** @test */
    public function adding_participant_validates_user_exists()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/participants", [
                'user_id' => 99999,
                'role' => 'viewer',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);
    }

    /** @test */
    public function cannot_add_same_user_twice()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/trips/{$trip->id}/participants", [
                'user_id' => $this->otherUser->id,
                'role' => 'editor',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);
    }

    /** @test */
    public function updating_role_validates_role_enum()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $participant = TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/trips/{$trip->id}/participants/{$participant->id}", [
                'role' => 'invalid_role',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['role']);
    }

    /** @test */
    public function participant_list_is_paginated()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        // Create 20 participants
        for ($i = 0; $i < 20; $i++) {
            $user = User::factory()->create();
            TripParticipant::factory()->create([
                'trip_id' => $trip->id,
                'user_id' => $user->id,
                'role' => 'viewer',
            ]);
        }

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$trip->id}/participants");

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => ['current_page', 'total', 'per_page'],
            ])
            ->assertJsonCount(15, 'data'); // Default 15 per page
    }

    /** @test */
    public function participant_list_can_filter_by_role()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
        ]);

        TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$trip->id}/participants?role=viewer");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.role', 'viewer');
    }

    /** @test */
    public function participant_includes_user_data()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
            'role' => 'viewer',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$trip->id}/participants");

        $response->assertOk()
            ->assertJsonPath('data.0.user.id', $this->otherUser->id);
    }

    /** @test */
    public function participant_not_found_returns_404()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/trips/{$trip->id}/participants/99999", [
                'role' => 'editor',
            ]);

        $response->assertNotFound();
    }

    /** @test */
    public function role_accepts_valid_values()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $roles = ['owner', 'editor', 'viewer'];

        foreach ($roles as $role) {
            $user = User::factory()->create();

            $response = $this->actingAs($this->user, 'sanctum')
                ->postJson("/api/trips/{$trip->id}/participants", [
                    'user_id' => $user->id,
                    'role' => $role,
                ]);

            $response->assertCreated();

            $this->assertDatabaseHas('trip_participants', [
                'trip_id' => $trip->id,
                'user_id' => $user->id,
                'role' => $role,
            ]);
        }
    }

    /** @test */
    public function cannot_remove_trip_owner()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $ownerParticipant = TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/trips/{$trip->id}/participants/{$ownerParticipant->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('trip_participants', [
            'id' => $ownerParticipant->id,
        ]);
    }

    /** @test */
    public function cannot_change_owner_role()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $ownerParticipant = TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/trips/{$trip->id}/participants/{$ownerParticipant->id}", [
                'role' => 'editor',
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('trip_participants', [
            'id' => $ownerParticipant->id,
            'role' => 'owner',
        ]);
    }
}
