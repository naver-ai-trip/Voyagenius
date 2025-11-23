<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchFlightOffersRequest;
use App\Services\SerpAPI\FlightService;
use Illuminate\Http\JsonResponse;

/**
 * FlightController
 *
 * Provides endpoints for SerpAPI Google Flights Search.
 */
class FlightController extends Controller
{
    public function __construct(
        private FlightService $flightService,
    ) {}

    /**
     * Search for flight offers (SerpAPI Google Flights API).
     *
     * Searches for flight offers between two airports using SerpAPI's Google Flights integration.
     * Requires airport IATA codes for departure and arrival locations.
     *
     * @OA\Post(
     *     path="/api/flights/search",
     *     summary="Search for flight offers",
     *     description="Searches for flight offers between departure and arrival airports using SerpAPI Google Flights. Requires airport IATA codes (e.g., 'LAX', 'JFK', 'AUS'). Supports both round-trip and one-way flights.",
     *     operationId="searchFlightOffers",
     *     tags={"Flights"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Flight search parameters. Requires airport IATA codes.",
     *         @OA\JsonContent(
     *             required={"departure_id","arrival_id","outbound_date"},
     *             @OA\Property(
     *                 property="departure_id",
     *                 type="string",
     *                 example="LAX",
     *                 description="Departure airport IATA code (e.g., 'LAX', 'JFK', 'NYC'). Must be a valid 3-letter airport code.",
     *                 minLength=3,
     *                 maxLength=3
     *             ),
     *             @OA\Property(
     *                 property="arrival_id",
     *                 type="string",
     *                 example="AUS",
     *                 description="Arrival airport IATA code (e.g., 'AUS', 'SFO', 'PAR'). Must be a valid 3-letter airport code.",
     *                 minLength=3,
     *                 maxLength=3
     *             ),
     *             @OA\Property(
     *                 property="outbound_date",
     *                 type="string",
     *                 format="date",
     *                 example="2025-10-14",
     *                 description="Outbound (departure) date in YYYY-MM-DD format. Must be today or in the future."
     *             ),
     *             @OA\Property(
     *                 property="return_date",
     *                 type="string",
     *                 format="date",
     *                 nullable=true,
     *                 example="2025-10-21",
     *                 description="Return date in YYYY-MM-DD format. Optional for one-way flights. Must be after outbound date if provided."
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Flight offers returned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 description="Array of flight offers",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="string", example="1", description="Flight offer ID"),
     *                     @OA\Property(property="source", type="string", example="GDS", description="Data source"),
     *                     @OA\Property(
     *                         property="itineraries",
     *                         type="array",
     *                         description="Flight itineraries",
     *                         @OA\Items(type="object")
     *                     ),
     *                     @OA\Property(
     *                         property="price",
     *                         type="object",
     *                         description="Price information",
     *                         @OA\Property(property="total", type="string", example="500.00"),
     *                         @OA\Property(property="currency", type="string", example="USD")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="meta",
     *                 type="object",
     *                 description="Response metadata",
     *                 @OA\Property(property="total_offers", type="integer", example=15, description="Total number of offers returned"),
     *                 @OA\Property(property="origin", type="string", example="New York", description="Origin location"),
     *                 @OA\Property(property="destination", type="string", example="Paris", description="Destination location"),
     *                 @OA\Property(property="departure_date", type="string", format="date", example="2025-06-01"),
     *                 @OA\Property(property="return_date", type="string", format="date", nullable=true, example="2025-06-10")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required",
     *         ref="#/components/responses/Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error - Invalid request data",
     *         ref="#/components/responses/ValidationError"
     *     ),
     *     @OA\Response(
     *         response=503,
     *         description="Service unavailable - Flight offers service is currently unavailable",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Flight offers service is currently unavailable"),
     *             @OA\Property(property="data", type="array", @OA\Items(), example=[])
     *         )
     *     )
     * )
     */
    public function searchOffers(SearchFlightOffersRequest $request): JsonResponse
    {
        $departureId = $request->input('departure_id');
        $arrivalId = $request->input('arrival_id');
        $outboundDate = $request->input('outbound_date');
        $returnDate = $request->input('return_date');

        $results = $this->flightService->searchFlightOffers(
            $departureId,
            $arrivalId,
            $outboundDate,
            $returnDate
        );

        if ($results === null) {
            return response()->json([
                'message' => 'Flight offers service is currently unavailable',
                'data' => [],
            ], 503);
        }

        return response()->json([
            'data' => $results,
            'meta' => [
                'total_offers' => count($results),
                'departure_id' => $departureId,
                'arrival_id' => $arrivalId,
                'outbound_date' => $outboundDate,
                'return_date' => $returnDate,
            ],
        ]);
    }
}
