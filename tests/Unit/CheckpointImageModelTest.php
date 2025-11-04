<?php

namespace Tests\Unit;

use App\Models\CheckpointImage;
use App\Models\MapCheckpoint;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CheckpointImage Model Unit Tests
 * 
 * Tests file path storage, captions, upload tracking.
 * Per TDD: These tests define expected behavior before implementation.
 * 
 * File Storage Strategy:
 * - Uses Flysystem with file_path column (not direct storage in DB)
 * - Files stored in storage/app/checkpoints/{trip_id}/{checkpoint_id}/
 * - Path format: checkpoints/123/456/image-uuid.jpg
 * - Future: Integration with NAVER Object Storage or S3-compatible
 */
class CheckpointImageModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function checkpoint_image_has_fillable_attributes(): void
    {
        $checkpoint = MapCheckpoint::factory()->create();
        $user = User::factory()->create();

        $image = CheckpointImage::create([
            'map_checkpoint_id' => $checkpoint->id,
            'user_id' => $user->id,
            'file_path' => 'checkpoints/123/456/image-uuid.jpg',
            'caption' => 'Beautiful sunset view',
            'uploaded_at' => now(),
        ]);

        $this->assertDatabaseHas('checkpoint_images', [
            'map_checkpoint_id' => $checkpoint->id,
            'caption' => 'Beautiful sunset view',
        ]);
    }

    /** @test */
    public function checkpoint_image_belongs_to_checkpoint(): void
    {
        $checkpoint = MapCheckpoint::factory()->create();
        $image = CheckpointImage::factory()->create(['map_checkpoint_id' => $checkpoint->id]);

        $this->assertInstanceOf(MapCheckpoint::class, $image->checkpoint);
        $this->assertEquals($checkpoint->id, $image->checkpoint->id);
    }

    /** @test */
    public function checkpoint_image_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $image = CheckpointImage::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $image->user);
        $this->assertEquals($user->id, $image->user->id);
    }

    /** @test */
    public function checkpoint_has_many_images(): void
    {
        $checkpoint = MapCheckpoint::factory()->create();
        CheckpointImage::factory()->count(3)->create(['map_checkpoint_id' => $checkpoint->id]);

        $this->assertCount(3, $checkpoint->images);
    }

    /** @test */
    public function checkpoint_image_has_url_accessor(): void
    {
        $image = CheckpointImage::factory()->create([
            'file_path' => 'checkpoints/123/456/image-uuid.jpg',
        ]);

        // Accessor should generate storage URL
        $this->assertStringContainsString('storage/checkpoints', $image->url);
    }

    /** @test */
    public function checkpoint_image_caption_is_optional(): void
    {
        $image = CheckpointImage::factory()->create(['caption' => null]);

        $this->assertNull($image->caption);
    }

    /** @test */
    public function checkpoint_image_uploaded_at_defaults_to_now(): void
    {
        $image = CheckpointImage::factory()->create();

        $this->assertNotNull($image->uploaded_at);
        $this->assertInstanceOf(\DateTime::class, $image->uploaded_at);
    }

    /** @test */
    public function user_has_many_checkpoint_images(): void
    {
        $user = User::factory()->create();
        CheckpointImage::factory()->count(3)->create(['user_id' => $user->id]);

        $this->assertCount(3, $user->checkpointImages);
    }

    /** @test */
    public function checkpoint_image_can_scope_by_checkpoint(): void
    {
        $checkpoint1 = MapCheckpoint::factory()->create();
        $checkpoint2 = MapCheckpoint::factory()->create();

        CheckpointImage::factory()->count(2)->create(['map_checkpoint_id' => $checkpoint1->id]);
        CheckpointImage::factory()->create(['map_checkpoint_id' => $checkpoint2->id]);

        $checkpoint1Images = CheckpointImage::forCheckpoint($checkpoint1->id)->get();

        $this->assertCount(2, $checkpoint1Images);
    }

    /** @test */
    public function checkpoint_image_can_scope_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        CheckpointImage::factory()->count(2)->create(['user_id' => $user1->id]);
        CheckpointImage::factory()->create(['user_id' => $user2->id]);

        $user1Images = CheckpointImage::byUser($user1->id)->get();

        $this->assertCount(2, $user1Images);
    }

    /** @test */
    public function checkpoint_image_can_scope_recent_uploads(): void
    {
        CheckpointImage::factory()->create([
            'uploaded_at' => now()->subDays(2),
        ]);
        
        CheckpointImage::factory()->create([
            'uploaded_at' => now()->subHours(1),
        ]);

        $recent = CheckpointImage::recent()->get();

        $this->assertCount(2, $recent);
        // Should be ordered by uploaded_at DESC
        $this->assertTrue($recent[0]->uploaded_at > $recent[1]->uploaded_at);
    }
}
