<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Tag;
use App\Models\Trip;
use App\Models\Place;
use App\Models\MapCheckpoint;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /** @test */
    public function it_lists_all_tags_paginated()
    {
        Tag::factory()->count(20)->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/tags');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'slug', 'usage_count', 'created_at', 'updated_at']
                ],
                'links',
                'meta' => ['current_page', 'per_page', 'total']
            ])
            ->assertJsonCount(15, 'data'); // Default pagination
    }

    /** @test */
    public function it_requires_authentication_to_list_tags()
    {
        $response = $this->getJson('/api/tags');

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_lists_popular_tags()
    {
        Tag::factory()->create(['usage_count' => 5]);
        Tag::factory()->create(['usage_count' => 15]);
        Tag::factory()->create(['usage_count' => 25]);
        Tag::factory()->create(['usage_count' => 8]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/tags/popular');

        $response->assertOk()
            ->assertJsonCount(2, 'data'); // Only tags with usage_count >= 10

        // Verify ordering by usage_count descending
        $usageCounts = collect($response->json('data'))->pluck('usage_count')->toArray();
        $this->assertEquals([25, 15], $usageCounts);
    }

    /** @test */
    public function it_allows_custom_minimum_usage_count_for_popular_tags()
    {
        Tag::factory()->create(['usage_count' => 5]);
        Tag::factory()->create(['usage_count' => 15]);
        Tag::factory()->create(['usage_count' => 25]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/tags/popular?min_count=5');

        $response->assertOk()
            ->assertJsonCount(3, 'data'); // All 3 tags have usage_count >= 5
    }

    /** @test */
    public function it_requires_authentication_to_view_popular_tags()
    {
        $response = $this->getJson('/api/tags/popular');

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_shows_a_single_tag()
    {
        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/tags/{$tag->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'slug', 'usage_count', 'created_at', 'updated_at']
            ])
            ->assertJsonFragment(['id' => $tag->id]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_tag()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/tags/99999');

        $response->assertNotFound();
    }

    /** @test */
    public function it_can_attach_tag_to_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $tag = Tag::factory()->create(['usage_count' => 5]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tags/attach', [
                'tag_id' => $tag->id,
                'taggable_type' => 'trip',
                'taggable_id' => $trip->id,
            ]);

        $response->assertCreated()
            ->assertJson(['message' => 'Tag attached successfully']);

        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag->id,
            'taggable_type' => 'App\Models\Trip',
            'taggable_id' => $trip->id,
        ]);

        // Verify usage count incremented
        $this->assertEquals(6, $tag->fresh()->usage_count);
    }

    /** @test */
    public function it_can_attach_tag_to_place()
    {
        $place = Place::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tags/attach', [
                'tag_id' => $tag->id,
                'taggable_type' => 'place',
                'taggable_id' => $place->id,
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag->id,
            'taggable_type' => 'App\Models\Place',
            'taggable_id' => $place->id,
        ]);
    }

    /** @test */
    public function it_can_attach_tag_to_checkpoint()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $checkpoint = MapCheckpoint::factory()->create(['trip_id' => $trip->id]);
        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tags/attach', [
                'tag_id' => $tag->id,
                'taggable_type' => 'checkpoint',
                'taggable_id' => $checkpoint->id,
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('taggables', [
            'tag_id' => $tag->id,
            'taggable_type' => 'App\Models\MapCheckpoint',
            'taggable_id' => $checkpoint->id,
        ]);
    }

    /** @test */
    public function it_requires_authentication_to_attach_tag()
    {
        $trip = Trip::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->postJson('/api/tags/attach', [
            'tag_id' => $tag->id,
            'taggable_type' => 'trip',
            'taggable_id' => $trip->id,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_validates_required_fields_for_attach()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tags/attach', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tag_id', 'taggable_type', 'taggable_id']);
    }

    /** @test */
    public function it_validates_taggable_type_enum()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tags/attach', [
                'tag_id' => $tag->id,
                'taggable_type' => 'invalid_type',
                'taggable_id' => $trip->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['taggable_type']);
    }

    /** @test */
    public function it_validates_tag_exists()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tags/attach', [
                'tag_id' => 99999,
                'taggable_type' => 'trip',
                'taggable_id' => $trip->id,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tag_id']);
    }

    /** @test */
    public function it_prevents_duplicate_tag_attachment()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $tag = Tag::factory()->create();

        // First attachment
        $trip->tags()->attach($tag->id);

        // Try to attach again
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tags/attach', [
                'tag_id' => $tag->id,
                'taggable_type' => 'trip',
                'taggable_id' => $trip->id,
            ]);

        $response->assertUnprocessable()
            ->assertJson(['message' => 'Tag is already attached to this entity']);
    }

    /** @test */
    public function it_can_detach_tag_from_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $tag = Tag::factory()->create(['usage_count' => 5]);
        $trip->tags()->attach($tag->id);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/tags/detach', [
                'tag_id' => $tag->id,
                'taggable_type' => 'trip',
                'taggable_id' => $trip->id,
            ]);

        $response->assertOk()
            ->assertJson(['message' => 'Tag detached successfully']);

        $this->assertDatabaseMissing('taggables', [
            'tag_id' => $tag->id,
            'taggable_type' => 'App\Models\Trip',
            'taggable_id' => $trip->id,
        ]);

        // Verify usage count decremented
        $this->assertEquals(4, $tag->fresh()->usage_count);
    }

    /** @test */
    public function it_requires_authentication_to_detach_tag()
    {
        $trip = Trip::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->deleteJson('/api/tags/detach', [
            'tag_id' => $tag->id,
            'taggable_type' => 'trip',
            'taggable_id' => $trip->id,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_validates_required_fields_for_detach()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/tags/detach', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['tag_id', 'taggable_type', 'taggable_id']);
    }

    /** @test */
    public function it_handles_detaching_non_attached_tag_gracefully()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/tags/detach', [
                'tag_id' => $tag->id,
                'taggable_type' => 'trip',
                'taggable_id' => $trip->id,
            ]);

        $response->assertOk()
            ->assertJson(['message' => 'Tag detached successfully']);
    }

    /** @test */
    public function it_searches_tags_by_name()
    {
        Tag::factory()->create(['name' => 'Beach Vacation']);
        Tag::factory()->create(['name' => 'Mountain Hiking']);
        Tag::factory()->create(['name' => 'Beach Resort']);
        Tag::factory()->create(['name' => 'City Tour']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/tags?search=beach');

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertContains('Beach Vacation', $names);
        $this->assertContains('Beach Resort', $names);
    }

    /** @test */
    public function it_lists_tags_ordered_by_name_alphabetically()
    {
        Tag::factory()->create(['name' => 'Zebra']);
        Tag::factory()->create(['name' => 'Apple']);
        Tag::factory()->create(['name' => 'Mango']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/tags?sort=name');

        $response->assertOk();

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertEquals(['Apple', 'Mango', 'Zebra'], $names);
    }
}
