<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\MapCheckpoint;
use App\Models\Trip;
use App\Models\TripDiary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentControllerTest extends TestCase
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
    public function user_can_comment_on_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/comments', [
                'entity_type' => 'trip',
                'entity_id' => $trip->id,
                'content' => 'Great trip plan!',
            ]);

        $response->assertCreated()
            ->assertJson([
                'data' => [
                    'user_id' => $this->user->id,
                    'entity_type' => 'trip',
                    'entity_id' => $trip->id,
                    'content' => 'Great trip plan!',
                ],
            ]);

        $this->assertDatabaseHas('comments', [
            'user_id' => $this->user->id,
            'entity_type' => Trip::class,
            'entity_id' => $trip->id,
            'content' => 'Great trip plan!',
        ]);
    }

    /** @test */
    public function user_can_comment_on_checkpoint()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $checkpoint = MapCheckpoint::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/comments', [
                'entity_type' => 'map_checkpoint',
                'entity_id' => $checkpoint->id,
                'content' => 'Beautiful place!',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('comments', [
            'user_id' => $this->user->id,
            'entity_type' => MapCheckpoint::class,
            'entity_id' => $checkpoint->id,
            'content' => 'Beautiful place!',
        ]);
    }

    /** @test */
    public function user_can_comment_on_trip_diary()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $diary = TripDiary::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/comments', [
                'entity_type' => 'trip_diary',
                'entity_id' => $diary->id,
                'content' => 'Amazing story!',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('comments', [
            'user_id' => $this->user->id,
            'entity_type' => TripDiary::class,
            'entity_id' => $diary->id,
        ]);
    }

    /** @test */
    public function user_can_list_comments()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        Comment::factory()->create([
            'user_id' => $this->user->id,
            'entity_type' => Trip::class,
            'entity_id' => $trip->id,
        ]);

        Comment::factory()->create([
            'user_id' => $this->otherUser->id,
            'entity_type' => Trip::class,
            'entity_id' => $trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/comments');

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function user_can_view_comment_details()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'entity_type' => Trip::class,
            'entity_id' => $trip->id,
            'content' => 'Test comment',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/comments/{$comment->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $comment->id,
                    'content' => 'Test comment',
                ],
            ]);
    }

    /** @test */
    public function user_can_update_their_comment()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'entity_type' => Trip::class,
            'entity_id' => $trip->id,
            'content' => 'Original comment',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/comments/{$comment->id}", [
                'content' => 'Updated comment',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'content' => 'Updated comment',
                ],
            ]);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated comment',
        ]);
    }

    /** @test */
    public function user_can_delete_their_comment()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'entity_type' => Trip::class,
            'entity_id' => $trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }

    /** @test */
    public function comment_creation_requires_authentication()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson('/api/comments', [
            'entity_type' => 'trip',
            'entity_id' => $trip->id,
            'content' => 'Test comment',
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function comment_creation_validates_required_fields()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/comments', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['entity_type', 'entity_id', 'content']);
    }

    /** @test */
    public function comment_validates_entity_type_enum()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/comments', [
                'entity_type' => 'invalid_type',
                'entity_id' => 1,
                'content' => 'Test comment',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['entity_type']);
    }

    /** @test */
    public function comment_validates_trip_exists()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/comments', [
                'entity_type' => 'trip',
                'entity_id' => 99999,
                'content' => 'Test comment',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['entity_id']);
    }

    /** @test */
    public function comment_validates_checkpoint_exists()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/comments', [
                'entity_type' => 'map_checkpoint',
                'entity_id' => 99999,
                'content' => 'Test comment',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['entity_id']);
    }

    /** @test */
    public function comment_validates_diary_exists()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/comments', [
                'entity_type' => 'trip_diary',
                'entity_id' => 99999,
                'content' => 'Test comment',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['entity_id']);
    }

    /** @test */
    public function comment_content_is_required()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/comments', [
                'entity_type' => 'trip',
                'entity_id' => $trip->id,
                'content' => '',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }

    /** @test */
    public function comment_content_validates_max_length()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/comments', [
                'entity_type' => 'trip',
                'entity_id' => $trip->id,
                'content' => str_repeat('a', 2001), // Max 2000
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['content']);
    }

    /** @test */
    public function user_cannot_update_other_users_comment()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $comment = Comment::factory()->create([
            'user_id' => $this->otherUser->id,
            'entity_type' => Trip::class,
            'entity_id' => $trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson("/api/comments/{$comment->id}", [
                'content' => 'Updated comment',
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function user_cannot_delete_other_users_comment()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $comment = Comment::factory()->create([
            'user_id' => $this->otherUser->id,
            'entity_type' => Trip::class,
            'entity_id' => $trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/comments/{$comment->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function comment_not_found_returns_404()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/comments/99999');

        $response->assertNotFound();
    }

    /** @test */
    public function comment_list_is_paginated()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        // Create 20 comments
        for ($i = 0; $i < 20; $i++) {
            Comment::factory()->create([
                'user_id' => $this->user->id,
                'entity_type' => Trip::class,
                'entity_id' => $trip->id,
            ]);
        }

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/comments');

        $response->assertOk()
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => ['current_page', 'total', 'per_page'],
            ])
            ->assertJsonCount(15, 'data'); // Default 15 per page
    }

    /** @test */
    public function comment_list_can_filter_by_entity_type()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $checkpoint = MapCheckpoint::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
        ]);

        Comment::factory()->create([
            'user_id' => $this->user->id,
            'entity_type' => Trip::class,
            'entity_id' => $trip->id,
        ]);

        Comment::factory()->create([
            'user_id' => $this->user->id,
            'entity_type' => MapCheckpoint::class,
            'entity_id' => $checkpoint->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/comments?entity_type=trip');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.entity_type', 'trip');
    }

    /** @test */
    public function comment_list_can_filter_by_entity_id()
    {
        $trip1 = Trip::factory()->create(['user_id' => $this->user->id]);
        $trip2 = Trip::factory()->create(['user_id' => $this->user->id]);

        Comment::factory()->create([
            'user_id' => $this->user->id,
            'entity_type' => Trip::class,
            'entity_id' => $trip1->id,
        ]);

        Comment::factory()->create([
            'user_id' => $this->user->id,
            'entity_type' => Trip::class,
            'entity_id' => $trip2->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/comments?entity_id={$trip1->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.entity_id', $trip1->id);
    }

    /** @test */
    public function comment_includes_entity_data_when_loaded()
    {
        $trip = Trip::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Trip',
        ]);

        Comment::factory()->create([
            'user_id' => $this->user->id,
            'entity_type' => Trip::class,
            'entity_id' => $trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/comments');

        $response->assertOk()
            ->assertJsonPath('data.0.entity.title', 'Test Trip');
    }

    /** @test */
    public function comment_includes_user_data_when_loaded()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        Comment::factory()->create([
            'user_id' => $this->user->id,
            'entity_type' => Trip::class,
            'entity_id' => $trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/comments');

        $response->assertOk()
            ->assertJsonPath('data.0.user.id', $this->user->id);
    }
}
