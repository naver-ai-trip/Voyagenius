<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NotificationControllerTest extends TestCase
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
    public function it_lists_user_notifications_paginated()
    {
        // Create some notifications for the authenticated user
        Notification::factory()->count(20)->create(['user_id' => $this->user->id]);
        
        // Create notifications for another user (should not appear)
        Notification::factory()->count(5)->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'user_id', 'type', 'content', 'data', 'read_at', 'created_at', 'updated_at']
                ],
                'links',
                'meta' => ['current_page', 'per_page', 'total']
            ])
            ->assertJsonCount(15, 'data'); // Default pagination

        // Verify only user's notifications returned
        $this->assertEquals($this->user->id, $response->json('data.0.user_id'));
    }

    /** @test */
    public function it_requires_authentication_to_list_notifications()
    {
        $response = $this->getJson('/api/notifications');

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_filters_notifications_by_type()
    {
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'trip_invite'
        ]);
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'comment_added'
        ]);
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'trip_invite'
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/notifications?type=trip_invite');

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        // Verify all returned notifications are of the requested type
        $types = collect($response->json('data'))->pluck('type')->unique();
        $this->assertCount(1, $types);
        $this->assertEquals('trip_invite', $types->first());
    }

    /** @test */
    public function it_filters_notifications_by_read_status()
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => null
        ]);
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'read_at' => now()
        ]);

        // Filter for unread
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/notifications?unread=1');

        $response->assertOk()
            ->assertJsonCount(3, 'data');

        // Verify all are unread
        foreach ($response->json('data') as $notification) {
            $this->assertNull($notification['read_at']);
        }

        // Filter for read
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/notifications?unread=0');

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        // Verify all are read
        foreach ($response->json('data') as $notification) {
            $this->assertNotNull($notification['read_at']);
        }
    }

    /** @test */
    public function it_returns_unread_count()
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'read_at' => null
        ]);
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'read_at' => now()
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/notifications/unread-count');

        $response->assertOk()
            ->assertJson(['unread_count' => 5]);
    }

    /** @test */
    public function it_requires_authentication_to_get_unread_count()
    {
        $response = $this->getJson('/api/notifications/unread-count');

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_marks_a_single_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'read_at' => null
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/notifications/{$notification->id}/mark-read");

        $response->assertOk()
            ->assertJsonFragment(['id' => $notification->id]);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    /** @test */
    public function it_requires_authentication_to_mark_notification_as_read()
    {
        $notification = Notification::factory()->create(['user_id' => $this->user->id]);

        $response = $this->patchJson("/api/notifications/{$notification->id}/mark-read");

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_prevents_marking_another_users_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->otherUser->id,
            'read_at' => null
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/notifications/{$notification->id}/mark-read");

        $response->assertForbidden();

        // Verify notification was not marked as read
        $this->assertNull($notification->fresh()->read_at);
    }

    /** @test */
    public function it_marks_all_notifications_as_read()
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'read_at' => null
        ]);

        // Create notifications for another user (should not be affected)
        Notification::factory()->count(2)->create([
            'user_id' => $this->otherUser->id,
            'read_at' => null
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/notifications/mark-all-read');

        $response->assertOk()
            ->assertJson(['message' => 'All notifications marked as read', 'count' => 5]);

        // Verify all user's notifications are marked as read
        $unreadCount = Notification::where('user_id', $this->user->id)
            ->whereNull('read_at')
            ->count();
        $this->assertEquals(0, $unreadCount);

        // Verify other user's notifications are unaffected
        $otherUserUnread = Notification::where('user_id', $this->otherUser->id)
            ->whereNull('read_at')
            ->count();
        $this->assertEquals(2, $otherUserUnread);
    }

    /** @test */
    public function it_requires_authentication_to_mark_all_as_read()
    {
        $response = $this->postJson('/api/notifications/mark-all-read');

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_deletes_a_notification()
    {
        $notification = Notification::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    /** @test */
    public function it_requires_authentication_to_delete_notification()
    {
        $notification = Notification::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/notifications/{$notification->id}");

        $response->assertUnauthorized();
    }

    /** @test */
    public function it_prevents_deleting_another_users_notification()
    {
        $notification = Notification::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/notifications/{$notification->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_notification()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/notifications/99999');

        $response->assertNotFound();
    }

    /** @test */
    public function it_orders_notifications_by_created_at_descending()
    {
        $notification1 = Notification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(3)
        ]);
        $notification2 = Notification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(1)
        ]);
        $notification3 = Notification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(2)
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/notifications');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->toArray();
        
        // Most recent first
        $this->assertEquals([$notification2->id, $notification3->id, $notification1->id], $ids);
    }

    /** @test */
    public function it_includes_json_data_field_in_response()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'data' => ['trip_id' => 123, 'trip_name' => 'Tokyo Adventure']
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonFragment([
                'data' => ['trip_id' => 123, 'trip_name' => 'Tokyo Adventure']
            ]);
    }

    /** @test */
    public function it_handles_null_data_field()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'data' => null
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/notifications');

        $response->assertOk();

        $notificationData = collect($response->json('data'))
            ->firstWhere('id', $notification->id);

        $this->assertNull($notificationData['data']);
    }

    /** @test */
    public function it_shows_a_single_notification()
    {
        $notification = Notification::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/notifications/{$notification->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'user_id', 'type', 'content', 'data', 'read_at', 'created_at', 'updated_at']
            ])
            ->assertJsonFragment(['id' => $notification->id]);
    }

    /** @test */
    public function it_prevents_viewing_another_users_notification()
    {
        $notification = Notification::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/notifications/{$notification->id}");

        $response->assertForbidden();
    }
}
