<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'provider';
    }

    public function rules(): array
    {
        return [
            'name' => 'required_without_all:price,duration|string|max:255',
            'price' => 'required_without_all:name,duration|integer|min:0',
            'duration' => 'required_without_all:name,price|integer|min:1',
        ];
    }
}
