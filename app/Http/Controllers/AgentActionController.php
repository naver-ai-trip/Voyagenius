<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAgentActionRequest;
use App\Http\Resources\AgentActionResource;
use App\Models\AgentAction;
use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AgentActionController extends Controller
{
    /**
     * Display a listing of actions in a chat session.
     * 
     * @OA\Get(
     *     path="/api/chat-sessions/{sessionId}/actions",
     *     summary="List actions in chat session",
     *     tags={"AI Agent - Actions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="sessionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="filter[status]",
     *         in="query",
     *         description="Filter by status (pending, completed, failed)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="filter[action_type]",
     *         in="query",
     *         description="Filter by action type",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function index(Request $request, ChatSession $chatSession): AnonymousResourceCollection
    {
        $this->authorize('view', $chatSession);

        $query = AgentAction::where('chat_session_id', $chatSession->id);

        // Filter by status
        if ($request->has('filter.status')) {
            $query->where('status', $request->input('filter.status'));
        }

        // Filter by action type
        if ($request->has('filter.action_type')) {
            $query->where('action_type', $request->input('filter.action_type'));
        }

        $actions = $query->latest()->get();

        return AgentActionResource::collection($actions);
    }

    /**
     * Store a newly created action.
     *
     * @OA\Post(
     *     path="/api/chat-sessions/{sessionId}/actions",
     *     summary="Create a new agent action",
     *     description="Track AI agent operations like searches, translations, API calls",
     *     tags={"AI Agent - Actions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="sessionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="Chat session ID"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"action_type", "input_data"},
     *             @OA\Property(
     *                 property="action_type",
     *                 type="string",
     *                 enum={"search_places", "translate_content", "add_to_itinerary", "get_recommendations", "create_trip"},
     *                 description="Type of action being performed",
     *                 example="search_places"
     *             ),
     *             @OA\Property(
     *                 property="input_data",
     *                 type="object",
     *                 description="Input parameters for the action",
     *                 example={"query": "Korean restaurant Seoul", "category": "food"}
     *             ),
     *             @OA\Property(
     *                 property="entity_type",
     *                 type="string",
     *                 nullable=true,
     *                 description="Related entity type",
     *                 example="trip"
     *             ),
     *             @OA\Property(
     *                 property="entity_id",
     *                 type="integer",
     *                 nullable=true,
     *                 description="Related entity ID",
     *                 example=123
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Action created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=999),
     *                 @OA\Property(property="action_type", type="string", example="search_places"),
     *                 @OA\Property(property="status", type="string", example="pending"),
     *                 @OA\Property(property="input_data", type="object"),
     *                 @OA\Property(property="started_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreAgentActionRequest $request, ChatSession $chatSession): JsonResponse
    {
        $this->authorize('view', $chatSession);

        $action = AgentAction::create([
            'chat_session_id' => $chatSession->id,
            'action_type' => $request->input('action_type'),
            'status' => 'pending',
            'input_data' => $request->input('input_data', []),
            'entity_type' => $request->input('entity_type'),
            'entity_id' => $request->input('entity_id'),
            'started_at' => now(),
        ]);

        return (new AgentActionResource($action))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified action.
     *
     * @OA\Get(
     *     path="/api/chat-sessions/{sessionId}/actions/{id}",
     *     summary="Get action details",
     *     tags={"AI Agent - Actions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="sessionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=404, ref="#/components/responses/NotFound")
     * )
     */
    public function show(ChatSession $chatSession, AgentAction $action): AgentActionResource
    {
        $this->authorize('view', $chatSession);

        // Ensure action belongs to this session
        abort_if($action->chat_session_id !== $chatSession->id, 404);

        return new AgentActionResource($action);
    }

    /**
     * Mark an action as completed.
     *
     * @OA\Post(
     *     path="/api/actions/{id}/complete",
     *     summary="Mark action as completed",
     *     description="Update action status to completed with output data",
     *     tags={"AI Agent - Actions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="Action ID"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"output_data"},
     *             @OA\Property(
     *                 property="output_data",
     *                 type="object",
     *                 description="Results from the completed action",
     *                 example={"results_found": 10, "processing_time_ms": 234}
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Action marked as completed",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="status", type="string", example="completed"),
     *                 @OA\Property(property="output_data", type="object"),
     *                 @OA\Property(property="completed_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function complete(Request $request, AgentAction $action): JsonResponse
    {
        $this->authorize('view', $action->chatSession);

        $action->markCompleted($request->input('output_data', []));

        return response()->json([
            'data' => new AgentActionResource($action),
        ]);
    }

    /**
     * Mark an action as failed.
     *
     * @OA\Post(
     *     path="/api/actions/{id}/fail",
     *     summary="Mark action as failed",
     *     description="Update action status to failed with error message",
     *     tags={"AI Agent - Actions"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="Action ID"
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"error_message"},
     *             @OA\Property(
     *                 property="error_message",
     *                 type="string",
     *                 description="Reason for failure",
     *                 example="NAVER Maps API rate limit exceeded"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Action marked as failed",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="status", type="string", example="failed"),
     *                 @OA\Property(property="error_message", type="string"),
     *                 @OA\Property(property="completed_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function fail(Request $request, AgentAction $action): JsonResponse
    {
        $this->authorize('view', $action->chatSession);

        $action->markFailed($request->input('error_message', 'Action failed'));

        return response()->json([
            'data' => new AgentActionResource($action),
        ]);
    }
}
