<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class TestFailureJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(): void
    {
        Log::info('TestFailureJob successfully processed', [
            'attempt' => $this->attempts(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('TestFailureJob permanently failed', [
            'error' => $exception?->getMessage(),
        ]);
    }
}
