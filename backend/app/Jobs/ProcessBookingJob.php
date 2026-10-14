<?php

namespace App\Jobs;

use App\Mail\BookingCreatedMail;
use App\Models\Booking;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ProcessBookingJob implements ShouldQueue
{
    use Queueable;

    // Maximum number of attempts
    public int $tries = 3;

    // Wait 10 seconds before retrying
    public int $backoff = 10;

    // Maximum execution time in seconds
    public int $timeout = 60;

    public function __construct(
        public int $bookingId
    ) {}

    public function handle(): void
    {
        $booking = Booking::with('user')
            ->find($this->bookingId);

        if (! $booking) {
            Log::warning('Booking not found', [
                'booking_id' => $this->bookingId,
            ]);

            return;
        }

        Mail::to($booking->user->email)
            ->send(new BookingCreatedMail($booking));

        Log::info('Booking confirmation email processed', [
            'booking_id' => $booking->id,
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Booking processing permanently failed', [
            'booking_id' => $this->bookingId,
            'error' => $exception?->getMessage(),
        ]);
    }
}
