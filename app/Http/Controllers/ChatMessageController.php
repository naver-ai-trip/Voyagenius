<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreChatMessageRequest;
use App\Http\Resources\ChatMessageResource;
use App\Models\ChatMessage;
use App\Models\ChatSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ChatMessageController extends Controller
{
    /**
     * Display a listing of messages in a chat session.
     * 
     * @OA\Get(
     *     path="/api/chat-sessions/{sessionId}/messages",
     *     summary="List messages in chat session",
     *     tags={"AI Agent - Chat Messages"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="sessionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="filter[from_role]",
     *         in="query",
     *         description="Filter by role (user, ai)",
     *         required=false,
     *         @OA\Schema(type="string", enum={"user", "ai"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Results per page",
     *         required=false,
     *         @OA\Schema(type="integer", default=15)
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function index(Request $request, ChatSession $chatSession): AnonymousResourceCollection
    {
        $this->authorize('view', $chatSession);

        $query = ChatMessage::where('chat_session_id', $chatSession->id);

        // Filter by role
        if ($request->has('filter.from_role')) {
            $query->where('from_role', $request->input('filter.from_role'));
        }

        $perPage = $request->input('per_page', 15);
        $messages = $query->oldest()->paginate($perPage);

        return ChatMessageResource::collection($messages);
    }

    /**
     * Store a newly created message.
     *
     * @OA\Post(
     *     path="/api/chat-sessions/{sessionId}/messages",
     *     summary="Send a message in chat session",
     *     tags={"AI Agent - Chat Messages"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="sessionId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=201, description="Message created"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreChatMessageRequest $request, ChatSession $chatSession): JsonResponse
    {
        $this->authorize('view', $chatSession);

        $message = ChatMessage::create([
            'chat_session_id' => $chatSession->id,
            'from_role' => $request->input('from_role', 'user'),
            'message' => $request->input('message'),
            'metadata' => $request->input('metadata', []),
            'entity_type' => $request->input('entity_type'),
            'entity_id' => $request->input('entity_id'),
        ]);

        return (new ChatMessageResource($message))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified message.
     *
     * @OA\Get(
     *     path="/api/chat-sessions/{sessionId}/messages/{id}",
     *     summary="Get message details",
     *     tags={"AI Agent - Chat Messages"},
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
    public function show(ChatSession $chatSession, ChatMessage $message): ChatMessageResource
    {
        $this->authorize('view', $chatSession);

        // Ensure message belongs to this session
        abort_if($message->chat_session_id !== $chatSession->id, 404);

        return new ChatMessageResource($message);
    }
}
