<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateHotelBookingRequest;
use App\Http\Requests\GetHotelRatingsRequest;
use App\Http\Requests\SearchHotelOffersRequest;
use App\Http\Requests\SearchHotelsRequest;
use App\Http\Requests\SearchHotelsWithOffersRequest;
use App\Services\Amadeus\HotelService;
use Illuminate\Http\JsonResponse;

/**
 * HotelController
 *
 * Handles hotel search, ratings, and booking using Amadeus Hotel APIs.
 */
class HotelController extends Controller
{
    public function __construct(
        private HotelService $hotelService
    ) {}

    /**
     * Search for hotels by city, geocode, or hotel IDs.
     *
     * @OA\Post(
     *     path="/api/hotels/search",
     *     summary="Search for hotels by city, geocode, or hotel IDs",
     *     tags={"Hotels"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"search_type"},
     *             @OA\Property(property="search_type", type="string", enum={"city", "geocode", "hotel_ids"}, example="city"),
     *             @OA\Property(property="city_code", type="string", example="NYC", description="Required if search_type is city"),
     *             @OA\Property(property="latitude", type="number", format="double", example=40.7128, description="Required if search_type is geocode"),
     *             @OA\Property(property="longitude", type="number", format="double", example=-74.0060, description="Required if search_type is geocode"),
     *             @OA\Property(property="radius", type="integer", example=5, description="Search radius (default: 5)"),
     *             @OA\Property(property="radius_unit", type="string", enum={"KM", "MILE"}, example="KM", description="Radius unit"),
     *             @OA\Property(property="hotel_ids", type="array", @OA\Items(type="string"), example={"RTPAR001", "RTPAR002"}, description="Required if search_type is hotel_ids"),
     *             @OA\Property(property="hotel_source", type="string", enum={"ALL", "GDS"}, example="ALL")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Hotel search results",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Hotel")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="search_type", type="string", example="city"),
     *                 @OA\Property(property="total", type="integer", example=25)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=503, ref="#/components/responses/ServiceUnavailable")
     * )
     */
    public function search(SearchHotelsRequest $request): JsonResponse
    {
        $searchType = $request->input('search_type');
        $results = null;

        switch ($searchType) {
            case 'city':
                $results = $this->hotelService->searchHotelsByCity(
                    $request->input('city_code'),
                    array_filter([
                        'hotelSource' => $request->input('hotel_source'),
                    ])
                );
                break;

            case 'geocode':
                $results = $this->hotelService->searchHotelsByGeocode(
                    $request->input('latitude'),
                    $request->input('longitude'),
                    $request->input('radius', 5),
                    $request->input('radius_unit', 'KM')
                );
                break;

            case 'hotel_ids':
                $results = $this->hotelService->searchHotelsByIds(
                    $request->input('hotel_ids')
                );
                break;
        }

        if ($results === null) {
            return response()->json([
                'message' => 'Hotel search service is currently unavailable',
                'data' => []
            ], 503);
        }

        return response()->json([
            'data' => $results,
            'meta' => [
                'search_type' => $searchType,
                'total' => count($results)
            ]
        ]);
    }

