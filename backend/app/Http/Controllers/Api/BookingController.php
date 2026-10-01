<?php

namespace App\Http\Controllers\Api;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Services\AvailabilityService;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Booking::class);

        $user = $request->user();

        if ($user->role === 'customer') {
            $bookings = Booking::where('user_id', $user->id)->get();
        } else {
            $bookings = Booking::whereHas('service', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->get();
        }

        return response()->json([
            'bookings' => BookingResource::collection($bookings),
        ]);
    }
    public function show(Request $request, int $id)
    {
        $booking = Booking::with('service')->findOrFail($id);

        Gate::authorize('view', $booking);

        return response()->json([
            'booking' => new BookingResource($booking)
        ]);
    }
    public function store(
        StoreBookingRequest $request,
        AvailabilityService $availabilityService
    ) {
        $validated = $request->validated();

        Gate::authorize('create', Booking::class);

        return DB::transaction(
            function () use ($validated, $request, $availabilityService) {
                $service = Service::where('id', $validated['service_id'])
                    ->lockForUpdate()
                    ->firstOrFail();

                $startAt = Carbon::parse($validated['start_at']);

                if (!$availabilityService->isAvailable($service, $startAt)) {
                    return response()->json([
                        'message' => 'The selected time is not available.'
                    ], 409);
                }

                $endAt = $startAt->copy()->addMinutes($service->duration);

                $existingBookings = Booking::with('service')
                    ->where('service_id', $validated['service_id'])
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->get();

                if ($this->hasOverlap($startAt, $endAt, $existingBookings)) {
                    return response()->json([
                        'message' => 'The selected time is already booked.'
                    ], 409);
                }

                $booking = Booking::create([
                    'user_id' =>  $request->user()->id,
                    'service_id' => $validated['service_id'],
                    'start_at' => $startAt,
                    'status' => 'pending',
                ]);

                $booking->load('service');

                return response()->json([
                    'message' => 'Booking created successfully',
                    'booking' => new BookingResource($booking),
                ], 201);
            }
        );
    }
    public function destroy(Request $request, int $id)
    {
        $booking = Booking::with('service')->findOrFail($id);

        Gate::authorize('delete', $booking);

        if (!$booking->status->canTransitionTo(BookingStatus::CANCELLED)) {
            return response()->json([
                'message' => 'Booking cannot be cancelled in its current status.'
            ], 409);
        }

        $booking->status = BookingStatus::CANCELLED;
        $booking->save();

        return response()->json([
            'message' => 'Booking cancelled successfully',
            'booking' => $booking
        ]);
    }
    public function update(
        UpdateBookingRequest $request,
        int $id,
        AvailabilityService $availabilityService
    ) {
        return DB::transaction(
            function () use ($request, $id, $availabilityService) {
                $booking = Booking::with('service')->findOrFail($id);

                Gate::authorize('update', $booking);

                if (in_array($booking->status, [
                    BookingStatus::CANCELLED,
                    BookingStatus::COMPLETED,
                ], true)) {
                    return response()->json([
                        'message' => $booking->status === 'cancelled'
                            ? 'Booking is already cancelled.'
                            : 'Completed bookings cannot be updated.'
                    ], 409);
                }

                $validated = $request->validated();

                if (isset($validated['service_id'])) {
                    $booking->service_id = $validated['service_id'];
                }

                if (isset($validated['start_at'])) {
                    $booking->start_at = Carbon::parse($validated['start_at']);
                }

                $service = Service::where('id', $booking->service_id)
                    ->lockForUpdate()
                    ->first();

                $startAt = Carbon::parse($booking->start_at);
                $endAt = $startAt->copy()->addMinutes($service->duration);

                if (!$availabilityService->isAvailable($service, $startAt)) {
                    return response()->json([
                        'message' => 'The selected time is not available.'
                    ], 409);
                }

                $existingBookings = Booking::with('service')
                    ->where('service_id', $booking->service_id)
                    ->whereIn('status', ['pending', 'confirmed'])
                    ->where('id', '!=', $booking->id)
                    ->get();

                if ($this->hasOverlap($startAt, $endAt, $existingBookings)) {
                    return response()->json([
                        'message' => 'The selected time is already booked.'
                    ], 409);
                }

                $booking->save();

                return response()->json([
                    'message' => 'Booking updated successfully',
                    'booking' => new BookingResource($booking),
                ]);
            }
        );
    }
    private function hasOverlap(Carbon $startAt, Carbon $endAt, Collection $existingBookings)
    {
        foreach ($existingBookings as $existingBooking) {
            $existingStart = $existingBooking->start_at;
            $existingEnd = $existingStart->copy()->addMinutes($existingBooking->service->duration);

            if ($startAt < $existingEnd && $endAt > $existingStart) {
                return true;
            }
        }
        return false;
    }
    public function confirm(Request $request, int $id)
    {
        $booking = Booking::with('service')->findOrFail($id);

        Gate::authorize('confirm', $booking);

        if (!$booking->status->canTransitionTo(BookingStatus::CONFIRMED)) {
            return response()->json([
                'message' => 'Booking cannot be confirmed in its current status.'
            ], 409);
        }

        $booking->status = BookingStatus::CONFIRMED;
        $booking->save();

        return response()->json([
            'message' => 'Booking confirmed successfully',
            'booking' => new BookingResource($booking),
        ]);
    }
    public function complete(Request $request, int $id)
    {
        $booking = Booking::with('service')->findOrFail($id);

        Gate::authorize('complete', $booking);

        if (!$booking->status->canTransitionTo(BookingStatus::COMPLETED)) {
            return response()->json([
                'message' => 'Booking cannot be completed in its current status.'
            ], 409);
        }

        $booking->status = BookingStatus::COMPLETED;
        $booking->save();

        return response()->json([
            'message' => 'Booking completed successfully',
            'booking' => new BookingResource($booking),
        ]);
    }
}
