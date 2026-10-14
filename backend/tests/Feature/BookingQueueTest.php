<?php

namespace Tests\Feature;

use App\Jobs\ProcessBookingJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BookingQueueTest extends TestCase
{
    public function test_booking_job_can_be_dispatched(): void
    {
        Queue::fake();

        ProcessBookingJob::dispatch(123);

        Queue::assertPushed(
            ProcessBookingJob::class,
            fn (ProcessBookingJob $job) => $job->bookingId === 123
        );
    }
}
