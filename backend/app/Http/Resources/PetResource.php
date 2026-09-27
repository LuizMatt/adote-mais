<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class PetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'species' => $this->species,
            'size' => $this->size,
            'approximate_age' => $this->approximate_age,
            'gender' => $this->gender,
            'photo_url' => $this->photo_path ? url(Storage::url($this->photo_path)) : null,
            'description' => $this->description,
            'vaccines' => $this->vaccines ?? [],
            'status' => $this->status,
            'rescue_date' => $this->rescue_date?->format('Y-m-d'),
            'adopter_name' => $this->adopter_name,
            'adoption_date' => $this->adoption_date?->format('Y-m-d'),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
