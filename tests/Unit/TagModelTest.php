<?php

namespace Tests\Unit;

use App\Models\MapCheckpoint;
use App\Models\Place;
use App\Models\Tag;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TDD Test Suite for Tag Model
 * 
 * Testing polymorphic many-to-many tagging system:
 * - Tag model attributes (name, slug, usage_count)
 * - Polymorphic morphedByMany relationships (trips, places, checkpoints)
 * - Auto-slug generation from name
 * - Usage count tracking
 * - Popular tags scoping
 * - Tag discovery and search
 * 
 * @see tests/Unit/TagModelTest.php
 */
class TagModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function tag_can_be_created_with_name()
    {
        $tag = Tag::create([
            'name' => 'Adventure Travel',
        ]);

        $this->assertDatabaseHas('tags', [
            'name' => 'Adventure Travel',
        ]);
    }

    /** @test */
    public function tag_has_correct_fillable_attributes()
    {
        $tag = new Tag();

        $this->assertEquals(
            ['name', 'slug', 'usage_count'],
            $tag->getFillable()
        );
    }

    /** @test */
    public function tag_auto_generates_slug_from_name()
    {
        $tag = Tag::create([
            'name' => 'Adventure Travel',
        ]);

        $this->assertEquals('adventure-travel', $tag->slug);
    }

    /** @test */
    public function tag_slug_must_be_unique()
    {
        Tag::create(['name' => 'Beach']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Tag::create(['name' => 'Beach']); // Same slug
    }

    /** @test */
    public function tag_usage_count_defaults_to_zero()
    {
        $tag = Tag::create([
            'name' => 'Mountain Hiking',
        ]);

        $this->assertEquals(0, $tag->usage_count);
    }

    /** @test */
    public function tag_can_have_many_trips()
    {
        $tag = Tag::create(['name' => 'Summer Vacation']);
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user)->create();

        $tag->trips()->attach($trip);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $tag->trips);
        $this->assertTrue($tag->trips->contains($trip));
    }

    /** @test */
    public function tag_can_have_many_places()
    {
        $tag = Tag::create(['name' => 'Restaurant']);
        $place = Place::factory()->create();

        $tag->places()->attach($place);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $tag->places);
        $this->assertTrue($tag->places->contains($place));
    }

    /** @test */
    public function tag_can_have_many_checkpoints()
    {
        $tag = Tag::create(['name' => 'Scenic View']);
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user)->create();
        $checkpoint = MapCheckpoint::factory()->for($trip)->for($user)->create();

        $tag->checkpoints()->attach($checkpoint);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $tag->checkpoints);
        $this->assertTrue($tag->checkpoints->contains($checkpoint));
    }

    /** @test */
    public function trip_can_have_many_tags()
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user)->create();
        $tag1 = Tag::create(['name' => 'Beach']);
        $tag2 = Tag::create(['name' => 'Summer']);

        $trip->tags()->attach([$tag1->id, $tag2->id]);

        $this->assertEquals(2, $trip->tags()->count());
        $this->assertTrue($trip->tags->contains($tag1));
        $this->assertTrue($trip->tags->contains($tag2));
    }

    /** @test */
    public function place_can_have_many_tags()
    {
        $place = Place::factory()->create();
        $tag1 = Tag::create(['name' => 'Cafe']);
        $tag2 = Tag::create(['name' => 'WiFi']);

        $place->tags()->attach([$tag1->id, $tag2->id]);

        $this->assertEquals(2, $place->tags()->count());
        $this->assertTrue($place->tags->contains($tag1));
        $this->assertTrue($place->tags->contains($tag2));
    }

    /** @test */
    public function checkpoint_can_have_many_tags()
    {
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user)->create();
        $checkpoint = MapCheckpoint::factory()->for($trip)->for($user)->create();
        $tag1 = Tag::create(['name' => 'Photo Spot']);
        $tag2 = Tag::create(['name' => 'Landmark']);

        $checkpoint->tags()->attach([$tag1->id, $tag2->id]);

        $this->assertEquals(2, $checkpoint->tags()->count());
        $this->assertTrue($checkpoint->tags->contains($tag1));
        $this->assertTrue($checkpoint->tags->contains($tag2));
    }

    /** @test */
    public function tag_can_increment_usage_count()
    {
        $tag = Tag::create(['name' => 'Popular']);

        $tag->incrementUsage();
        $this->assertEquals(1, $tag->fresh()->usage_count);

        $tag->incrementUsage();
        $this->assertEquals(2, $tag->fresh()->usage_count);
    }

    /** @test */
    public function tag_can_decrement_usage_count()
    {
        $tag = Tag::create(['name' => 'Popular', 'usage_count' => 5]);

        $tag->decrementUsage();
        $this->assertEquals(4, $tag->fresh()->usage_count);

        $tag->decrementUsage();
        $this->assertEquals(3, $tag->fresh()->usage_count);
    }

    /** @test */
    public function tag_usage_count_does_not_go_below_zero()
    {
        $tag = Tag::create(['name' => 'Popular', 'usage_count' => 1]);

        $tag->decrementUsage();
        $this->assertEquals(0, $tag->fresh()->usage_count);

        $tag->decrementUsage();
        $this->assertEquals(0, $tag->fresh()->usage_count); // Should not go negative
    }

    /** @test */
    public function tag_can_scope_popular_tags()
    {
        Tag::create(['name' => 'Unpopular', 'usage_count' => 1]);
        $popular1 = Tag::create(['name' => 'Very Popular', 'usage_count' => 100]);
        $popular2 = Tag::create(['name' => 'Also Popular', 'usage_count' => 50]);
        Tag::create(['name' => 'Less Popular', 'usage_count' => 5]);

        $popularTags = Tag::popular(10)->get();

        $this->assertEquals(2, $popularTags->count());
        $this->assertTrue($popularTags->contains($popular1));
        $this->assertTrue($popularTags->contains($popular2));
    }

    /** @test */
    public function tag_popular_scope_orders_by_usage_count_descending()
    {
        $tag1 = Tag::create(['name' => 'Tag 1', 'usage_count' => 10]);
        $tag2 = Tag::create(['name' => 'Tag 2', 'usage_count' => 100]);
        $tag3 = Tag::create(['name' => 'Tag 3', 'usage_count' => 50]);

        $popularTags = Tag::popular()->get();

        $this->assertEquals('Tag 2', $popularTags->first()->name);
        $this->assertEquals('Tag 3', $popularTags->get(1)->name);
        $this->assertEquals('Tag 1', $popularTags->get(2)->name);
    }

    /** @test */
    public function tag_can_scope_by_name()
    {
        Tag::create(['name' => 'Beach Holiday']);
        Tag::create(['name' => 'Mountain Adventure']);
        Tag::create(['name' => 'Beach Resort']);

        $beachTags = Tag::whereName('Beach Holiday')->get();

        $this->assertEquals(1, $beachTags->count());
        $this->assertEquals('Beach Holiday', $beachTags->first()->name);
    }

    /** @test */
    public function tag_can_search_by_partial_name()
    {
        Tag::create(['name' => 'Beach Holiday']);
        Tag::create(['name' => 'Mountain Adventure']);
        Tag::create(['name' => 'Beach Resort']);

        $beachTags = Tag::where('name', 'like', '%Beach%')->get();

        $this->assertEquals(2, $beachTags->count());
    }

    /** @test */
    public function tag_slug_is_lowercase()
    {
        $tag = Tag::create(['name' => 'UPPERCASE NAME']);

        $this->assertEquals('uppercase-name', $tag->slug);
    }

    /** @test */
    public function tag_slug_replaces_spaces_with_hyphens()
    {
        $tag = Tag::create(['name' => 'Multiple Word Tag']);

        $this->assertEquals('multiple-word-tag', $tag->slug);
    }

    /** @test */
    public function tag_slug_handles_special_characters()
    {
        $tag = Tag::create(['name' => 'Tag with #special @characters!']);

        // Slug should only contain alphanumeric and hyphens
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $tag->slug);
    }

    /** @test */
    public function tag_name_is_required()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Tag::create([]);
    }

    /** @test */
    public function multiple_taggables_can_share_same_tag()
    {
        $tag = Tag::create(['name' => 'Popular Destination']);
        
        $user = User::factory()->create();
        $trip = Trip::factory()->for($user)->create();
        $place = Place::factory()->create();
        $checkpoint = MapCheckpoint::factory()->for($trip)->for($user)->create();

        $trip->tags()->attach($tag);
        $place->tags()->attach($tag);
        $checkpoint->tags()->attach($tag);

        $this->assertEquals(1, $trip->tags()->count());
        $this->assertEquals(1, $place->tags()->count());
        $this->assertEquals(1, $checkpoint->tags()->count());
        $this->assertTrue($trip->tags->contains($tag));
        $this->assertTrue($place->tags->contains($tag));
        $this->assertTrue($checkpoint->tags->contains($tag));
    }

    /** @test */
    public function tag_can_find_or_create_by_name()
    {
        // First call creates
        $tag1 = Tag::firstOrCreate(['name' => 'New Tag']);
        $this->assertEquals('new-tag', $tag1->slug);
        
        // Second call finds existing
        $tag2 = Tag::firstOrCreate(['name' => 'New Tag']);
        $this->assertEquals($tag1->id, $tag2->id);
    }

    /** @test */
    public function tag_timestamps_are_set_automatically()
    {
        $tag = Tag::create(['name' => 'Timestamped']);

        $this->assertNotNull($tag->created_at);
        $this->assertNotNull($tag->updated_at);
    }
}