    /**
     * Search for hotel offers (availability and pricing).
     *
     * @OA\Post(
     *     path="/api/hotels/offers",
     *     summary="Search for hotel offers with availability and pricing",
     *     tags={"Hotels"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"hotel_ids", "check_in_date", "check_out_date"},
     *             @OA\Property(property="hotel_ids", type="array", @OA\Items(type="string"), example={"RTPAR001", "RTPAR002"}),
     *             @OA\Property(property="check_in_date", type="string", format="date", example="2024-12-25"),
     *             @OA\Property(property="check_out_date", type="string", format="date", example="2024-12-27"),
     *             @OA\Property(property="adults", type="integer", example=2, description="Number of adults (default: 1)"),
     *             @OA\Property(property="room_quantity", type="integer", example=1, description="Number of rooms (default: 1)"),
     *             @OA\Property(property="currency", type="string", example="USD", description="Currency code (3 letters)"),
     *             @OA\Property(property="price_range", type="string", example="100-500", description="Price range filter"),
     *             @OA\Property(property="payment_policy", type="string", enum={"NONE", "GUARANTEE", "DEPOSIT"}),
     *             @OA\Property(property="board_type", type="string", enum={"ROOM_ONLY", "BREAKFAST", "HALF_BOARD", "FULL_BOARD", "ALL_INCLUSIVE"}),
     *             @OA\Property(property="include_closed", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Hotel offers with availability and pricing",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/HotelOffer")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="check_in_date", type="string", format="date", example="2024-12-25"),
     *                 @OA\Property(property="check_out_date", type="string", format="date", example="2024-12-27"),
     *                 @OA\Property(property="total_hotels", type="integer", example=5)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=503, ref="#/components/responses/ServiceUnavailable")
     * )
     */
    public function searchOffers(SearchHotelOffersRequest $request): JsonResponse
    {
        $options = array_filter([
            'adults' => $request->input('adults'),
            'roomQuantity' => $request->input('room_quantity'),
            'currency' => $request->input('currency'),
            'priceRange' => $request->input('price_range'),
            'paymentPolicy' => $request->input('payment_policy'),
            'boardType' => $request->input('board_type'),
            'includeClosed' => $request->input('include_closed'),
        ]);

        $results = $this->hotelService->getHotelOffers(
            $request->input('hotel_ids'),
            $request->input('check_in_date'),
            $request->input('check_out_date'),
            $options
        );

        if ($results === null) {
            return response()->json([
                'message' => 'Hotel offers service is currently unavailable',
                'data' => []
            ], 503);
        }

        return response()->json([
            'data' => $results,
            'meta' => [
                'check_in_date' => $request->input('check_in_date'),
                'check_out_date' => $request->input('check_out_date'),
                'total_hotels' => count($results)
            ]
        ]);
    }

    /**
     * Get hotel offer details by offer ID.
     *
     * @OA\Get(
     *     path="/api/hotels/offers/{offerId}",
     *     summary="Get hotel offer details by offer ID",
     *     tags={"Hotels"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="offerId",
     *         in="path",
     *         description="Hotel offer ID",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Hotel offer details",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/HotelOffer")
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *     @OA\Response(response=503, description="Amadeus API service unavailable")
     * )
     */
    public function getOffer(string $offerId): JsonResponse
    {
        $offer = $this->hotelService->getHotelOfferById($offerId);

        if ($offer === null) {
            return response()->json([
                'message' => 'Hotel offer not found or service unavailable'
            ], 404);
        }

        return response()->json([
            'data' => $offer
        ]);
    }

    /**
     * Get hotel ratings and sentiments.
     *
     * @OA\Post(
     *     path="/api/hotels/ratings",
     *     summary="Get hotel ratings and sentiments",
     *     tags={"Hotels"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"hotel_ids"},
     *             @OA\Property(property="hotel_ids", type="array", @OA\Items(type="string"), example={"RTPAR001", "RTPAR002"}, maxItems=3, description="Maximum 3 hotel IDs per request")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Hotel ratings and sentiments",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/HotelRating")),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total", type="integer", example=3)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=503, ref="#/components/responses/ServiceUnavailable")
     * )
     */
    public function getRatings(GetHotelRatingsRequest $request): JsonResponse
    {
        $results = $this->hotelService->getHotelRatings(
            $request->input('hotel_ids')
        );

        if ($results === null) {
            return response()->json([
                'message' => 'Hotel ratings service is currently unavailable',
                'data' => []
            ], 503);
        }

        return response()->json([
            'data' => $results,
            'meta' => [
                'total' => count($results)
            ]
        ]);
    }

