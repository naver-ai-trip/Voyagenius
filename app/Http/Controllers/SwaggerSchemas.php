<?php

namespace App\Http\Controllers;

/**
 * @OA\Schema(
 *     schema="Trip",
 *     type="object",
 *     title="Trip",
 *     description="Trip model",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Tokyo Adventure"),
 *     @OA\Property(property="destination_country", type="string", example="Japan"),
 *     @OA\Property(property="destination_city", type="string", example="Tokyo"),
 *     @OA\Property(property="start_date", type="string", format="date", example="2025-12-01"),
 *     @OA\Property(property="end_date", type="string", format="date", example="2025-12-07"),
 *     @OA\Property(property="status", type="string", enum={"planning", "ongoing", "completed"}, example="planning"),
 *     @OA\Property(property="is_group", type="boolean", example=false),
 *     @OA\Property(property="progress", type="string", nullable=true, example="Planning itinerary"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Place",
 *     type="object",
 *     title="Place",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="naver_place_id", type="string", example="1234567890"),
 *     @OA\Property(property="name", type="string", example="Tokyo Tower"),
 *     @OA\Property(property="address", type="string", example="4 Chome-2-8 Shibakoen, Minato City, Tokyo"),
 *     @OA\Property(property="latitude", type="number", format="double", example=35.6586),
 *     @OA\Property(property="longitude", type="number", format="double", example=139.7454),
 *     @OA\Property(property="category", type="string", example="Landmark"),
 *     @OA\Property(property="phone_number", type="string", nullable=true, example="+81-3-3433-5111"),
 *     @OA\Property(property="rating", type="number", format="double", nullable=true, example=4.5),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="MapCheckpoint",
 *     type="object",
 *     title="Map Checkpoint",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="trip_id", type="integer", example=1),
 *     @OA\Property(property="place_id", type="integer", nullable=true, example=1),
 *     @OA\Property(property="name", type="string", example="Tokyo Tower Visit"),
 *     @OA\Property(property="latitude", type="number", format="double", example=35.6586),
 *     @OA\Property(property="longitude", type="number", format="double", example=139.7454),
 *     @OA\Property(property="notes", type="string", nullable=true, example="Best view at sunset"),
 *     @OA\Property(property="checked_in", type="boolean", example=false),
 *     @OA\Property(property="checked_in_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="order", type="integer", example=1),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Review",
 *     type="object",
 *     title="Review",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="reviewable_type", type="string", example="place"),
 *     @OA\Property(property="reviewable_id", type="integer", example=1),
 *     @OA\Property(property="rating", type="integer", example=5),
 *     @OA\Property(property="comment", type="string", nullable=true, example="Amazing view!"),
 *     @OA\Property(
 *         property="images",
 *         type="array",
 *         description="Array of public image URLs",
 *         @OA\Items(type="string", format="url", example="http://tripplanner.test/storage/reviews/1/uuid.jpg")
 *     ),
 *     @OA\Property(property="is_flagged", type="boolean", example=false, description="Whether content is flagged by moderation"),
 *     @OA\Property(
 *         property="moderation_results",
 *         type="object",
 *         nullable=true,
 *         description="NAVER Green-Eye moderation results (only shown if flagged)",
 *         @OA\Property(property="safe", type="boolean", example=true),
 *         @OA\Property(property="reason", type="string", example="Content passed safety checks")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Translation",
 *     type="object",
 *     title="Translation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="source_type", type="string", enum={"text", "image", "speech"}, example="text"),
 *     @OA\Property(property="source_text", type="string", example="안녕하세요"),
 *     @OA\Property(property="translated_text", type="string", example="Hello"),
 *     @OA\Property(property="source_lang", type="string", example="ko"),
 *     @OA\Property(property="target_lang", type="string", example="en"),
 *     @OA\Property(property="file_path", type="string", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Tag",
 *     type="object",
 *     title="Tag",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Adventure"),
 *     @OA\Property(property="slug", type="string", example="adventure"),
 *     @OA\Property(property="usage_count", type="integer", example=15),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Favorite",
 *     type="object",
 *     title="Favorite",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="favoritable_type", type="string", example="App\\Models\\Place"),
 *     @OA\Property(property="favoritable_id", type="integer", example=5),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Comment",
 *     type="object",
 *     title="Comment",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="entity_type", type="string", example="trip"),
 *     @OA\Property(property="entity_id", type="integer", example=5),
 *     @OA\Property(property="content", type="string", example="Great trip! Loved it."),
 *     @OA\Property(
 *         property="images",
 *         type="array",
 *         description="Array of public image URLs",
 *         @OA\Items(type="string", format="url", example="http://tripplanner.test/storage/comments/1/uuid.jpg")
 *     ),
 *     @OA\Property(property="is_flagged", type="boolean", example=false, description="Whether content is flagged by moderation"),
 *     @OA\Property(
 *         property="moderation_results",
 *         type="object",
 *         nullable=true,
 *         description="NAVER Green-Eye moderation results (only shown if flagged)",
 *         @OA\Property(property="safe", type="boolean", example=true),
 *         @OA\Property(property="reason", type="string", example="Content passed safety checks")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="TripParticipant",
 *     type="object",
 *     title="Trip Participant",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="trip_id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=2),
 *     @OA\Property(property="role", type="string", enum={"owner", "editor", "viewer"}, example="editor"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="TripDiary",
 *     type="object",
 *     title="Trip Diary",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="trip_id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="entry_date", type="string", format="date", example="2024-01-15"),
 *     @OA\Property(property="text", type="string", example="Today was amazing!"),
 *     @OA\Property(property="mood", type="string", enum={"happy", "excited", "tired", "sad", "neutral"}, example="happy"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="ChecklistItem",
 *     type="object",
 *     title="Checklist Item",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="trip_id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="content", type="string", example="Pack passport"),
 *     @OA\Property(property="is_checked", type="boolean", example=false),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="ItineraryItem",
 *     type="object",
 *     title="Itinerary Item",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="trip_id", type="integer", example=1),
 *     @OA\Property(property="day_number", type="integer", example=2),
 *     @OA\Property(property="title", type="string", example="Visit Tokyo Tower"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Enjoy panoramic views"),
 *     @OA\Property(property="start_time", type="string", format="time", nullable=true, example="09:00:00"),
 *     @OA\Property(property="end_time", type="string", format="time", nullable=true, example="11:00:00"),
 *     @OA\Property(property="place_id", type="integer", nullable=true, example=5),
 *     @OA\Property(property="duration_minutes", type="integer", nullable=true, example=120),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="CheckpointImage",
 *     type="object",
 *     title="Checkpoint Image",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="map_checkpoint_id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="file_path", type="string", example="checkpoints/1/1/uuid.jpg"),
 *     @OA\Property(property="url", type="string", example="http://localhost/storage/checkpoints/1/1/uuid.jpg"),
 *     @OA\Property(property="caption", type="string", nullable=true, example="Beautiful view"),
 *     @OA\Property(property="is_flagged", type="boolean", example=false, description="Whether content is flagged by moderation"),
 *     @OA\Property(
 *         property="moderation_results",
 *         type="object",
 *         nullable=true,
 *         description="NAVER Green-Eye moderation results (only shown if flagged)",
 *         @OA\Property(property="safe", type="boolean", example=true),
 *         @OA\Property(property="reason", type="string", example="Content passed safety checks")
 *     ),
 *     @OA\Property(property="uploaded_at", type="string", format="date-time"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Share",
 *     type="object",
 *     title="Share",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="trip_id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="token", type="string", example="abc123xyz456..."),
 *     @OA\Property(property="permission", type="string", enum={"viewer", "editor"}, example="viewer"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="Notification",
 *     type="object",
 *     title="Notification",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="type", type="string", enum={"trip_invite", "comment_added", "check_in", "trip_shared", "participant_added", "review_added"}, example="trip_invite"),
 *     @OA\Property(property="data", type="object", example={"trip_id": 5, "inviter_name": "John"}),
 *     @OA\Property(property="read_at", type="string", format="date-time", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Response(
 *     response="Unauthorized",
 *     description="Unauthenticated",
 *     @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
 * )
 *
 * @OA\Response(
 *     response="ValidationError",
 *     description="Validation error",
 *     @OA\JsonContent(ref="#/components/schemas/ValidationError")
 * )
 *
 * @OA\Response(
 *     response="Forbidden",
 *     description="Forbidden",
 *     @OA\JsonContent(ref="#/components/schemas/ForbiddenError")
 * )
 *
 * @OA\Response(
 *     response="NotFound",
 *     description="Resource not found",
 *     @OA\JsonContent(ref="#/components/schemas/NotFoundError")
 * )
 *
 * @OA\Response(
 *     response="ServiceUnavailable",
 *     description="Service unavailable",
 *     @OA\JsonContent(
 *         @OA\Property(property="message", type="string", example="Hotel search service is currently unavailable"),
 *         @OA\Property(property="data", type="array", @OA\Items())
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="Hotel",
 *     type="object",
 *     title="Hotel",
 *     description="Hotel information from Amadeus API",
 *     @OA\Property(property="type", type="string", example="hotel"),
 *     @OA\Property(property="hotelId", type="string", example="RTPAR001"),
 *     @OA\Property(property="chainCode", type="string", nullable=true, example="RT"),
 *     @OA\Property(property="dupeId", type="string", nullable=true, example="700012345"),
 *     @OA\Property(property="name", type="string", example="Hotel Example Paris"),
 *     @OA\Property(property="rating", type="string", nullable=true, example="4"),
 *     @OA\Property(property="cityCode", type="string", example="PAR"),
 *     @OA\Property(property="latitude", type="number", format="double", nullable=true, example=48.8566),
 *     @OA\Property(property="longitude", type="number", format="double", nullable=true, example=2.3522),
 *     @OA\Property(property="hotelDistance", type="object", nullable=true,
 *         @OA\Property(property="distance", type="number", example=0.5),
 *         @OA\Property(property="distanceUnit", type="string", example="KM")
 *     ),
 *     @OA\Property(property="address", type="object", nullable=true,
 *         @OA\Property(property="lines", type="array", @OA\Items(type="string")),
 *         @OA\Property(property="postalCode", type="string"),
 *         @OA\Property(property="cityName", type="string"),
 *         @OA\Property(property="countryCode", type="string")
 *     ),
 *     @OA\Property(property="contact", type="object", nullable=true,
 *         @OA\Property(property="phone", type="string"),
 *         @OA\Property(property="fax", type="string")
 *     ),
 *     @OA\Property(property="description", type="object", nullable=true,
 *         @OA\Property(property="lang", type="string"),
 *         @OA\Property(property="text", type="string")
 *     ),
 *     @OA\Property(property="amenities", type="array", nullable=true, @OA\Items(type="string")),
 *     @OA\Property(property="media", type="array", nullable=true, @OA\Items(
 *         @OA\Property(property="uri", type="string"),
 *         @OA\Property(property="category", type="string")
 *     ))
 * )
 *
 * @OA\Schema(
 *     schema="HotelOffer",
 *     type="object",
 *     title="Hotel Offer",
 *     description="Hotel offer with availability and pricing from Amadeus API",
 *     @OA\Property(property="type", type="string", example="hotel-offers"),
 *     @OA\Property(property="hotel", type="object", ref="#/components/schemas/Hotel"),
 *     @OA\Property(property="available", type="boolean", example=true),
 *     @OA\Property(property="offers", type="array", @OA\Items(
 *         @OA\Property(property="id", type="string", example="ABC123XYZ"),
 *         @OA\Property(property="checkInDate", type="string", format="date", example="2024-12-25"),
 *         @OA\Property(property="checkOutDate", type="string", format="date", example="2024-12-27"),
 *         @OA\Property(property="room", type="object",
 *             @OA\Property(property="type", type="string", example="STANDARD_ROOM"),
 *             @OA\Property(property="typeEstimated", type="object", nullable=true,
 *                 @OA\Property(property="category", type="string"),
 *                 @OA\Property(property="beds", type="integer"),
 *                 @OA\Property(property="bedType", type="string")
 *             ),
 *             @OA\Property(property="description", type="object", nullable=true,
 *                 @OA\Property(property="text", type="string"),
 *                 @OA\Property(property="lang", type="string")
 *             )
 *         ),
 *         @OA\Property(property="guests", type="object",
 *             @OA\Property(property="adults", type="integer", example=2)
 *         ),
 *         @OA\Property(property="price", type="object",
 *             @OA\Property(property="currency", type="string", example="USD"),
 *             @OA\Property(property="base", type="string", example="200.00"),
 *             @OA\Property(property="total", type="string", example="400.00"),
 *             @OA\Property(property="variations", type="object", nullable=true,
 *                 @OA\Property(property="average", type="object", nullable=true),
 *                 @OA\Property(property="changes", type="array", nullable=true, @OA\Items(type="object"))
 *             )
 *         ),
 *         @OA\Property(property="policies", type="object", nullable=true,
 *             @OA\Property(property="paymentType", type="string", example="GUARANTEE"),
 *             @OA\Property(property="cancellation", type="object", nullable=true,
 *                 @OA\Property(property="type", type="string"),
 *                 @OA\Property(property="amount", type="string"),
 *                 @OA\Property(property="numberOfNights", type="integer")
 *             )
 *         ),
 *         @OA\Property(property="self", type="string", format="uri", example="https://api.amadeus.com/v3/shopping/hotel-offers/ABC123XYZ")
 *     )),
 *     @OA\Property(property="self", type="string", format="uri", nullable=true)
 * )
 *
 * @OA\Schema(
 *     schema="HotelRating",
 *     type="object",
 *     title="Hotel Rating",
 *     description="Hotel ratings and sentiments from Amadeus e-Reputation API",
 *     @OA\Property(property="hotelId", type="string", example="RTPAR001"),
 *     @OA\Property(property="rating", type="number", format="double", nullable=true, example=4.5),
 *     @OA\Property(property="numberOfRatings", type="integer", nullable=true, example=1250),
 *     @OA\Property(property="sentiments", type="object", nullable=true,
 *         @OA\Property(property="overall", type="object", nullable=true,
 *             @OA\Property(property="score", type="number", format="double", example=0.85),
 *             @OA\Property(property="distribution", type="object", nullable=true,
 *                 @OA\Property(property="positive", type="number", format="double", example=0.75),
 *                 @OA\Property(property="neutral", type="number", format="double", example=0.15),
 *                 @OA\Property(property="negative", type="number", format="double", example=0.10)
 *             )
 *         ),
 *         @OA\Property(property="aspects", type="object", nullable=true,
 *             @OA\Property(property="service", type="object", nullable=true,
 *                 @OA\Property(property="score", type="number", format="double"),
 *                 @OA\Property(property="distribution", type="object", nullable=true)
 *             ),
 *             @OA\Property(property="facilities", type="object", nullable=true,
 *                 @OA\Property(property="score", type="number", format="double"),
 *                 @OA\Property(property="distribution", type="object", nullable=true)
 *             ),
 *             @OA\Property(property="location", type="object", nullable=true,
 *                 @OA\Property(property="score", type="number", format="double"),
 *                 @OA\Property(property="distribution", type="object", nullable=true)
 *             ),
 *             @OA\Property(property="food", type="object", nullable=true,
 *                 @OA\Property(property="score", type="number", format="double"),
 *                 @OA\Property(property="distribution", type="object", nullable=true)
 *             ),
 *             @OA\Property(property="room", type="object", nullable=true,
 *                 @OA\Property(property="score", type="number", format="double"),
 *                 @OA\Property(property="distribution", type="object", nullable=true)
 *             )
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="HotelBooking",
 *     type="object",
 *     title="Hotel Booking",
 *     description="Hotel booking confirmation from Amadeus API",
 *     @OA\Property(property="type", type="string", example="hotel-booking"),
 *     @OA\Property(property="id", type="string", example="ABC123XYZ"),
 *     @OA\Property(property="providerConfirmationId", type="string", example="CONF123456"),
 *     @OA\Property(property="associatedRecords", type="array", nullable=true, @OA\Items(
 *         @OA\Property(property="reference", type="string"),
 *         @OA\Property(property="originSystemCode", type="string")
 *     )),
 *     @OA\Property(property="hotel", type="object", ref="#/components/schemas/Hotel"),
 *     @OA\Property(property="guests", type="array", @OA\Items(
 *         @OA\Property(property="name", type="object",
 *             @OA\Property(property="title", type="string", example="MR"),
 *             @OA\Property(property="firstName", type="string", example="John"),
 *             @OA\Property(property="lastName", type="string", example="Doe")
 *         ),
 *         @OA\Property(property="contact", type="object",
 *             @OA\Property(property="phone", type="string", example="+1234567890"),
 *             @OA\Property(property="email", type="string", format="email", example="john@example.com")
 *         )
 *     )),
 *     @OA\Property(property="payments", type="array", @OA\Items(
 *         @OA\Property(property="method", type="string", example="CREDIT_CARD"),
 *         @OA\Property(property="card", type="object", nullable=true,
 *             @OA\Property(property="vendorCode", type="string", example="VI"),
 *             @OA\Property(property="cardNumber", type="string", example="411111******1111"),
 *             @OA\Property(property="expiryDate", type="string", example="12/25")
 *         )
 *     )),
 *     @OA\Property(property="rooms", type="array", @OA\Items(
 *         @OA\Property(property="type", type="string", example="STANDARD_ROOM"),
 *         @OA\Property(property="typeEstimated", type="object", nullable=true),
 *         @OA\Property(property="description", type="object", nullable=true)
 *     )),
 *     @OA\Property(property="checkInDate", type="string", format="date", example="2024-12-25"),
 *     @OA\Property(property="checkOutDate", type="string", format="date", example="2024-12-27"),
 *     @OA\Property(property="createdAt", type="string", format="date-time", example="2024-01-15T10:30:00Z")
 * )
 */
class SwaggerSchemas
{
    // This class exists only to hold Swagger/OpenAPI schema definitions
    // It is never instantiated
}
