<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBusinessHourRequest;
use App\Http\Requests\UpdateBusinessHourRequest;
use App\Http\Resources\BusinessHourResource;
use App\Models\BusinessHour;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BusinessHourController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', BusinessHour::class);

        $businessHours = BusinessHour::where('user_id', $request->user()->id)
            ->orderBy('day_of_week')
            ->get();

        return response()->json([
            'business_hours' => BusinessHourResource::collection($businessHours),
        ]);
    }

    public function store(StoreBusinessHourRequest $request)
    {
        Gate::authorize('create', BusinessHour::class);

        $validated = $request->validated();

        $businessHour = BusinessHour::create([
            'user_id' => $request->user()->id,
            'day_of_week' => $validated['day_of_week'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'break_start' => $validated['break_start'] ?? null,
            'break_end' => $validated['break_end'] ?? null,
        ]);

        return response()->json([
            'message' => 'Business hours created successfully',
            'business_hour' => new BusinessHourResource($businessHour),
        ], 201);
    }
    public function update(UpdateBusinessHourRequest $request, int $id)
    {
        $businessHour = BusinessHour::findOrFail($id);

        Gate::authorize('update', $businessHour);

        $validated = $request->validated();

        $businessHour->update($validated);

        return response()->json([
            'message' => 'Business hours updated successfully',
            'business_hour' => new BusinessHourResource($businessHour),
        ]);
    }
    public function destroy(Request $request, int $id)
    {
        $businessHour = BusinessHour::findOrFail($id);

        Gate::authorize('delete', $businessHour);

        $businessHour->delete();

        return response()->json([
            'message' => 'Business hours deleted successfully',
        ]);
    }
}
