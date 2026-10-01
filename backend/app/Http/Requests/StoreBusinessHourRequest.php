<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBusinessHourRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'provider';
    }

    public function rules(): array
    {
        return [
            'day_of_week' => [
                'required',
                'integer',
                'between:1,7',
                Rule::unique('business_hours', 'day_of_week')
                    ->where(fn($query) => $query->where('user_id', $this->user()->id)),
            ],
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            'break_start' => 'nullable|date_format:H:i',
            'break_end' => 'nullable|date_format:H:i',
        ];
    }
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $start = $this->input('start_time');
                $end = $this->input('end_time');
                $breakStart = $this->input('break_start');
                $breakEnd = $this->input('break_end');

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
