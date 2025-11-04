<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Trip;
use App\Models\MapCheckpoint;
use App\Models\CheckpointImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CheckpointImageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Trip $trip;
    protected MapCheckpoint $checkpoint;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->trip = Trip::factory()->create(['user_id' => $this->user->id]);
        $this->checkpoint = MapCheckpoint::factory()->create([
            'trip_id' => $this->trip->id,
            'user_id' => $this->user->id,
        ]);

        // Use fake storage for testing
        Storage::fake('public');
    }

    /** @test */
    public function it_can_list_checkpoint_images_with_pagination()
    {
        CheckpointImage::factory()->count(20)->create([
            'map_checkpoint_id' => $this->checkpoint->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/checkpoints/{$this->checkpoint->id}/images");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'map_checkpoint_id', 'user_id', 'file_path', 'url', 'caption', 'uploaded_at', 'created_at', 'updated_at']
                ],
                'links',
                'meta' => ['current_page', 'per_page', 'total']
            ])
            ->assertJsonCount(15, 'data'); // Default pagination
    }

    /** @test */
    public function it_can_upload_checkpoint_image()
    {
        $file = UploadedFile::fake()->image('checkpoint.jpg', 800, 600);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/checkpoints/{$this->checkpoint->id}/images", [
                'image' => $file,
                'caption' => 'Beautiful view from the checkpoint',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => ['id', 'map_checkpoint_id', 'user_id', 'file_path', 'url', 'caption', 'uploaded_at']
            ]);

        $this->assertDatabaseHas('checkpoint_images', [
            'map_checkpoint_id' => $this->checkpoint->id,
            'user_id' => $this->user->id,
            'caption' => 'Beautiful view from the checkpoint',
        ]);

        // Verify file was stored
        $image = CheckpointImage::first();
        Storage::disk('public')->assertExists($image->file_path);
    }

    /** @test */
    public function it_can_upload_checkpoint_image_without_caption()
    {
        $file = UploadedFile::fake()->image('checkpoint.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/checkpoints/{$this->checkpoint->id}/images", [
                'image' => $file,
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('checkpoint_images', [
            'map_checkpoint_id' => $this->checkpoint->id,
            'user_id' => $this->user->id,
            'caption' => null,
        ]);
    }

    /** @test */
    public function it_stores_image_with_organized_path_structure()
    {
        $file = UploadedFile::fake()->image('checkpoint.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/checkpoints/{$this->checkpoint->id}/images", [
                'image' => $file,
            ]);

        $response->assertCreated();

        $image = CheckpointImage::first();
        
        // Verify path structure: checkpoints/{trip_id}/{checkpoint_id}/{uuid}.{extension}
        $this->assertStringContainsString("checkpoints/{$this->trip->id}/{$this->checkpoint->id}/", $image->file_path);
        $this->assertStringEndsWith('.jpg', $image->file_path);
    }

    /** @test */
    public function it_can_view_checkpoint_image_details()
    {
        $image = CheckpointImage::factory()->create([
            'map_checkpoint_id' => $this->checkpoint->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/checkpoints/{$this->checkpoint->id}/images/{$image->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $image->id,
                    'map_checkpoint_id' => $this->checkpoint->id,
                    'caption' => $image->caption,
                ]
            ]);
    }

    /** @test */
    public function it_can_update_checkpoint_image_caption()
    {
        $image = CheckpointImage::factory()->create([
            'map_checkpoint_id' => $this->checkpoint->id,
            'user_id' => $this->user->id,
            'caption' => 'Old caption',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/checkpoints/{$this->checkpoint->id}/images/{$image->id}", [
                'caption' => 'Updated caption',
            ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $image->id,
                    'caption' => 'Updated caption',
                ]
            ]);

        $this->assertDatabaseHas('checkpoint_images', [
            'id' => $image->id,
            'caption' => 'Updated caption',
        ]);
    }

    /** @test */
    public function it_can_delete_checkpoint_image()
    {
        // First upload an image through the controller
        $file = UploadedFile::fake()->image('checkpoint.jpg');
        
        $uploadResponse = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/checkpoints/{$this->checkpoint->id}/images", [
                'image' => $file,
            ]);
        
        $uploadResponse->assertCreated();
        $imageId = $uploadResponse->json('data.id');
        $filePath = $uploadResponse->json('data.file_path');
        
        // Verify file exists before deletion
        $this->assertTrue(Storage::disk('public')->exists($filePath));

        // Now delete it
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/checkpoints/{$this->checkpoint->id}/images/{$imageId}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('checkpoint_images', ['id' => $imageId]);
        
        // Verify file was deleted from storage
        $this->assertFalse(Storage::disk('public')->exists($filePath));
    }

    /** @test */
    public function it_requires_authentication_to_upload_image()
    {
        $file = UploadedFile::fake()->image('checkpoint.jpg');

        $response = $this->postJson("/api/checkpoints/{$this->checkpoint->id}/images", [
            'image' => $file,
        ]);

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_validates_image_file_is_required()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/checkpoints/{$this->checkpoint->id}/images", [
                'caption' => 'No image',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function it_validates_image_must_be_valid_image_file()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/checkpoints/{$this->checkpoint->id}/images", [
                'image' => $file,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function it_validates_image_file_size_limit()
    {
        $file = UploadedFile::fake()->image('huge.jpg')->size(11000); // 11MB

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/checkpoints/{$this->checkpoint->id}/images", [
                'image' => $file,
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    }

    /** @test */
    public function it_validates_caption_max_length()
    {
        $file = UploadedFile::fake()->image('checkpoint.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/checkpoints/{$this->checkpoint->id}/images", [
                'image' => $file,
                'caption' => str_repeat('a', 501),
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['caption']);
    }

    /** @test */
    public function only_trip_owner_can_upload_checkpoint_image()
    {
        $file = UploadedFile::fake()->image('checkpoint.jpg');

        $response = $this->actingAs($this->otherUser, 'sanctum')
            ->postJson("/api/checkpoints/{$this->checkpoint->id}/images", [
                'image' => $file,
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function only_image_uploader_can_update_caption()
    {
        $image = CheckpointImage::factory()->create([
            'map_checkpoint_id' => $this->checkpoint->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->otherUser, 'sanctum')
            ->patchJson("/api/checkpoints/{$this->checkpoint->id}/images/{$image->id}", [
                'caption' => 'Trying to update',
            ]);

        $response->assertForbidden();
    }

    /** @test */
    public function only_image_uploader_can_delete_image()
    {
        $image = CheckpointImage::factory()->create([
            'map_checkpoint_id' => $this->checkpoint->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->otherUser, 'sanctum')
            ->deleteJson("/api/checkpoints/{$this->checkpoint->id}/images/{$image->id}");

        $response->assertForbidden();
    }

    /** @test */
    public function trip_owner_can_delete_any_checkpoint_image()
    {
        // Upload image as otherUser first
        $file = UploadedFile::fake()->image('other-user-image.jpg');
        $filePath = "checkpoints/{$this->trip->id}/{$this->checkpoint->id}/test-" . time() . ".jpg";
        Storage::disk('public')->put($filePath, file_get_contents($file));

        // Image uploaded by someone else
        $image = CheckpointImage::factory()->create([
            'map_checkpoint_id' => $this->checkpoint->id,
            'user_id' => $this->otherUser->id,
            'file_path' => $filePath,
        ]);

        // Trip owner can delete it
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/checkpoints/{$this->checkpoint->id}/images/{$image->id}");

        $response->assertNoContent();
        $this->assertDatabaseMissing('checkpoint_images', ['id' => $image->id]);
        $this->assertFalse(Storage::disk('public')->exists($filePath));
    }

    /** @test */
    public function it_returns_404_for_non_existent_checkpoint()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/checkpoints/99999/images');

        $response->assertNotFound();
    }

    /** @test */
    public function it_returns_404_for_non_existent_image()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/checkpoints/{$this->checkpoint->id}/images/99999");

        $response->assertNotFound();
    }

    /** @test */
    public function it_lists_images_ordered_by_upload_date_desc()
    {
        $image1 = CheckpointImage::factory()->create([
            'map_checkpoint_id' => $this->checkpoint->id,
            'uploaded_at' => now()->subDays(2),
        ]);
        $image2 = CheckpointImage::factory()->create([
            'map_checkpoint_id' => $this->checkpoint->id,
            'uploaded_at' => now()->subDays(1),
        ]);
        $image3 = CheckpointImage::factory()->create([
            'map_checkpoint_id' => $this->checkpoint->id,
            'uploaded_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/checkpoints/{$this->checkpoint->id}/images");

        $response->assertOk();
        
        $data = $response->json('data');
        $this->assertEquals($image3->id, $data[0]['id']);
        $this->assertEquals($image2->id, $data[1]['id']);
        $this->assertEquals($image1->id, $data[2]['id']);
    }

    /** @test */
    public function it_includes_checkpoint_relationship_when_requested()
    {
        $image = CheckpointImage::factory()->create([
            'map_checkpoint_id' => $this->checkpoint->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/checkpoints/{$this->checkpoint->id}/images/{$image->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'checkpoint' => ['id', 'title', 'lat', 'lng'],
                    'user' => ['id', 'name', 'email'],
                ]
            ]);
    }

    /** @test */
    public function it_generates_public_url_for_image()
    {
        $image = CheckpointImage::factory()->create([
            'map_checkpoint_id' => $this->checkpoint->id,
            'user_id' => $this->user->id,
            'file_path' => 'checkpoints/1/1/test.jpg',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/checkpoints/{$this->checkpoint->id}/images/{$image->id}");

        $response->assertOk()
            ->assertJsonFragment(['url' => Storage::url('checkpoints/1/1/test.jpg')]);
    }

    /** @test */
    public function it_validates_checkpoint_exists_before_upload()
    {
        $file = UploadedFile::fake()->image('checkpoint.jpg');

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/checkpoints/99999/images', [
                'image' => $file,
            ]);

        $response->assertNotFound();
    }

    /** @test */
    public function it_supports_common_image_formats()
    {
        $formats = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        foreach ($formats as $format) {
            Storage::fake('public');
            
            $file = UploadedFile::fake()->image("checkpoint.{$format}");

            $response = $this->actingAs($this->user, 'sanctum')
                ->postJson("/api/checkpoints/{$this->checkpoint->id}/images", [
                    'image' => $file,
                ]);

            $response->assertCreated();
            
            $image = CheckpointImage::latest('id')->first();
            $this->assertStringEndsWith(".{$format}", $image->file_path);
        }
    }
}
