<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for UserPreference API endpoints.
 * 
 * Following TDD: Tests written FIRST, implementation AFTER.
 * 
 * Test Coverage:
 * - CRUD operations (index, store, show, update, destroy)
 * - Preference value validation by type
 * - Priority management
 * - Authorization (only preference owner)
 * - Filtering by type
 * - Default values and overrides
 * - Status codes and JSON structure
 */
class UserPreferenceControllerTest extends TestCase
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
    public function user_can_list_their_preferences()
    {
        $preferences = UserPreference::factory()->count(5)->create([
            'user_id' => $this->user->id,
        ]);

        // Create preferences for other user (should not appear)
        UserPreference::factory()->count(3)->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/user-preferences');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'user_id',
                        'preference_type',
                        'value',
                        'priority',
                        'created_at',
                        'updated_at',
                    ]
                ]
            ]);
    }

    /** @test */
    public function user_can_create_preference()
    {
        $preferenceData = [
            'preference_type' => 'travel_style',
            'value' => [
                'styles' => ['adventure', 'cultural'],
                'primary' => 'adventure',
            ],
            'priority' => 8,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/user-preferences', $preferenceData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'preference_type',
                    'value',
                    'priority',
                ]
            ])
            ->assertJson([
                'data' => [
                    'user_id' => $this->user->id,
                    'preference_type' => 'travel_style',
                    'priority' => 8,
                ]
            ]);

        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $this->user->id,
            'preference_type' => 'travel_style',
            'priority' => 8,
        ]);
    }

    /** @test */
    public function preference_type_is_required()
    {
        $preferenceData = [
            'value' => ['test' => 'data'],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/user-preferences', $preferenceData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['preference_type']);
    }

    /** @test */
    public function preference_value_is_required()
    {
        $preferenceData = [
            'preference_type' => 'travel_style',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/user-preferences', $preferenceData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['value']);
    }

    /** @test */
    public function priority_defaults_to_5_if_not_provided()
    {
        $preferenceData = [
            'preference_type' => 'travel_style',
            'value' => ['styles' => ['adventure']],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/user-preferences', $preferenceData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'priority' => 5,
                ]
            ]);
    }

    /** @test */
    public function priority_must_be_between_1_and_10()
    {
        $preferenceData = [
            'preference_type' => 'travel_style',
            'value' => ['styles' => ['adventure']],
            'priority' => 15,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/user-preferences', $preferenceData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['priority']);
    }

    /** @test */
    public function user_can_view_specific_preference()
    {
        $preference = UserPreference::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/user-preferences/{$preference->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $preference->id,
                    'user_id' => $this->user->id,
                ]
            ]);
    }

    /** @test */
    public function user_cannot_view_other_users_preference()
    {
        $preference = UserPreference::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/user-preferences/{$preference->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_update_preference_value()
    {
        $preference = UserPreference::factory()->create([
            'user_id' => $this->user->id,
            'preference_type' => 'travel_style',
            'value' => ['styles' => ['adventure']],
            'priority' => 5,
        ]);

        $updateData = [
            'value' => ['styles' => ['adventure', 'cultural', 'food']],
            'priority' => 9,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/user-preferences/{$preference->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $preference->id,
                    'value' => ['styles' => ['adventure', 'cultural', 'food']],
                    'priority' => 9,
                ]
            ]);

        $preference->refresh();
        $this->assertEquals(['adventure', 'cultural', 'food'], $preference->value['styles']);
        $this->assertEquals(9, $preference->priority);
    }

    /** @test */
    public function user_cannot_update_other_users_preference()
    {
        $preference = UserPreference::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/user-preferences/{$preference->id}", [
                'value' => ['new' => 'data'],
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_delete_their_preference()
    {
        $preference = UserPreference::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/user-preferences/{$preference->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('user_preferences', [
            'id' => $preference->id,
        ]);
    }

    /** @test */
    public function user_cannot_delete_other_users_preference()
    {
        $preference = UserPreference::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/user-preferences/{$preference->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('user_preferences', [
            'id' => $preference->id,
        ]);
    }

    /** @test */
    public function can_filter_preferences_by_type()
    {
        UserPreference::factory()->count(2)->travelStyle()->create([
            'user_id' => $this->user->id,
        ]);
        UserPreference::factory()->count(3)->budgetRange()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/user-preferences?filter[type]=travel_style');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $pref) {
            $this->assertEquals('travel_style', $pref['preference_type']);
        }
    }

    /** @test */
    public function preferences_are_ordered_by_priority_desc()
    {
        $pref1 = UserPreference::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 5,
        ]);
        $pref2 = UserPreference::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 9,
        ]);
        $pref3 = UserPreference::factory()->create([
            'user_id' => $this->user->id,
            'priority' => 7,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/user-preferences');

        $response->assertStatus(200);

        $preferences = $response->json('data');
        $this->assertEquals($pref2->id, $preferences[0]['id']); // priority 9
        $this->assertEquals($pref3->id, $preferences[1]['id']); // priority 7
        $this->assertEquals($pref1->id, $preferences[2]['id']); // priority 5
    }

    /** @test */
    public function can_create_budget_range_preference()
    {
        $preferenceData = [
            'preference_type' => 'budget_range',
            'value' => [
                'min' => 100000,
                'max' => 1000000,
                'currency' => 'KRW',
                'category' => 'moderate',
            ],
            'priority' => 8,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/user-preferences', $preferenceData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'preference_type' => 'budget_range',
                    'value' => [
                        'min' => 100000,
                        'max' => 1000000,
                        'currency' => 'KRW',
                        'category' => 'moderate',
                    ],
                ]
            ]);
    }

    /** @test */
    public function can_create_dietary_restrictions_preference()
    {
        $preferenceData = [
            'preference_type' => 'dietary_restrictions',
            'value' => [
                'restrictions' => ['vegetarian', 'gluten-free'],
                'allergies' => ['peanuts'],
            ],
            'priority' => 10,
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/user-preferences', $preferenceData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'preference_type' => 'dietary_restrictions',
                    'value' => [
                        'restrictions' => ['vegetarian', 'gluten-free'],
                        'allergies' => ['peanuts'],
                    ],
                    'priority' => 10,
                ]
            ]);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_preferences()
    {
        $response = $this->getJson('/api/user-preferences');

        $response->assertStatus(401);
    }

    /** @test */
    public function user_cannot_have_duplicate_preference_types()
    {
        UserPreference::factory()->create([
            'user_id' => $this->user->id,
            'preference_type' => 'travel_style',
        ]);

        $duplicateData = [
            'preference_type' => 'travel_style',
            'value' => ['styles' => ['cultural']],
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/user-preferences', $duplicateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['preference_type']);
    }
}
