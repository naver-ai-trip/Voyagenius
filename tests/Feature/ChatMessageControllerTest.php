<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\ChatSession;
use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for ChatMessage API endpoints.
 * 
 * Following TDD: Tests written FIRST, implementation AFTER.
 * 
 * Test Coverage:
 * - CRUD operations (index, store, show)
 * - Message creation from user and AI
 * - Entity references (polymorphic)
 * - Authorization (only session participants)
 * - Pagination for message history
 * - Status codes and JSON structure
 */
class ChatMessageControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;
    private ChatSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->session = ChatSession::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function user_can_list_messages_in_their_session()
    {
        $messages = ChatMessage::factory()->count(5)->create([
            'chat_session_id' => $this->session->id,
        ]);

        // Create messages in other session (should not appear)
        $otherSession = ChatSession::factory()->create(['user_id' => $this->otherUser->id]);
        ChatMessage::factory()->count(3)->create([
            'chat_session_id' => $otherSession->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/chat-sessions/{$this->session->id}/messages");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'chat_session_id',
                        'from_role',
                        'message',
                        'metadata',
                        'created_at',
                    ]
                ]
            ]);
    }

    /** @test */
    public function user_can_send_message_to_their_session()
    {
        $messageData = [
            'message' => 'I want to plan a trip to Seoul for 5 days',
            'from_role' => 'user',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/chat-sessions/{$this->session->id}/messages", $messageData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'chat_session_id',
                    'from_role',
                    'message',
                    'metadata',
                ]
            ])
            ->assertJson([
                'data' => [
                    'chat_session_id' => $this->session->id,
                    'from_role' => 'user',
                    'message' => 'I want to plan a trip to Seoul for 5 days',
                ]
            ]);

        $this->assertDatabaseHas('chat_messages', [
            'chat_session_id' => $this->session->id,
            'from_role' => 'user',
            'message' => 'I want to plan a trip to Seoul for 5 days',
        ]);
    }

    /** @test */
    public function message_content_is_required()
    {
        $messageData = [
            'from_role' => 'user',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/chat-sessions/{$this->session->id}/messages", $messageData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['message']);
    }

    /** @test */
    public function from_role_must_be_valid()
    {
        $messageData = [
            'message' => 'Test message',
            'from_role' => 'invalid_role',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/chat-sessions/{$this->session->id}/messages", $messageData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_role']);
    }

    /** @test */
    public function from_role_defaults_to_user_if_not_provided()
    {
        $messageData = [
            'message' => 'Test message without role',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/chat-sessions/{$this->session->id}/messages", $messageData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'from_role' => 'user',
                ]
            ]);
    }

    /** @test */
    public function user_can_attach_metadata_to_message()
    {
        $messageData = [
            'message' => 'Test message',
            'metadata' => [
                'device' => 'mobile',
                'location' => 'Seoul',
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/chat-sessions/{$this->session->id}/messages", $messageData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'metadata' => [
                        'device' => 'mobile',
                        'location' => 'Seoul',
                    ],
                ]
            ]);
    }

    /** @test */
    public function user_can_reference_entity_in_message()
    {
        $place = Place::factory()->create();

        $messageData = [
            'message' => 'I want to add this place to my itinerary',
            'entity_type' => Place::class,
            'entity_id' => $place->id,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/chat-sessions/{$this->session->id}/messages", $messageData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'entity_type' => Place::class,
                    'entity_id' => $place->id,
                ]
            ]);

        $this->assertDatabaseHas('chat_messages', [
            'chat_session_id' => $this->session->id,
            'entity_type' => Place::class,
            'entity_id' => $place->id,
        ]);
    }

    /** @test */
    public function user_cannot_send_message_to_other_users_session()
    {
        $otherSession = ChatSession::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $messageData = [
            'message' => 'Unauthorized message',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/chat-sessions/{$otherSession->id}/messages", $messageData);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_view_specific_message()
    {
        $message = ChatMessage::factory()->create([
            'chat_session_id' => $this->session->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/chat-sessions/{$this->session->id}/messages/{$message->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $message->id,
                    'chat_session_id' => $this->session->id,
                ]
            ]);
    }

    /** @test */
    public function messages_are_ordered_by_creation_time()
    {
        $message1 = ChatMessage::factory()->create([
            'chat_session_id' => $this->session->id,
            'created_at' => now()->subMinutes(10),
        ]);
        $message2 = ChatMessage::factory()->create([
            'chat_session_id' => $this->session->id,
            'created_at' => now()->subMinutes(5),
        ]);
        $message3 = ChatMessage::factory()->create([
            'chat_session_id' => $this->session->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/chat-sessions/{$this->session->id}/messages");

        $response->assertStatus(200);

        $messages = $response->json('data');
        $this->assertEquals($message1->id, $messages[0]['id']);
        $this->assertEquals($message2->id, $messages[1]['id']);
        $this->assertEquals($message3->id, $messages[2]['id']);
    }

    /** @test */
    public function can_filter_messages_by_role()
    {
        ChatMessage::factory()->count(3)->fromUser()->create([
            'chat_session_id' => $this->session->id,
        ]);
        ChatMessage::factory()->count(2)->fromAI()->create([
            'chat_session_id' => $this->session->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/chat-sessions/{$this->session->id}/messages?filter[from_role]=user");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        foreach ($response->json('data') as $message) {
            $this->assertEquals('user', $message['from_role']);
        }
    }

    /** @test */
    public function messages_support_pagination()
    {
        ChatMessage::factory()->count(25)->create([
            'chat_session_id' => $this->session->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/chat-sessions/{$this->session->id}/messages?per_page=10");

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure([
                'data',
                'links',
                'meta' => ['current_page', 'total', 'per_page'],
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_messages()
    {
        $response = $this->getJson("/api/chat-sessions/{$this->session->id}/messages");

        $response->assertStatus(401);
    }

    /** @test */
    public function ai_message_includes_confidence_in_metadata()
    {
        $messageData = [
            'message' => 'Based on your preferences, I recommend these places',
            'from_role' => 'ai',
            'metadata' => [
                'confidence' => 0.95,
                'model' => 'agent-v1',
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/chat-sessions/{$this->session->id}/messages", $messageData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'from_role' => 'ai',
                    'metadata' => [
                        'confidence' => 0.95,
                        'model' => 'agent-v1',
                    ],
                ]
            ]);
    }
}
