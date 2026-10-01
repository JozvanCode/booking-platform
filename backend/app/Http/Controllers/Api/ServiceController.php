<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ServiceResource;
use Illuminate\Http\Request;
use App\Models\Service;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\StoreServiceRequest;
use App\Http\Requests\UpdateServiceRequest;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Service::class);

        $services = Service::where('user_id', $request->user()->id)->get();

        return response()->json([
            'services' => ServiceResource::collection($services),
        ]);
    }
    public function store(StoreServiceRequest $request)
    {
        Gate::authorize('create', Service::class);

        $validated = $request->validated();

        $service = Service::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'price' => $validated['price'],
            'duration' => $validated['duration'],
        ]);

        return response()->json([
            'message' => 'Service created successfully',
            'service' => new ServiceResource($service),
        ], 201);
    }
    public function show(Request $request, int $id)
    {
        $service = Service::findOrFail($id);

        Gate::authorize('view', $service);

        return response()->json([
            'service' => new ServiceResource($service),
        ]);
    }
    public function update(UpdateServiceRequest $request, int $id)
    {
        $validated = $request->validated();

        $service = Service::findOrFail($id);

        Gate::authorize('update', $service);

        $service->update($validated);

        return response()->json([
            'message' => 'Service updated successfully',
            'service' => new ServiceResource($service),
        ]);
    }
    public function destroy(Request $request, int $id)
    {
        $service = Service::findOrFail($id);

        Gate::authorize('delete', $service);

        $service->delete();

        return response()->json([
            'message' => 'Service deleted successfully',
        ]);
    }
}
