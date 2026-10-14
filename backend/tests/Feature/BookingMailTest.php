<?php

namespace Tests\Feature;

use App\Jobs\ProcessBookingJob;
use App\Mail\BookingCreatedMail;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_job_sends_confirmation_email(): void
    {
        Mail::fake();

        $booking = Booking::factory()->create();

        $job = new ProcessBookingJob($booking->id);

        $job->handle();

        Mail::assertSent(
            BookingCreatedMail::class,
            function (BookingCreatedMail $mail) use ($booking) {
                return $mail->hasTo($booking->user->email);
            }
        );
    }
}
