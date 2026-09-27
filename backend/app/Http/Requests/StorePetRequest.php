<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'species' => 'required|in:dog,cat,other',
            'size' => 'required|in:small,medium,large',
            'approximate_age' => 'required|string|max:50',
            'gender' => 'required|in:male,female',
            'description' => 'nullable|string|max:1000',
            'vaccines' => 'nullable|array',
            'vaccines.*.name' => 'required_with:vaccines|string|max:100',
            'vaccines.*.applied_at' => 'nullable|date',
            'rescue_date' => 'nullable|date',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ];
    }
}
