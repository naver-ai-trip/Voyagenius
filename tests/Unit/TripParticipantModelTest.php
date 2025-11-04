<?php

namespace Tests\Unit;

use App\Models\Trip;
use App\Models\TripParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * TripParticipant Model Unit Tests
 *
 * Tests role-based collaboration: owner, editor, viewer roles.
 * Tests constraints, relationships, and permissions logic.
 */
class TripParticipantModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test trip participant can be created with required attributes.
     */
    public function test_trip_participant_can_be_created(): void
    {
        $trip = Trip::factory()->create();
        $user = User::factory()->create();

        $participant = TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
            'role' => 'editor',
        ]);

        $this->assertDatabaseHas('trip_participants', [
            'trip_id' => $trip->id,
            'user_id' => $user->id,
            'role' => 'editor',
        ]);
    }

    /**
     * Test trip participant has correct fillable attributes.
     */
    public function test_trip_participant_has_correct_fillable_attributes(): void
    {
        $participant = new TripParticipant();
        
        $fillable = $participant->getFillable();
        
        $this->assertContains('trip_id', $fillable);
        $this->assertContains('user_id', $fillable);
        $this->assertContains('role', $fillable);
        $this->assertContains('joined_at', $fillable);
    }

    /**
     * Test trip participant belongs to a trip.
     */
    public function test_trip_participant_belongs_to_trip(): void
    {
        $trip = Trip::factory()->create();
        $participant = TripParticipant::factory()->create(['trip_id' => $trip->id]);

        $this->assertInstanceOf(Trip::class, $participant->trip);
        $this->assertEquals($trip->id, $participant->trip->id);
    }

    /**
     * Test trip participant belongs to a user.
     */
    public function test_trip_participant_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $participant = TripParticipant::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $participant->user);
        $this->assertEquals($user->id, $participant->user->id);
    }

    /**
     * Test valid roles: owner, editor, viewer.
     */
    public function test_trip_participant_can_have_valid_roles(): void
    {
        $trip = Trip::factory()->create();
        $users = User::factory()->count(3)->create();

        $roles = ['owner', 'editor', 'viewer'];
        
        foreach ($roles as $index => $role) {
            $participant = TripParticipant::factory()->create([
                'trip_id' => $trip->id,
                'user_id' => $users[$index]->id,
                'role' => $role,
            ]);
            
            $this->assertEquals($role, $participant->role);
        }
    }

    /**
     * Test trip can have multiple participants.
     */
    public function test_trip_can_have_multiple_participants(): void
    {
        $trip = Trip::factory()->create();
        
        TripParticipant::factory()->count(5)->create([
            'trip_id' => $trip->id,
        ]);

        $this->assertCount(5, $trip->participants);
    }

    /**
     * Test user can participate in multiple trips.
     */
    public function test_user_can_participate_in_multiple_trips(): void
    {
        $user = User::factory()->create();
        $trips = Trip::factory()->count(3)->create();

        foreach ($trips as $trip) {
            TripParticipant::factory()->create([
                'trip_id' => $trip->id,
                'user_id' => $user->id,
            ]);
        }

        $this->assertCount(3, TripParticipant::where('user_id', $user->id)->get());
    }

    /**
     * Test same user cannot participate in same trip twice (unique constraint).
     */
    public function test_user_cannot_join_same_trip_twice(): void
    {
        $trip = Trip::factory()->create();
        $user = User::factory()->create();

        TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        TripParticipant::factory()->create([
            'trip_id' => $trip->id,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Test joined_at is automatically set.
     */
    public function test_joined_at_is_set_automatically(): void
    {
        $participant = TripParticipant::factory()->create();

        $this->assertNotNull($participant->joined_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $participant->joined_at);
    }

    /**
     * Test scope to get participants by role.
     */
    public function test_can_filter_participants_by_role(): void
    {
        $trip = Trip::factory()->create();
        
        TripParticipant::factory()->create(['trip_id' => $trip->id, 'role' => 'owner']);
        TripParticipant::factory()->count(2)->create(['trip_id' => $trip->id, 'role' => 'editor']);
        TripParticipant::factory()->count(3)->create(['trip_id' => $trip->id, 'role' => 'viewer']);

        $owners = TripParticipant::where('trip_id', $trip->id)->role('owner')->get();
        $editors = TripParticipant::where('trip_id', $trip->id)->role('editor')->get();
        $viewers = TripParticipant::where('trip_id', $trip->id)->role('viewer')->get();

        $this->assertCount(1, $owners);
        $this->assertCount(2, $editors);
        $this->assertCount(3, $viewers);
    }

    /**
     * Test helper method to check if user is owner.
     */
    public function test_can_check_if_participant_is_owner(): void
    {
        $owner = TripParticipant::factory()->create(['role' => 'owner']);
        $editor = TripParticipant::factory()->create(['role' => 'editor']);

        $this->assertTrue($owner->isOwner());
        $this->assertFalse($editor->isOwner());
    }

    /**
     * Test helper method to check if user can edit.
     */
    public function test_can_check_if_participant_can_edit(): void
    {
        $owner = TripParticipant::factory()->create(['role' => 'owner']);
        $editor = TripParticipant::factory()->create(['role' => 'editor']);
        $viewer = TripParticipant::factory()->create(['role' => 'viewer']);

        $this->assertTrue($owner->canEdit());
        $this->assertTrue($editor->canEdit());
        $this->assertFalse($viewer->canEdit());
    }

    /**
     * Test trip_id is required.
     */
    public function test_trip_id_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        TripParticipant::create([
            'user_id' => User::factory()->create()->id,
            'role' => 'editor',
        ]);
    }

    /**
     * Test user_id is required.
     */
    public function test_user_id_is_required(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        TripParticipant::create([
            'trip_id' => Trip::factory()->create()->id,
            'role' => 'editor',
        ]);
    }
}
