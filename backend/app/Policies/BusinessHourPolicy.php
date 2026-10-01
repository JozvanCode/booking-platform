<?php

namespace App\Policies;

use App\Models\BusinessHour;
use App\Models\User;

class BusinessHourPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'provider';
    }

    public function create(User $user): bool
    {
        return $user->role === 'provider';
    }

    public function view(User $user, BusinessHour $businessHour): bool
    {
        return $user->role === 'provider'
            && $businessHour->user_id === $user->id;
    }

    public function update(User $user, BusinessHour $businessHour): bool
    {
        return $user->role === 'provider'
            && $businessHour->user_id === $user->id;
    }

    public function delete(User $user, BusinessHour $businessHour): bool
    {
        return $user->role === 'provider'
            && $businessHour->user_id === $user->id;
    }
}
