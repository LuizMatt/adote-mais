<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:100',
            'species' => 'sometimes|required|in:dog,cat,other',
            'size' => 'sometimes|required|in:small,medium,large',
            'approximate_age' => 'sometimes|required|string|max:50',
            'gender' => 'sometimes|required|in:male,female',
            'description' => 'nullable|string|max:1000',
            'vaccines' => 'nullable|array',
            'vaccines.*.name' => 'required_with:vaccines|string|max:100',
            'vaccines.*.applied_at' => 'nullable|date',
            'rescue_date' => 'nullable|date',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ];
    }
}
