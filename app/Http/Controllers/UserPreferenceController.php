<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserPreferenceRequest;
use App\Http\Requests\UpdateUserPreferenceRequest;
use App\Http\Resources\UserPreferenceResource;
use App\Models\UserPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserPreferenceController extends Controller
{
    /**
     * Display a listing of the user's preferences.
     * 
     * @OA\Get(
     *     path="/api/user-preferences",
     *     summary="List user preferences",
     *     tags={"AI Agent - User Preferences"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="filter[type]",
     *         in="query",
     *         description="Filter by preference type",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response=200, description="Successful operation"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized")
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = UserPreference::where('user_id', $request->user()->id);

        // Filter by preference type
        if ($request->has('filter.type')) {
            $query->where('preference_type', $request->input('filter.type'));
        }

        $preferences = $query->orderBy('priority', 'desc')->get();

        return UserPreferenceResource::collection($preferences);
    }

    /**
     * Store a newly created preference.
     *
     * @OA\Post(
     *     path="/api/user-preferences",
     *     summary="Create a new user preference",
     *     description="Store user travel preferences for AI personalization",
     *     tags={"AI Agent - User Preferences"},
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"preference_type", "value"},
     *             @OA\Property(
     *                 property="preference_type",
     *                 type="string",
     *                 enum={"travel_style", "budget_range", "dietary_restrictions", "accessibility_needs"},
     *                 description="Type of preference",
     *                 example="travel_style"
     *             ),
     *             @OA\Property(
     *                 property="value",
     *                 type="object",
     *                 description="Preference data",
     *                 example={"styles": {"cultural", "foodie"}, "pace": "moderate"}
     *             ),
     *             @OA\Property(
     *                 property="priority",
     *                 type="integer",
     *                 minimum=1,
     *                 maximum=10,
     *                 description="Priority level (1-10, higher = more important)",
     *                 example=8
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Preference created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="preference_type", type="string"),
     *                 @OA\Property(property="value", type="object"),
     *                 @OA\Property(property="priority", type="integer")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function store(StoreUserPreferenceRequest $request): JsonResponse
    {
        $preference = UserPreference::create([
            'user_id' => $request->user()->id,
            'preference_type' => $request->input('preference_type'),
            'value' => $request->input('value'),
            'priority' => $request->input('priority', 5),
        ]);

        return (new UserPreferenceResource($preference))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified preference.
     *
     * @OA\Get(
     *     path="/api/user-preferences/{id}",
     *     summary="Get preference details",
     *     tags={"AI Agent - User Preferences"},
     *     security={{"sanctum":{}}},
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
    public function show(UserPreference $userPreference): UserPreferenceResource
    {
        $this->authorize('view', $userPreference);

        return new UserPreferenceResource($userPreference);
    }

    /**
     * Update the specified preference.
     *
     * @OA\Patch(
     *     path="/api/user-preferences/{id}",
     *     summary="Update user preference",
     *     tags={"AI Agent - User Preferences"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Preference updated"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *     @OA\Response(response=422, ref="#/components/responses/ValidationError")
     * )
     */
    public function update(UpdateUserPreferenceRequest $request, UserPreference $userPreference): UserPreferenceResource
    {
        $this->authorize('update', $userPreference);

        $userPreference->update($request->only(['value', 'priority']));

        return new UserPreferenceResource($userPreference);
    }

    /**
     * Remove the specified preference.
     *
     * @OA\Delete(
     *     path="/api/user-preferences/{id}",
     *     summary="Delete user preference",
     *     tags={"AI Agent - User Preferences"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=204, description="Preference deleted"),
     *     @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *     @OA\Response(response=403, ref="#/components/responses/Forbidden")
     * )
     */
    public function destroy(UserPreference $userPreference): JsonResponse
    {
        $this->authorize('delete', $userPreference);

        $userPreference->delete();

        return response()->json(null, 204);
    }
}
