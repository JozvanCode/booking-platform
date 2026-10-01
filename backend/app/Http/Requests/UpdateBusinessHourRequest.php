<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateBusinessHourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'provider';
    }

    public function rules(): array
    {
        return [
            'day_of_week' => [
                'sometimes',
                'integer',
                'between:1,7',
                Rule::unique('business_hours', 'day_of_week')
                    ->where(fn($query) => $query->where('user_id', $this->user()->id))
                    ->ignore($this->route('id')),
            ],

            'start_time' => 'sometimes|date_format:H:i',
            'end_time' => 'sometimes|date_format:H:i',

            'break_start' => 'sometimes|nullable|date_format:H:i',
            'break_end' => 'sometimes|nullable|date_format:H:i',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $businessHour = $this->route('id')
                    ? \App\Models\BusinessHour::find($this->route('id'))
                    : null;

                $start = $this->input('start_time', $businessHour?->start_time);
                $end = $this->input('end_time', $businessHour?->end_time);

                $breakStart = $this->input('break_start', $businessHour?->break_start);
                $breakEnd = $this->input('break_end', $businessHour?->break_end);

                if ($start && $end && $start >= $end) {
                    $validator->errors()->add(
                        'start_time',
                        'Start time must be before end time.'
                    );
                }

                if (($breakStart && !$breakEnd) || (!$breakStart && $breakEnd)) {
                    $validator->errors()->add(
                        'break_start',
                        'Both break start and break end are required.'
                    );
                }

                if ($breakStart && $breakEnd && $breakStart >= $breakEnd) {
                    $validator->errors()->add(
                        'break_start',
                        'Break start must be before break end.'
                    );
                }

                if (
                    $start &&
                    $end &&
                    $breakStart &&
                    $breakEnd &&
                    ($breakStart < $start || $breakEnd > $end)
                ) {
                    $validator->errors()->add(
                        'break_start',
                        'Break must be within working hours.'
                    );
                }
            },
        ];
    }
}
