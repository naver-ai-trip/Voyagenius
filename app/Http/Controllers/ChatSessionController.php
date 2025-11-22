<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChatSessionRequest;
use App\Http\Requests\UpdateChatSessionRequest;
use App\Http\Resources\ChatSessionResource;
use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ChatSessionController extends Controller
{
    /**
     * Display a listing of the user's chat sessions.
     * 
     * @OA\Get(
     *     path="/api/chat-sessions",
     *     summary="List user's chat sessions",
     *     tags={"AI Agent - Chat Sessions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="filter[is_active]",
     *         in="query",
     *         description="Filter by active status",
     *         required=false,
     *         @OA\Schema(type="boolean")
     *     ),
     *     @OA\Parameter(
     *         name="filter[trip_id]",
     *         in="query",
     *         description="Filter by trip ID",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Include relationships (messages, actions)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation"
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = ChatSession::where('user_id', $request->user()->id);

        // Filter by active status
        if ($request->has('filter.is_active')) {
            $query->where('is_active', $request->input('filter.is_active'));
        }

        // Filter by trip
        if ($request->has('filter.trip_id')) {
            $query->where('trip_id', $request->input('filter.trip_id'));
        }

        // Include relationships
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $query->with($includes);
        }

        $sessions = $query->latest()->get();

        return ChatSessionResource::collection($sessions);
    }

    /**
     * Store a newly created chat session.
     *
     * @OA\Post(
     *     path="/api/chat-sessions",
     *     summary="Create a new chat session",
     *     description="Start a new conversation session with the AI agent for trip planning, itinerary building, or place search",
     *     tags={"AI Agent - Chat Sessions"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"session_type"},
     *             @OA\Property(
     *                 property="session_type",
     *                 type="string",
     *                 enum={"trip_planning", "itinerary_building", "place_search", "recommendation"},
     *                 description="Type of conversation session",
     *                 example="trip_planning"
     *             ),
     *             @OA\Property(
     *                 property="trip_id",
     *                 type="integer",
     *                 nullable=true,
     *                 description="Optional trip ID to associate with this session",
     *                 example=123
     *             ),
     *             @OA\Property(
     *                 property="context",
     *                 type="object",
     *                 nullable=true,
     *                 description="Additional context for the conversation",
     *                 example={
     *                     "destination": "Seoul, South Korea",
     *                     "budget": "moderate",
     *                     "interests": {"food", "culture", "history"},
     *                     "travel_dates": {
     *                         "start": "2025-12-01",
     *                         "end": "2025-12-07"
     *                     }
     *                 }
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Chat session created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=123),
     *                 @OA\Property(property="user_id", type="integer", example=1),
     *                 @OA\Property(property="trip_id", type="integer", nullable=true, example=null),
     *                 @OA\Property(property="session_type", type="string", example="trip_planning"),
     *                 @OA\Property(property="context", type="object", nullable=true),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="started_at", type="string", format="date-time"),
     *                 @OA\Property(property="ended_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The given data was invalid."),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object",
     *                 @OA\Property(
     *                     property="session_type",
     *                     type="array",
     *                     @OA\Items(type="string", example="The session type field is required.")
     *                 )
     *             )
     *         )
     *     )
     * )
     */
    public function store(StoreChatSessionRequest $request): JsonResponse
    {
        $session = ChatSession::create([
            'user_id' => $request->user()->id,
            'trip_id' => $request->input('trip_id'),
            'session_type' => $request->input('session_type'),
            'context' => $request->input('context', []),
            'is_active' => true,
        ]);

        return (new ChatSessionResource($session))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified chat session.
     *
     * @OA\Get(
     *     path="/api/chat-sessions/{id}",
     *     summary="Get chat session details",
     *     tags={"AI Agent - Chat Sessions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="include",
     *         in="query",
     *         description="Include relationships (messages, actions)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(Request $request, ChatSession $chatSession): ChatSessionResource
    {
        $this->authorize('view', $chatSession);

        // Include relationships if requested
        if ($request->has('include')) {
            $includes = explode(',', $request->input('include'));
            $chatSession->load($includes);
        }

        return new ChatSessionResource($chatSession);
    }

    /**
     * Update the specified chat session.
     *
     * @OA\Patch(
     *     path="/api/chat-sessions/{id}",
     *     summary="Update chat session",
     *     tags={"AI Agent - Chat Sessions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Session updated"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function update(UpdateChatSessionRequest $request, ChatSession $chatSession): ChatSessionResource
    {
        $this->authorize('update', $chatSession);

        $chatSession->update($request->only(['context']));

        return new ChatSessionResource($chatSession);
    }

    /**
     * Remove the specified chat session.
     *
     * @OA\Delete(
     *     path="/api/chat-sessions/{id}",
     *     summary="Delete chat session",
     *     tags={"AI Agent - Chat Sessions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Session deleted"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function destroy(ChatSession $chatSession): JsonResponse
    {
        $this->authorize('delete', $chatSession);

        $chatSession->delete();

        return response()->json(null, 204);
    }

    /**
     * Deactivate the specified chat session.
     *
     * @OA\Post(
     *     path="/api/chat-sessions/{id}/deactivate",
     *     summary="Deactivate chat session",
     *     tags={"AI Agent - Chat Sessions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Session deactivated"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function deactivate(ChatSession $chatSession): ChatSessionResource
    {
        $this->authorize('update', $chatSession);

        $chatSession->deactivate();

        return new ChatSessionResource($chatSession);
    }

    /**
     * Activate the specified chat session.
     *
     * @OA\Post(
     *     path="/api/chat-sessions/{id}/activate",
     *     summary="Activate chat session",
     *     tags={"AI Agent - Chat Sessions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Session activated"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function activate(ChatSession $chatSession): ChatSessionResource
    {
        $this->authorize('update', $chatSession);

        $chatSession->activate();

        return new ChatSessionResource($chatSession);
    }
}
