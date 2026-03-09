<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Survey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    /**
     * List surveys (by lead or branch).
     *
     * @OA\Get(
     *     path="/solar/surveys",
     *     tags={"Solar - Site Survey"},
     *     summary="List Surveys",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="lead_id", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="status", in="query", @OA\Schema(type="string", enum={"pending","in_progress","completed"})),
     *     @OA\Parameter(name="page", in="query", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $query = Survey::query()->with(['lead:id,customer_name,phone,status', 'engineer:id,name']);

        if ($user->role !== 'super_admin') {
            $query->where('organization_id', $user->organization_id);
        }
        if ($request->filled('lead_id')) {
            $query->where('lead_id', $request->lead_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $surveys = $query->latest()->paginate($perPage);

        return response()->json($surveys);
    }

    /**
     * Create survey for a lead (Site Survey Engineer / Branch Manager).
     *
     * @OA\Post(
     *     path="/solar/surveys",
     *     tags={"Solar - Site Survey"},
     *     summary="Create Survey",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"lead_id"},
     *         @OA\Property(property="lead_id", type="integer"),
     *         @OA\Property(property="engineer_id", type="integer"),
     *         @OA\Property(property="roof_type", type="string"),
     *         @OA\Property(property="roof_size_sqft", type="number"),
     *         @OA\Property(property="shadow_analysis", type="string"),
     *         @OA\Property(property="direction", type="string"),
     *         @OA\Property(property="inverter_capacity_recommendation", type="string"),
     *         @OA\Property(property="system_size_kw", type="number"),
     *         @OA\Property(property="load_analysis", type="string"),
     *         @OA\Property(property="electrical_connection_notes", type="string"),
     *         @OA\Property(property="report_url", type="string")
     *     )),
     *     @OA\Response(response=201, description="Created"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=422, description="Validation Error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        $validated = $request->validate([
            'lead_id' => ['required', 'integer', 'exists:leads,id'],
            'engineer_id' => ['nullable', 'integer', 'exists:users,id'],
            'roof_type' => ['nullable', 'string', 'max:50'],
            'roof_size_sqft' => ['nullable', 'numeric', 'min:0'],
            'shadow_analysis' => ['nullable', 'string', 'max:100'],
            'direction' => ['nullable', 'string', 'max:20'],
            'inverter_capacity_recommendation' => ['nullable', 'string', 'max:50'],
            'system_size_kw' => ['nullable', 'numeric', 'min:0'],
            'load_analysis' => ['nullable', 'string'],
            'electrical_connection_notes' => ['nullable', 'string'],
            'report_url' => ['nullable', 'string', 'max:500'],
        ]);

        $lead = Lead::findOrFail($validated['lead_id']);
        if ($user->role !== 'super_admin' && $lead->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        if ($lead->survey()->exists()) {
            return response()->json(['message' => 'Lead already has a survey.'], 422);
        }

        $validated['organization_id'] = $lead->organization_id;
        $validated['engineer_id'] = $validated['engineer_id'] ?? $user->id;
        $validated['status'] = 'pending';

        $survey = Survey::create($validated);
        $survey->load(['lead:id,customer_name,phone', 'engineer:id,name']);

        return response()->json($survey, 201);
    }

    /**
     * Get survey.
     *
     * @OA\Get(
     *     path="/solar/surveys/{id}",
     *     tags={"Solar - Site Survey"},
     *     summary="Get Survey",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $survey = Survey::with(['lead', 'engineer', 'organization'])->findOrFail($id);
        $user = auth('api')->user();
        if ($user->role !== 'super_admin' && $survey->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }
        return response()->json($survey);
    }

    /**
     * Update survey (e.g. complete with report).
     *
     * @OA\Put(
     *     path="/solar/surveys/{id}",
     *     tags={"Solar - Site Survey"},
     *     summary="Update Survey",
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(@OA\JsonContent(
     *         @OA\Property(property="roof_type", type="string"),
     *         @OA\Property(property="roof_size_sqft", type="number"),
     *         @OA\Property(property="system_size_kw", type="number"),
     *         @OA\Property(property="report_url", type="string"),
     *         @OA\Property(property="status", type="string", enum={"pending","in_progress","completed"})
     *     )),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=403, description="Forbidden"),
     *     @OA\Response(response=404, description="Not Found")
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $survey = Survey::findOrFail($id);
        $user = auth('api')->user();
        if ($user->role !== 'super_admin' && $survey->organization_id !== $user->organization_id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'engineer_id' => ['nullable', 'integer', 'exists:users,id'],
            'roof_type' => ['nullable', 'string', 'max:50'],
            'roof_size_sqft' => ['nullable', 'numeric', 'min:0'],
            'shadow_analysis' => ['nullable', 'string', 'max:100'],
            'direction' => ['nullable', 'string', 'max:20'],
            'inverter_capacity_recommendation' => ['nullable', 'string', 'max:50'],
            'system_size_kw' => ['nullable', 'numeric', 'min:0'],
            'load_analysis' => ['nullable', 'string'],
            'electrical_connection_notes' => ['nullable', 'string'],
            'report_url' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', 'in:pending,in_progress,completed'],
            'survey_date' => ['nullable', 'date'],
        ]);

        if (isset($validated['status']) && $validated['status'] === 'completed') {
            $validated['completed_at'] = now();
        }
        $survey->update($validated);
        $survey->load(['lead', 'engineer']);

        return response()->json($survey);
    }
}
