<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'service_id', 'start_at', 'status'])]
class Booking extends Model
{
  public function user()
  {
    return $this->belongsTo(User::class);
  }
  public function service()
  {
    return $this->belongsTo(Service::class);
  }
  protected function casts(): array
  {
    return [
      'start_at' => 'datetime',
      'status' => BookingStatus::class,
    ];
  }
}
