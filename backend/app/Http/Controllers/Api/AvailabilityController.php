<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AvailabilityRequest;
use App\Models\Service;
use App\Services\AvailabilityService;
use Carbon\Carbon;

class AvailabilityController extends Controller
{
    public function index(
        AvailabilityRequest $request,
        Service $service,
        AvailabilityService $availabilityService
    ) {
        $date = Carbon::createFromFormat(
            'Y-m-d',
            $request->validated('date')
        );

        $slots = $availabilityService->getAvailableSlots(
            $service,
            $date
        );

        return response()->json([
            'service' => [
                'id' => $service->id,
                'name' => $service->name,
                'duration' => $service->duration,
            ],
            'date' => $date->toDateString(),
            'available_slots' => $slots,
        ]);
    }
}
