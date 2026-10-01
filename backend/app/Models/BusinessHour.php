<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'day_of_week',
    'start_time',
    'end_time',
    'break_start',
    'break_end',
])]
class BusinessHour extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
