<?php

namespace Tests\Feature;

use App\Models\ChatSession;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for ChatSession API endpoints.
 * 
 * Following TDD: Tests written FIRST, implementation AFTER.
 * 
 * Test Coverage:
 * - CRUD operations (index, store, show, update, destroy)
 * - Session activation/deactivation
 * - Context management
 * - Authorization (only session owner can access/modify)
 * - Relationships (messages, actions loaded)
 * - Status codes and JSON structure
 */
class ChatSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    /** @test */
    public function user_can_list_their_chat_sessions()
    {
        // Create sessions for authenticated user
        $userSessions = ChatSession::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Create sessions for other user (should not appear)
        ChatSession::factory()->count(2)->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/chat-sessions');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'trip_id',
                        'session_type',
                        'context',
                        'is_active',
                        'started_at',
                        'ended_at',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);

        // Verify only user's sessions returned
        $returnedIds = collect($response->json('data'))->pluck('id')->toArray();
        $expectedIds = $userSessions->pluck('id')->toArray();
        
        $this->assertEquals(sort($expectedIds), sort($returnedIds));
    }

    /** @test */
    public function user_can_create_chat_session()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $sessionData = [
            'trip_id' => $trip->id,
            'session_type' => 'trip_planning',
            'context' => [
                'destination' => 'Seoul, South Korea',
                'budget_range' => 'moderate',
                'travel_style' => 'cultural',
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/chat-sessions', $sessionData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'trip_id',
                    'session_type',
                    'context',
                    'is_active',
                    'started_at',
                ]
            ])
            ->assertJson([
                'data' => [
                    'user_id' => $this->user->id,
                    'trip_id' => $trip->id,
                    'session_type' => 'trip_planning',
                    'is_active' => true,
                ]
            ]);

        $this->assertDatabaseHas('chat_sessions', [
            'user_id' => $this->user->id,
            'trip_id' => $trip->id,
            'session_type' => 'trip_planning',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function user_can_create_session_without_trip()
    {
        $sessionData = [
            'session_type' => 'trip_planning',
            'context' => [
                'destination' => 'Seoul, South Korea',
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/chat-sessions', $sessionData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'user_id' => $this->user->id,
                    'trip_id' => null,
                    'session_type' => 'trip_planning',
                ]
            ]);
    }

    /** @test */
    public function session_type_is_required()
    {
        $sessionData = [
            'context' => ['destination' => 'Seoul'],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/chat-sessions', $sessionData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['session_type']);
    }

    /** @test */
    public function session_type_must_be_valid()
    {
        $sessionData = [
            'session_type' => 'invalid_type',
            'context' => ['destination' => 'Seoul'],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/chat-sessions', $sessionData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['session_type']);
    }

    /** @test */
    public function user_can_view_their_chat_session()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/chat-sessions/{$session->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $session->id,
                    'user_id' => $this->user->id,
                ]
            ]);
    }

    /** @test */
    public function user_cannot_view_other_users_chat_session()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/chat-sessions/{$session->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_update_session_context()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
            'context' => ['destination' => 'Seoul'],
        ]);

        $updateData = [
            'context' => [
                'destination' => 'Busan, South Korea',
                'budget_range' => 'luxury',
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/chat-sessions/{$session->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $session->id,
                    'context' => [
                        'destination' => 'Busan, South Korea',
                        'budget_range' => 'luxury',
                    ],
                ]
            ]);

        $this->assertDatabaseHas('chat_sessions', [
            'id' => $session->id,
        ]);

        $session->refresh();
        $this->assertEquals('Busan, South Korea', $session->context['destination']);
    }

    /** @test */
    public function user_can_deactivate_session()
    {
        $session = ChatSession::factory()->active()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/chat-sessions/{$session->id}/deactivate");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $session->id,
                    'is_active' => false,
                ]
            ]);

        $this->assertDatabaseHas('chat_sessions', [
            'id' => $session->id,
            'is_active' => false,
        ]);

        $session->refresh();
        $this->assertNotNull($session->ended_at);
    }

    /** @test */
    public function user_can_reactivate_session()
    {
        $session = ChatSession::factory()->ended()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/chat-sessions/{$session->id}/activate");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $session->id,
                    'is_active' => true,
                ]
            ]);

        $this->assertDatabaseHas('chat_sessions', [
            'id' => $session->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function user_cannot_update_other_users_session()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/chat-sessions/{$session->id}", [
                'context' => ['new' => 'data'],
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_delete_their_session()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/chat-sessions/{$session->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('chat_sessions', [
            'id' => $session->id,
        ]);
    }

    /** @test */
    public function user_cannot_delete_other_users_session()
    {
        $session = ChatSession::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/chat-sessions/{$session->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('chat_sessions', [
            'id' => $session->id,
        ]);
    }

    /** @test */
    public function can_filter_sessions_by_active_status()
    {
        ChatSession::factory()->count(2)->active()->create([
            'user_id' => $this->user->id,
        ]);
        ChatSession::factory()->count(3)->ended()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/chat-sessions?filter[is_active]=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $session) {
            $this->assertTrue($session['is_active']);
        }
    }

    /** @test */
    public function can_filter_sessions_by_trip()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);
        
        ChatSession::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'trip_id' => $trip->id,
        ]);
        ChatSession::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'trip_id' => null,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/chat-sessions?filter[trip_id]={$trip->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $session) {
            $this->assertEquals($trip->id, $session['trip_id']);
        }
    }

    /** @test */
    public function unauthenticated_user_cannot_access_sessions()
    {
        $response = $this->getJson('/api/chat-sessions');

        $response->assertStatus(401);
    }

    /** @test */
    public function session_includes_messages_when_requested()
    {
        $session = ChatSession::factory()
            ->has(\App\Models\ChatMessage::factory()->count(3))
            ->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/chat-sessions/{$session->id}?include=messages");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'messages' => [
                        '*' => ['id', 'from_role', 'message']
                    ]
                ]
            ])
            ->assertJsonCount(3, 'data.messages');
    }

    /** @test */
    public function session_includes_actions_when_requested()
    {
        $session = ChatSession::factory()
            ->has(\App\Models\AgentAction::factory()->count(2))
            ->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/chat-sessions/{$session->id}?include=actions");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'actions' => [
                        '*' => ['id', 'action_type', 'status']
                    ]
                ]
            ])
            ->assertJsonCount(2, 'data.actions');
    }

    /** @test */
    public function user_cannot_create_chat_session_for_other_users_trip()
    {
        // Create a trip owned by another user
        $otherUserTrip = Trip::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $sessionData = [
            'trip_id' => $otherUserTrip->id,
            'session_type' => 'trip_planning',
            'context' => [
                'destination' => 'Seoul',
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/chat-sessions', $sessionData);

        // Should return 403 Forbidden
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'You do not have permission to create a chat session for this trip. You must be the trip owner or a participant.',
            ]);

        // Verify session was NOT created
        $this->assertDatabaseMissing('chat_sessions', [
            'trip_id' => $otherUserTrip->id,
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function trip_participant_can_create_chat_session()
    {
        // Create a trip owned by other user
        $trip = Trip::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        // Add current user as participant
        $trip->participants()->create([
            'user_id' => $this->user->id,
            'role' => 'editor',
        ]);

        $sessionData = [
            'trip_id' => $trip->id,
            'session_type' => 'itinerary_building',
            'context' => [
                'destination' => 'Busan',
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/chat-sessions', $sessionData);

        // Should succeed since user is a participant
        $response->assertStatus(201)
            ->assertJsonPath('data.trip_id', $trip->id)
            ->assertJsonPath('data.user_id', $this->user->id);

        // Verify session was created
        $this->assertDatabaseHas('chat_sessions', [
            'trip_id' => $trip->id,
            'user_id' => $this->user->id,
            'session_type' => 'itinerary_building',
        ]);
    }

    /** @test */
    public function user_can_create_chat_session_without_trip()
    {
        // Creating session without trip_id should work
        $sessionData = [
            'session_type' => 'place_search',
            'context' => [
                'query' => 'Korean restaurants',
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/chat-sessions', $sessionData);

        $response->assertStatus(201)
            ->assertJsonPath('data.trip_id', null)
            ->assertJsonPath('data.user_id', $this->user->id);

        $this->assertDatabaseHas('chat_sessions', [
            'user_id' => $this->user->id,
            'trip_id' => null,
            'session_type' => 'place_search',
        ]);
    }
}
