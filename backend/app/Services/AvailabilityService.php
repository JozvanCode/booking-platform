<?php

namespace App\Services;

use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AvailabilityService
{
    public function getAvailableSlots(Service $service, Carbon $date): array
    {
        $dayOfWeek = $date->dayOfWeekIso;

        $businessHour = $service->user
            ->businessHours()
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$businessHour) {
            return [];
        }

        $bookings = $service->bookings()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('start_at', $date)
            ->get();

        $slots = [];

        $current = Carbon::parse(
            $date->toDateString() . ' ' . $businessHour->start_time
        );

        $end = Carbon::parse(
            $date->toDateString() . ' ' . $businessHour->end_time
        );

        $breakStart = $businessHour->break_start
            ? Carbon::parse(
                $date->toDateString() . ' ' . $businessHour->break_start
            )
            : null;

        $breakEnd = $businessHour->break_end
            ? Carbon::parse(
                $date->toDateString() . ' ' . $businessHour->break_end
            )
            : null;

        while ($current->copy()->addMinutes($service->duration) <= $end) {
            $slotEnd = $current->copy()->addMinutes($service->duration);

            $isBreak = false;

            if ($breakStart && $breakEnd) {
                $isBreak =
                    $current < $breakEnd &&
                    $slotEnd > $breakStart;
            }

            $isBooked = false;

            foreach ($bookings as $booking) {
                $bookingStart = $booking->start_at;

                $bookingEnd = $bookingStart->copy()
                    ->addMinutes($service->duration);

                if (
                    $current < $bookingEnd &&
                    $slotEnd > $bookingStart
                ) {
                    $isBooked = true;
                    break;
                }
            }

            if (!$isBreak && !$isBooked) {
                $slots[] = $current->format('H:i');
            }

            $current->addMinutes($service->duration);
        }

        return $slots;
    }
    public function isAvailable(
        Service $service,
        Carbon $startAt
    ): bool {
        $slots = $this->getAvailableSlots(
            $service,
            $startAt->copy()->startOfDay()
        );

        return in_array(
            $startAt->format('H:i'),
            $slots,
            true
        );
    }
}