    /**
     * Create a hotel booking (hotel order).
     *
     * Creates a hotel booking using the Amadeus Hotel Booking API. This endpoint requires
     * a valid hotel offer ID obtained from the hotel offers search endpoint.
     *
     * ⚠️ **Warning**: In test environment, this creates real bookings. Excessive fake/canceled
     * reservations may result in being blacklisted by hotel providers.
     *
     * @OA\Post(
     *     path="/api/hotels/bookings",
     *     summary="Create a hotel booking",
     *     description="Creates a hotel booking (hotel order) using a valid hotel offer ID from the availability search. The booking includes guest information, room associations, and payment details.",
     *     operationId="createHotelBooking",
     *     tags={"Hotels"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Hotel booking request data",
     *         @OA\JsonContent(
     *             required={"offer_id", "guests", "payment"},
     *             @OA\Property(
     *                 property="offer_id",
     *                 type="string",
     *                 example="4L8PRJPEN7",
     *                 description="Hotel offer ID from hotel offers search response. Must be a valid, non-expired offer ID.",
     *                 maxLength=100
     *             ),
     *             @OA\Property(
     *                 property="guests",
     *                 type="array",
     *                 description="Array of guest information. At least one guest is required.",
     *                 minItems=1,
     *                 @OA\Items(
     *                     type="object",
     *                     required={"first_name", "last_name", "phone", "email"},
     *                     @OA\Property(
     *                         property="tid",
     *                         type="integer",
     *                         example=1,
     *                         description="Temporary guest ID. Auto-generated if not provided (starts from 1)."
     *                     ),
     *                     @OA\Property(
     *                         property="title",
     *                         type="string",
     *                         example="MR",
     *                         description="Guest title/gender (MR, MRS, MS, etc.). Optional, max 54 characters.",
     *                         maxLength=54,
     *                         pattern="^[A-Za-z -]*$"
     *                     ),
     *                     @OA\Property(
     *                         property="first_name",
     *                         type="string",
     *                         example="BOB",
     *                         description="Guest first name (and middle name). Required, max 56 characters.",
     *                         maxLength=56,
     *                         minLength=1
     *                     ),
     *                     @OA\Property(
     *                         property="last_name",
     *                         type="string",
     *                         example="SMITH",
     *                         description="Guest last name. Required, max 57 characters.",
     *                         maxLength=57,
     *                         minLength=1
     *                     ),
     *                     @OA\Property(
     *                         property="phone",
     *                         type="string",
     *                         example="+33679278416",
     *                         description="Guest phone number. Required. E.123 format recommended (e.g., +33679278416).",
     *                         maxLength=199,
     *                         minLength=2
     *                     ),
     *                     @OA\Property(
     *                         property="email",
     *                         type="string",
     *                         format="email",
     *                         example="bob.smith@email.com",
     *                         description="Guest email address. Required, must be a valid email format.",
     *                         maxLength=90,
     *                         minLength=3
     *                     ),
     *                     @OA\Property(
     *                         property="child_age",
     *                         type="integer",
     *                         example=null,
     *                         description="Child age if the guest is a child. If provided, the guest will be treated as a child. If not provided and guest is not an adult, the system will consider them as an adult."
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="payment",
     *                 type="object",
     *                 required={"method", "payment_card"},
     *                 description="Payment information for the booking",
     *                 @OA\Property(
     *                     property="method",
     *                     type="string",
     *                     enum={"CREDIT_CARD"},
     *                     example="CREDIT_CARD",
     *                     description="Payment method. Currently only CREDIT_CARD is supported."
     *                 ),
     *                 @OA\Property(
     *                     property="payment_card",
     *                     type="object",
     *                     required={"vendor_code", "card_number", "expiry_date", "holder_name"},
     *                     description="Payment card information",
     *                     @OA\Property(
     *                         property="vendor_code",
     *                         type="string",
     *                         example="VI",
     *                         description="Card vendor code. Common values: VI (Visa), MC (MasterCard), AX (American Express), etc."
     *                     ),
     *                     @OA\Property(
     *                         property="card_number",
     *                         type="string",
     *                         example="4151289722471370",
     *                         description="Credit card number. Full card number without spaces or dashes."
     *                     ),
     *                     @OA\Property(
     *                         property="expiry_date",
     *                         type="string",
     *                         example="2026-08",
     *                         description="Card expiry date in YYYY-MM format (e.g., 2026-08 for August 2026).",
     *                         pattern="^\d{4}-\d{2}$"
     *                     ),
     *                     @OA\Property(
     *                         property="holder_name",
     *                         type="string",
     *                         example="BOB SMITH",
     *                         description="Card holder name as it appears on the card."
     *                     ),
     *                     @OA\Property(
     *                         property="security_code",
     *                         type="string",
     *                         example=null,
     *                         description="Card security code (CVV/CVC). Optional but may be required by some hotel providers."
     *                     )
     *                 )
     *             ),
     *             @OA\Property(
     *                 property="travel_agent",
     *                 type="object",
     *                 description="Travel agent contact information. Optional.",
     *                 @OA\Property(
     *                     property="contact",
     *                     type="object",
     *                     description="Travel agent contact details",
     *                     @OA\Property(
     *                         property="email",
     *                         type="string",
     *                         format="email",
     *                         example="agent@travelagency.com",
     *                         description="Travel agent contact email address. Optional.",
     *                         maxLength=90,
     *                         minLength=3
     *                     )
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Hotel booking created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Hotel booking created successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 description="Hotel order/booking confirmation data",
     *                 @OA\Property(property="type", type="string", example="hotel-order"),
     *                 @OA\Property(property="id", type="string", example="V0g2VFJaLzIwMjQtMDYtMDc=", description="Hotel order ID"),
     *                 @OA\Property(
     *                     property="hotelBookings",
     *                     type="array",
     *                     description="Array of hotel bookings in this order",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="type", type="string", example="hotel-booking"),
     *                         @OA\Property(property="id", type="string", example="MS84OTkyMjcxMC85MDIyNDU0OQ==", description="Hotel booking ID"),
     *                         @OA\Property(property="bookingStatus", type="string", example="CONFIRMED", description="Booking status (e.g., CONFIRMED, PENDING)"),
     *                         @OA\Property(
     *                             property="hotelProviderInformation",
     *                             type="array",
     *                             description="Hotel provider confirmation details",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="hotelProviderCode", type="string", example="AR"),
     *                                 @OA\Property(property="confirmationNumber", type="string", example="89922710")
     *                             )
     *                         ),
     *                         @OA\Property(
     *                             property="roomAssociations",
     *                             type="array",
     *                             description="Room associations with guest references",
     *                             @OA\Items(
     *                                 type="object",
     *                                 @OA\Property(property="hotelOfferId", type="string", example="4L8PRJPEN7"),
     *                                 @OA\Property(
     *                                     property="guestReferences",
     *                                     type="array",
     *                                     @OA\Items(
     *                                         type="object",
     *                                         @OA\Property(property="guestReference", type="string", example="1")
     *                                     )
     *                                 )
     *                             )
     *                         ),
     *                         @OA\Property(
     *                             property="hotel",
     *                             type="object",
     *                             description="Hotel information",
     *                             @OA\Property(property="hotelId", type="string", example="ARMADAIT"),
     *                             @OA\Property(property="chainCode", type="string", example="AR"),
     *                             @OA\Property(property="name", type="string", example="AC BY MARRIOTT HOTEL AITANA")
     *                         )
     *                     )
     *                 ),
     *                 @OA\Property(
     *                     property="guests",
     *                     type="array",
     *                     description="Guest information",
     *                     @OA\Items(
     *                         type="object",
     *                         @OA\Property(property="tid", type="integer", example=1),
     *                         @OA\Property(property="id", type="integer", example=1),
     *                         @OA\Property(property="title", type="string", example="MR"),
     *                         @OA\Property(property="firstName", type="string", example="BOB"),
     *                         @OA\Property(property="lastName", type="string", example="SMITH"),
     *                         @OA\Property(property="phone", type="string", example="+33679278416"),
     *                         @OA\Property(property="email", type="string", example="bob.smith@email.com")
     *                     )
     *                 )
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
     *         description="Service unavailable - Hotel booking service is currently unavailable or booking failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Hotel booking service is currently unavailable or booking failed")
     *         )
     *     )
     * )
     */
    public function createBooking(CreateHotelBookingRequest $request): JsonResponse
    {
        // Transform guests to match API reference schema
        $guests = [];
        foreach ($request->input('guests') as $index => $guest) {
            $guestData = [
                'tid' => $guest['tid'] ?? ($index + 1),
                'firstName' => $guest['first_name'],
                'lastName' => $guest['last_name'],
                'phone' => $guest['phone'],
                'email' => $guest['email'],
            ];

            if (isset($guest['title'])) {
                $guestData['title'] = $guest['title'];
            }

            if (isset($guest['child_age'])) {
                $guestData['childAge'] = $guest['child_age'];
            }

            $guests[] = $guestData;
        }

        // Build roomAssociations with guest references
        $guestReferences = [];
        foreach ($guests as $index => $guest) {
            $guestReferences[] = [
                'guestReference' => (string) $guest['tid']
            ];
        }

        $roomAssociations = [
            [
                'hotelOfferId' => $request->input('offer_id'),
                'guestReferences' => $guestReferences,
            ]
        ];

        // Transform payment to match API reference schema
        $paymentCard = $request->input('payment.payment_card');
        $payment = [
            'method' => $request->input('payment.method'),
            'paymentCard' => [
                'paymentCardInfo' => [
                    'vendorCode' => $paymentCard['vendor_code'],
                    'cardNumber' => $paymentCard['card_number'],
                    'expiryDate' => $paymentCard['expiry_date'],
                    'holderName' => $paymentCard['holder_name'],
                ]
            ]
        ];

        if (isset($paymentCard['security_code'])) {
            $payment['paymentCard']['paymentCardInfo']['securityCode'] = $paymentCard['security_code'];
        }

        // Build booking data matching API reference schema
        $bookingData = [
            'type' => 'hotel-order',
            'guests' => $guests,
            'roomAssociations' => $roomAssociations,
            'payment' => $payment,
        ];

        // Add travel agent if provided
        if ($request->has('travel_agent')) {
            $travelAgent = $request->input('travel_agent');
            if (isset($travelAgent['contact']['email'])) {
                $bookingData['travelAgent'] = [
                    'contact' => [
                        'email' => $travelAgent['contact']['email']
                    ]
                ];
            }
        }

        $booking = $this->hotelService->createHotelBooking($bookingData);

        if ($booking === null) {
            return response()->json([
                'message' => 'Hotel booking service is currently unavailable or booking failed'
            ], 503);
        }

        return response()->json([
            'message' => 'Hotel booking created successfully',
            'data' => $booking
        ], 201);
    }

