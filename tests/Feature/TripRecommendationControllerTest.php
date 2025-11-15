<?php

namespace Tests\Feature;

use App\Models\Trip;
use App\Models\TripRecommendation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for TripRecommendation API endpoints.
 * 
 * Following TDD: Tests written FIRST, implementation AFTER.
 * 
 * Test Coverage:
 * - CRUD operations (index, show)
 * - Recommendation acceptance/rejection
 * - Status transitions (pending -> accepted/rejected)
 * - Authorization (only trip owner/participants)
 * - Filtering by status, type, confidence
 * - Status codes and JSON structure
 */
class TripRecommendationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;
    private Trip $trip;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->trip = Trip::factory()->create([
            'user_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function user_can_list_recommendations_for_their_trip()
    {
        $recommendations = TripRecommendation::factory()->count(3)->create([
            'trip_id' => $this->trip->id,
        ]);

        // Create recommendations for other trip (should not appear)
        $otherTrip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        TripRecommendation::factory()->count(2)->create([
            'trip_id' => $otherTrip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$this->trip->id}/recommendations");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'trip_id',
                        'recommendation_type',
                        'data',
                        'confidence_score',
                        'status',
                        'applied_by',
                        'applied_at',
                        'created_at',
                    ]
                ]
            ]);
    }

    /** @test */
    public function user_cannot_access_recommendations_for_other_users_trip()
    {
        $otherTrip = Trip::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$otherTrip->id}/recommendations");

        $response->assertStatus(403);
    }

    /** @test */
    public function user_can_view_specific_recommendation()
    {
        $recommendation = TripRecommendation::factory()->create([
            'trip_id' => $this->trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$this->trip->id}/recommendations/{$recommendation->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $recommendation->id,
                    'trip_id' => $this->trip->id,
                ]
            ]);
    }

    /** @test */
    public function user_can_accept_recommendation()
    {
        $recommendation = TripRecommendation::factory()->pending()->create([
            'trip_id' => $this->trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/recommendations/{$recommendation->id}/accept");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $recommendation->id,
                    'status' => 'accepted',
                    'applied_by' => $this->user->id,
                ]
            ]);

        $this->assertDatabaseHas('trip_recommendations', [
            'id' => $recommendation->id,
            'status' => 'accepted',
            'applied_by' => $this->user->id,
        ]);

        $recommendation->refresh();
        $this->assertNotNull($recommendation->applied_at);
    }

    /** @test */
    public function user_can_reject_recommendation()
    {
        $recommendation = TripRecommendation::factory()->pending()->create([
            'trip_id' => $this->trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/recommendations/{$recommendation->id}/reject");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $recommendation->id,
                    'status' => 'rejected',
                    'applied_by' => $this->user->id,
                ]
            ]);

        $this->assertDatabaseHas('trip_recommendations', [
            'id' => $recommendation->id,
            'status' => 'rejected',
            'applied_by' => $this->user->id,
        ]);
    }

    /** @test */
    public function cannot_accept_already_accepted_recommendation()
    {
        $recommendation = TripRecommendation::factory()->accepted()->create([
            'trip_id' => $this->trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/recommendations/{$recommendation->id}/accept");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Recommendation has already been processed',
            ]);
    }

    /** @test */
    public function cannot_reject_already_rejected_recommendation()
    {
        $recommendation = TripRecommendation::factory()->rejected()->create([
            'trip_id' => $this->trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/recommendations/{$recommendation->id}/reject");

        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Recommendation has already been processed',
            ]);
    }

    /** @test */
    public function user_cannot_accept_recommendation_for_other_users_trip()
    {
        $otherTrip = Trip::factory()->create(['user_id' => $this->otherUser->id]);
        $recommendation = TripRecommendation::factory()->pending()->create([
            'trip_id' => $otherTrip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/recommendations/{$recommendation->id}/accept");

        $response->assertStatus(403);
    }

    /** @test */
    public function can_filter_recommendations_by_status()
    {
        TripRecommendation::factory()->count(2)->pending()->create([
            'trip_id' => $this->trip->id,
        ]);
        TripRecommendation::factory()->count(3)->accepted()->create([
            'trip_id' => $this->trip->id,
        ]);
        TripRecommendation::factory()->count(1)->rejected()->create([
            'trip_id' => $this->trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$this->trip->id}/recommendations?filter[status]=pending");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $rec) {
            $this->assertEquals('pending', $rec['status']);
        }
    }

    /** @test */
    public function can_filter_recommendations_by_type()
    {
        TripRecommendation::factory()->count(2)->place()->create([
            'trip_id' => $this->trip->id,
        ]);
        TripRecommendation::factory()->count(3)->itinerary()->create([
            'trip_id' => $this->trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$this->trip->id}/recommendations?filter[type]=place");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $rec) {
            $this->assertEquals('place', $rec['recommendation_type']);
        }
    }

    /** @test */
    public function can_filter_recommendations_by_minimum_confidence()
    {
        TripRecommendation::factory()->create([
            'trip_id' => $this->trip->id,
            'confidence_score' => 0.95,
        ]);
        TripRecommendation::factory()->create([
            'trip_id' => $this->trip->id,
            'confidence_score' => 0.85,
        ]);
        TripRecommendation::factory()->create([
            'trip_id' => $this->trip->id,
            'confidence_score' => 0.65,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$this->trip->id}/recommendations?filter[min_confidence]=0.80");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        foreach ($response->json('data') as $rec) {
            $this->assertGreaterThanOrEqual(0.80, $rec['confidence_score']);
        }
    }

    /** @test */
    public function recommendations_are_ordered_by_confidence_desc()
    {
        $rec1 = TripRecommendation::factory()->create([
            'trip_id' => $this->trip->id,
            'confidence_score' => 0.75,
        ]);
        $rec2 = TripRecommendation::factory()->create([
            'trip_id' => $this->trip->id,
            'confidence_score' => 0.95,
        ]);
        $rec3 = TripRecommendation::factory()->create([
            'trip_id' => $this->trip->id,
            'confidence_score' => 0.85,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$this->trip->id}/recommendations");

        $response->assertStatus(200);

        $recommendations = $response->json('data');
        $this->assertEquals($rec2->id, $recommendations[0]['id']); // 0.95
        $this->assertEquals($rec3->id, $recommendations[1]['id']); // 0.85
        $this->assertEquals($rec1->id, $recommendations[2]['id']); // 0.75
    }

    /** @test */
    public function unauthenticated_user_cannot_access_recommendations()
    {
        $response = $this->getJson("/api/trips/{$this->trip->id}/recommendations");

        $response->assertStatus(401);
    }

    /** @test */
    public function recommendation_data_structure_varies_by_type()
    {
        $placeRec = TripRecommendation::factory()->place()->create([
            'trip_id' => $this->trip->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/trips/{$this->trip->id}/recommendations/{$placeRec->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'recommendation_type',
                    'data' => [
                        'name',
                        'category',
                        'address',
                        'reason',
                        'estimated_cost',
                    ],
                ]
            ]);
    }
}
