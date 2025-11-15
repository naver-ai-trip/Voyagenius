<?php

namespace Tests\Feature;

use App\Models\AgentAction;
use App\Models\ChatSession;
use App\Models\Place;
use App\Models\Trip;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for AgentAction API endpoints.
 * 
 * Following TDD: Tests written FIRST, implementation AFTER.
 * 
 * Test Coverage:
 * - Listing actions for session
 * - Action creation and execution tracking
 * - Status transitions (pending -> completed/failed)
 * - Authorization (only session owner)
 * - Entity references (polymorphic)
 * - Error handling and retry logic
 * - Status codes and JSON structure
 */
class AgentActionControllerTest extends TestCase
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
    public function user_can_list_actions_for_their_session()
    {
        $actions = AgentAction::factory()->count(5)->create([
            'chat_session_id' => $this->session->id,
        ]);

        // Create actions for other session (should not appear)
        $otherSession = ChatSession::factory()->create(['user_id' => $this->otherUser->id]);
        AgentAction::factory()->count(3)->create([
            'chat_session_id' => $otherSession->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/chat-sessions/{$this->session->id}/actions");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'chat_session_id',
                        'action_type',
                        'status',
                        'input_data',
                        'output_data',
                        'error_message',
                        'started_at',
                        'completed_at',
                        'created_at',
                    ]
                ]
            ]);
    }

    /** @test */
    public function user_cannot_access_actions_for_other_users_session()
    {
        $otherSession = ChatSession::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/chat-sessions/{$otherSession->id}/actions");

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_view_specific_action()
    {
        $action = AgentAction::factory()->create([
            'chat_session_id' => $this->session->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/chat-sessions/{$this->session->id}/actions/{$action->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $action->id,
                    'chat_session_id' => $this->session->id,
                ]
            ]);
    }

    /** @test */
    public function can_create_agent_action()
    {
        $actionData = [
            'action_type' => 'search_places',
            'input_data' => [
                'query' => 'restaurants in Seoul',
                'category' => 'restaurant',
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/chat-sessions/{$this->session->id}/actions", $actionData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'chat_session_id',
                    'action_type',
                    'status',
                    'input_data',
                    'started_at',
                ]
            ])
            ->assertJson([
                'data' => [
                    'chat_session_id' => $this->session->id,
                    'action_type' => 'search_places',
                    'status' => 'pending',
                ]
            ]);

        $this->assertDatabaseHas('agent_actions', [
            'chat_session_id' => $this->session->id,
            'action_type' => 'search_places',
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function action_type_is_required()
    {
        $actionData = [
            'input_data' => ['query' => 'test'],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/chat-sessions/{$this->session->id}/actions", $actionData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['action_type']);
    }

    /** @test */
    public function can_mark_action_as_completed()
    {
        $action = AgentAction::factory()->pending()->create([
            'chat_session_id' => $this->session->id,
        ]);

        $outputData = [
            'output_data' => [
                'results_count' => 25,
                'places' => ['Place 1', 'Place 2'],
            ],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/actions/{$action->id}/complete", $outputData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $action->id,
                    'status' => 'completed',
                ]
            ]);

        $this->assertDatabaseHas('agent_actions', [
            'id' => $action->id,
            'status' => 'completed',
        ]);

        $action->refresh();
        $this->assertNotNull($action->completed_at);
        $this->assertNull($action->error_message);
    }

    /** @test */
    public function can_mark_action_as_failed()
    {
        $action = AgentAction::factory()->pending()->create([
            'chat_session_id' => $this->session->id,
        ]);

        $errorData = [
            'error_message' => 'API rate limit exceeded',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/actions/{$action->id}/fail", $errorData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $action->id,
                    'status' => 'failed',
                    'error_message' => 'API rate limit exceeded',
                ]
            ]);

        $this->assertDatabaseHas('agent_actions', [
            'id' => $action->id,
            'status' => 'failed',
            'error_message' => 'API rate limit exceeded',
        ]);
    }

    /** @test */
    public function can_attach_entity_to_action()
    {
        $trip = Trip::factory()->create(['user_id' => $this->user->id]);

        $actionData = [
            'action_type' => 'create_trip',
            'input_data' => ['destination' => 'Seoul'],
            'entity_type' => Trip::class,
            'entity_id' => $trip->id,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/chat-sessions/{$this->session->id}/actions", $actionData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'entity_type' => Trip::class,
                    'entity_id' => $trip->id,
                ]
            ]);
    }

    /** @test */
    public function can_filter_actions_by_status()
    {
        AgentAction::factory()->count(2)->pending()->create([
            'chat_session_id' => $this->session->id,
        ]);
        AgentAction::factory()->count(3)->completed()->create([
            'chat_session_id' => $this->session->id,
        ]);
        AgentAction::factory()->count(1)->failed()->create([
            'chat_session_id' => $this->session->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/chat-sessions/{$this->session->id}/actions?filter[status]=completed");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');

        foreach ($response->json('data') as $action) {
            $this->assertEquals('completed', $action['status']);
        }
    }

    /** @test */
    public function can_filter_actions_by_type()
    {
        AgentAction::factory()->count(2)->createTrip()->create([
            'chat_session_id' => $this->session->id,
        ]);
        AgentAction::factory()->count(3)->searchPlaces()->create([
            'chat_session_id' => $this->session->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/chat-sessions/{$this->session->id}/actions?filter[action_type]=create_trip");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $action) {
            $this->assertEquals('create_trip', $action['action_type']);
        }
    }

    /** @test */
    public function actions_are_ordered_by_creation_time_desc()
    {
        $action1 = AgentAction::factory()->create([
            'chat_session_id' => $this->session->id,
            'created_at' => now()->subMinutes(30),
        ]);
        $action2 = AgentAction::factory()->create([
            'chat_session_id' => $this->session->id,
            'created_at' => now()->subMinutes(15),
        ]);
        $action3 = AgentAction::factory()->create([
            'chat_session_id' => $this->session->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/chat-sessions/{$this->session->id}/actions");

        $response->assertStatus(200);

        $actions = $response->json('data');
        $this->assertEquals($action3->id, $actions[0]['id']);
        $this->assertEquals($action2->id, $actions[1]['id']);
        $this->assertEquals($action1->id, $actions[2]['id']);
    }

    /** @test */
    public function completed_action_calculates_execution_time()
    {
        $action = AgentAction::factory()->create([
            'chat_session_id' => $this->session->id,
            'status' => 'pending',
            'started_at' => now()->subSeconds(10),
            'completed_at' => null,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/actions/{$action->id}/complete", [
                'output_data' => ['result' => 'success'],
            ]);

        $response->assertStatus(200);

        $action->refresh();
        $this->assertNotNull($action->completed_at);
        
        $executionTime = $action->completed_at->diffInSeconds($action->started_at);
        $this->assertGreaterThanOrEqual(10, $executionTime);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_actions()
    {
        $response = $this->getJson("/api/chat-sessions/{$this->session->id}/actions");

        $response->assertStatus(401);
    }

    /** @test */
    public function action_input_and_output_data_are_json()
    {
        $action = AgentAction::factory()->create([
            'chat_session_id' => $this->session->id,
            'input_data' => [
                'query' => 'Seoul restaurants',
                'filters' => ['rating' => 4.5],
            ],
            'output_data' => [
                'results' => ['Restaurant A', 'Restaurant B'],
                'total' => 2,
            ],
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/chat-sessions/{$this->session->id}/actions/{$action->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'input_data' => [
                        'query' => 'Seoul restaurants',
                        'filters' => ['rating' => 4.5],
                    ],
                    'output_data' => [
                        'results' => ['Restaurant A', 'Restaurant B'],
                        'total' => 2,
                    ],
                ]
            ]);
    }
}
