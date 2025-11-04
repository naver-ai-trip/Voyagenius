<?php

namespace Tests\Feature;

use App\Models\ChecklistItem;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChecklistItemControllerTest extends TestCase
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
    public function user_can_create_checklist_item_for_their_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checklist-items', [
                'trip_id' => $trip->id,
                'content' => 'Pack passport and tickets',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'trip_id',
                    'user_id',
                    'content',
                    'is_checked',
                    'created_at',
                    'updated_at',
                ],
            ])
            ->assertJsonPath('data.trip_id', $trip->id)
            ->assertJsonPath('data.content', 'Pack passport and tickets')
            ->assertJsonPath('data.is_checked', false);

        $this->assertDatabaseHas('checklist_items', [
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'content' => 'Pack passport and tickets',
            'is_checked' => false,
        ]);
    }

    /** @test */
    public function user_can_list_checklist_items_for_their_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        ChecklistItem::factory()->count(3)->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/checklist-items');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /** @test */
    public function user_can_view_checklist_item_details()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $item = ChecklistItem::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'content' => 'Book hotel',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/checklist-items/{$item->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonPath('data.content', 'Book hotel');
    }

    /** @test */
    public function user_can_update_their_checklist_item()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $item = ChecklistItem::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'content' => 'Book hotel',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/checklist-items/{$item->id}", [
                'content' => 'Book hotel and arrange transport',
            ]);

        $response->assertOk()
            ->assertJsonPath('data.content', 'Book hotel and arrange transport');

        $this->assertDatabaseHas('checklist_items', [
            'id' => $item->id,
            'content' => 'Book hotel and arrange transport',
        ]);
    }

    /** @test */
    public function user_can_toggle_checklist_item_checked_status()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $item = ChecklistItem::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'is_checked' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/checklist-items/{$item->id}", [
                'is_checked' => true,
            ]);

        $response->assertOk()
            ->assertJsonPath('data.is_checked', true);

        $this->assertDatabaseHas('checklist_items', [
            'id' => $item->id,
            'is_checked' => true,
        ]);
    }

    /** @test */
    public function user_can_delete_their_checklist_item()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $item = ChecklistItem::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/checklist-items/{$item->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('checklist_items', [
            'id' => $item->id,
        ]);
    }

    /** @test */
    public function checklist_item_creation_requires_authentication()
    {
        $trip = Trip::factory()->create();

        $response = $this->postJson('/api/checklist-items', [
            'trip_id' => $trip->id,
            'content' => 'Pack luggage',
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function checklist_item_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checklist-items', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['trip_id', 'content']);
    }

    /** @test */
    public function checklist_item_validates_trip_exists()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checklist-items', [
                'trip_id' => 99999,
                'content' => 'Pack luggage',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['trip_id']);
    }

    /** @test */
    public function checklist_item_validates_content_length()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checklist-items', [
                'trip_id' => $trip->id,
                'content' => str_repeat('a', 256), // Assuming max 255
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }

    /** @test */
    public function user_cannot_create_checklist_item_for_other_users_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checklist-items', [
                'trip_id' => $trip->id,
                'content' => 'Pack luggage',
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_view_other_users_checklist_item()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        $item = ChecklistItem::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/checklist-items/{$item->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_update_other_users_checklist_item()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        $item = ChecklistItem::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/checklist-items/{$item->id}", [
                'content' => 'Updated content',
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_delete_other_users_checklist_item()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        $item = ChecklistItem::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/checklist-items/{$item->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function checklist_item_not_found_returns_404()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/checklist-items/99999');

        $response->assertNotFound();
    }

    /** @test */
    public function checklist_item_list_is_paginated()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        ChecklistItem::factory()->count(20)->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/checklist-items');

        $response->assertOk()
            ->assertJsonCount(15, 'data') // Default pagination
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    /** @test */
    public function checklist_item_list_can_filter_by_trip_id()
    {
        $trip1 = Trip::factory()->create(['user_id' => $this->user->id]);
        $trip2 = Trip::factory()->create(['user_id' => $this->user->id]);

        ChecklistItem::factory()->count(2)->create([
            'trip_id' => $trip1->id,
            'user_id' => $this->user->id,
        ]);

        ChecklistItem::factory()->count(3)->create([
            'trip_id' => $trip2->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/checklist-items?trip_id={$trip1->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function checklist_item_list_can_filter_by_checked_status()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        ChecklistItem::factory()->count(2)->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'is_checked' => true,
        ]);

        ChecklistItem::factory()->count(3)->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'is_checked' => false,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/checklist-items?is_checked=1');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function checklist_item_includes_trip_data_when_loaded()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $item = ChecklistItem::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/checklist-items/{$item->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'trip' => ['id', 'title'],
                ],
            ]);
    }

    /** @test */
    public function checklist_item_is_checked_defaults_to_false()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checklist-items', [
                'trip_id' => $trip->id,
                'content' => 'Pack luggage',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.is_checked', false);
    }
}
