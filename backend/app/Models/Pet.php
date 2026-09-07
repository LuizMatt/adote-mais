<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pet extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'species',
        'size',
        'approximate_age',
        'gender',
        'photo_path',
        'description',
        'vaccines',
        'status',
        'rescue_date',
        'adopter_name',
        'adoption_date',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'vaccines' => 'array',
            'rescue_date' => 'date',
            'adoption_date' => 'date',
        ];
    }
}
