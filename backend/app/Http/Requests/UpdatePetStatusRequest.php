<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePetStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:available,in_process,adopted',
            'adopter_name' => 'required_if:status,adopted|nullable|string|max:150',
            'adoption_date' => 'nullable|date',
        ];
    }

    public function messages(): array
    {
        return [
            'adopter_name.required_if' => 'The adopter name is required when marking a pet as adopted.',
        ];
    }
}