    /**
     * Search hotels with offers in one call (combined search).
     *
     * @OA\Post(
     *     path="/api/hotels/search-with-offers",
     *     summary="Search hotels with offers in one call",
     *     tags={"Hotels"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"latitude", "longitude", "check_in_date", "check_out_date"},
     *             @OA\Property(property="latitude", type="number", format="double", example=40.7128),
     *             @OA\Property(property="longitude", type="number", format="double", example=-74.0060),
     *             @OA\Property(property="check_in_date", type="string", format="date", example="2024-12-25"),
     *             @OA\Property(property="check_out_date", type="string", format="date", example="2024-12-27"),
     *             @OA\Property(property="radius", type="integer", example=5),
     *             @OA\Property(property="radius_unit", type="string", enum={"KM", "MILE"}, example="KM"),
     *             @OA\Property(property="adults", type="integer", example=2),
     *             @OA\Property(property="room_quantity", type="integer", example=1),
     *             @OA\Property(property="currency", type="string", example="USD")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Hotels with offers",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="hotels", type="array", @OA\Items(ref="#/components/schemas/Hotel")),
     *                 @OA\Property(property="offers", type="array", @OA\Items(ref="#/components/schemas/HotelOffer"))
     *             ),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="total_hotels", type="integer", example=10),
     *                 @OA\Property(property="total_offers", type="integer", example=5)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError"),
     *     @OA\Response(response=503, ref="#/components/responses/ServiceUnavailable")
     * )
     */
    public function searchWithOffers(SearchHotelsWithOffersRequest $request): JsonResponse
    {

        $options = array_filter([
            'radius' => $request->input('radius'),
            'radiusUnit' => $request->input('radius_unit'),
            'adults' => $request->input('adults'),
            'roomQuantity' => $request->input('room_quantity'),
            'currency' => $request->input('currency'),
        ]);

        $results = $this->hotelService->searchHotelsWithOffers(
            $request->input('latitude'),
            $request->input('longitude'),
            $request->input('check_in_date'),
            $request->input('check_out_date'),
            $options
        );

        if ($results === null) {
            return response()->json([
                'message' => 'Hotel search service is currently unavailable',
                'data' => [
                    'hotels' => [],
                    'offers' => []
                ]
            ], 503);
        }

        return response()->json([
            'data' => $results,
            'meta' => [
                'total_hotels' => count($results['hotels'] ?? []),
                'total_offers' => count($results['offers'] ?? [])
            ]
        ]);
    }
}
