<?php

namespace App\Enums;

enum BookingStatus: string
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function canTransitionTo(self $status): bool
    {
        return match ($this) {
            self::PENDING => in_array($status, [
                self::CONFIRMED,
                self::CANCELLED,
            ], true),

            self::CONFIRMED => in_array($status, [
                self::COMPLETED,
                self::CANCELLED,
            ], true),

            self::CANCELLED,
            self::COMPLETED => false,
        };
    }
}
