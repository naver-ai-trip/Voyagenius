<?php

namespace App\Http\Controllers;

use App\Models\AgentWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Tag(
 *     name="AI Agent - Webhooks",
 *     description="Manage webhook endpoints for real-time event notifications"
 * )
 */
class AgentWebhookController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/agent-webhooks",
     *     summary="List user's registered webhooks",
     *     tags={"AI Agent - Webhooks"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="List of webhooks"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $webhooks = AgentWebhook::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $webhooks]);
    }

    /**
     * @OA\Post(
     *     path="/api/agent-webhooks",
     *     summary="Register a new webhook",
     *     tags={"AI Agent - Webhooks"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"url", "events"},
     *             @OA\Property(property="url", type="string", example="https://your-agent.com/webhook"),
     *             @OA\Property(property="events", type="array", @OA\Items(type="string"), example={"message.sent", "recommendation.created"}),
     *             @OA\Property(property="retry_count", type="integer", example=3),
     *             @OA\Property(property="timeout_seconds", type="integer", example=30)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Webhook created"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => ['required', 'url', 'max:500'],
            'events' => ['required', 'array', 'min:1'],
            'events.*' => ['required', 'string', 'in:message.sent,recommendation.created,action.completed,session.started,session.ended'],
            'retry_count' => ['nullable', 'integer', 'min:0', 'max:5'],
            'timeout_seconds' => ['nullable', 'integer', 'min:5', 'max:60'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $webhook = AgentWebhook::create([
            'user_id' => $request->user()->id,
            'url' => $request->url,
            'events' => $request->events,
            'retry_count' => $request->retry_count ?? 3,
            'timeout_seconds' => $request->timeout_seconds ?? 30,
        ]);

        return response()->json([
            'data' => $webhook,
            'message' => 'Webhook registered successfully',
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/agent-webhooks/{id}",
     *     summary="Get webhook details",
     *     tags={"AI Agent - Webhooks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Webhook details"),
     *     @OA\Response(response=404, description="Not found")
     * )
     */
    public function show(AgentWebhook $agentWebhook): JsonResponse
    {
        $this->authorize('view', $agentWebhook);

        return response()->json(['data' => $agentWebhook]);
    }

    /**
     * @OA\Patch(
     *     path="/api/agent-webhooks/{id}",
     *     summary="Update webhook",
     *     tags={"AI Agent - Webhooks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             @OA\Property(property="url", type="string"),
     *             @OA\Property(property="events", type="array", @OA\Items(type="string")),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Webhook updated")
     * )
     */
    public function update(Request $request, AgentWebhook $agentWebhook): JsonResponse
    {
        $this->authorize('update', $agentWebhook);

        $validator = Validator::make($request->all(), [
            'url' => ['sometimes', 'url', 'max:500'],
            'events' => ['sometimes', 'array', 'min:1'],
            'events.*' => ['required', 'string', 'in:message.sent,recommendation.created,action.completed,session.started,session.ended'],
            'is_active' => ['sometimes', 'boolean'],
            'retry_count' => ['sometimes', 'integer', 'min:0', 'max:5'],
            'timeout_seconds' => ['sometimes', 'integer', 'min:5', 'max:60'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $agentWebhook->update($request->only(['url', 'events', 'is_active', 'retry_count', 'timeout_seconds']));

        return response()->json(['data' => $agentWebhook]);
    }

    /**
     * @OA\Delete(
     *     path="/api/agent-webhooks/{id}",
     *     summary="Delete webhook",
     *     tags={"AI Agent - Webhooks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=204, description="Webhook deleted")
     * )
     */
    public function destroy(AgentWebhook $agentWebhook): JsonResponse
    {
        $this->authorize('delete', $agentWebhook);

        $agentWebhook->delete();

        return response()->json(null, 204);
    }

    /**
     * @OA\Post(
     *     path="/api/agent-webhooks/{id}/test",
     *     summary="Test webhook delivery",
     *     tags={"AI Agent - Webhooks"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Test webhook sent")
     * )
     */
    public function test(AgentWebhook $agentWebhook): JsonResponse
    {
        $this->authorize('update', $agentWebhook);

        app(\App\Services\WebhookService::class)->trigger(
            'webhook.test',
            [
                'message' => 'This is a test webhook from TripPlanner',
                'webhook_id' => $agentWebhook->id,
            ],
            $agentWebhook->user_id
        );

        return response()->json([
            'message' => 'Test webhook triggered. Check your endpoint logs.',
        ]);
    }
}
