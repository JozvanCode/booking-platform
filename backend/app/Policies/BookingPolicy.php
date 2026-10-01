<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Create a new policy instance.
     */
    public function __construct()
    {
        //
    }
    public function create(User $user): bool
    {
        return $user->role === 'customer';
    }
    public function view(User $user, Booking $booking): bool
    {
        return ($user->role === 'customer' && $user->id === $booking->user_id)
            || ($user->role === 'provider' && $user->id === $booking->service->user_id);
    }
    public function viewAny(User $user): bool
    {
        return $user->role === 'customer' || $user->role === 'provider';
    }
    public function update(User $user, Booking $booking): bool
    {
        return $user->role === 'customer'
            && $user->id === $booking->user_id;
    }
    public function delete(User $user, Booking $booking): bool
    {
        return $user->role === 'customer'
            && $user->id === $booking->user_id;
    }
    public function confirm(User $user, Booking $booking): bool
    {
        return $user->role === 'provider'
            && $booking->service->user_id === $user->id;
    }
    public function complete(User $user, Booking $booking): bool
    {
        return $user->role === 'provider'
            && $booking->service->user_id === $user->id;
    }
}
