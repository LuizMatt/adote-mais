<?php

namespace Database\Seeders;

use App\Models\Pet;
use Illuminate\Database\Seeder;

class PetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pets = [
            [
                'name' => 'Pipoca',
                'species' => 'dog',
                'size' => 'small',
                'approximate_age' => '1 year',
                'gender' => 'female',
                'photo_path' => null,
                'description' => 'Very playful, docile and energetic. Great for apartments.',
                'vaccines' => [
                    ['name' => 'Rabies', 'applied_at' => '2026-01-10'],
                    ['name' => 'DHPP (Dose 1)', 'applied_at' => '2026-02-15'],
                ],
                'status' => 'available',
                'rescue_date' => '2026-01-05',
                'adopter_name' => null,
                'adoption_date' => null,
            ],
            [
                'name' => 'Thor',
                'species' => 'dog',
                'size' => 'large',
                'approximate_age' => '3 years',
                'gender' => 'male',
                'photo_path' => null,
                'description' => 'Imposing size but very calm and gentle. Loves long walks.',
                'vaccines' => [
                    ['name' => 'Rabies', 'applied_at' => '2025-11-20'],
                    ['name' => 'DHPP Annual', 'applied_at' => '2025-11-20'],
                    ['name' => 'Giardia', 'applied_at' => '2025-12-05'],
                ],
                'status' => 'available',
                'rescue_date' => '2025-10-15',
                'adopter_name' => null,
                'adoption_date' => null,
            ],
            [
                'name' => 'Mimi',
                'species' => 'cat',
                'size' => 'small',
                'approximate_age' => '6 months',
                'gender' => 'female',
                'photo_path' => null,
                'description' => 'Affectionate kitten, purrs constantly and gets along well with other cats.',
                'vaccines' => [
                    ['name' => 'FVRCP', 'applied_at' => '2026-02-01'],
                ],
                'status' => 'available',
                'rescue_date' => '2026-01-20',
                'adopter_name' => null,
                'adoption_date' => null,
            ],
            [
                'name' => 'Simba',
                'species' => 'cat',
                'size' => 'medium',
                'approximate_age' => '2 years',
                'gender' => 'male',
                'photo_path' => null,
                'description' => 'Tabby cat, independent and calm. Excellent companion.',
                'vaccines' => [
                    ['name' => 'Rabies', 'applied_at' => '2025-09-12'],
                    ['name' => 'FVRCP', 'applied_at' => '2025-09-12'],
                ],
                'status' => 'in_process',
                'rescue_date' => '2025-08-30',
                'adopter_name' => null,
                'adoption_date' => null,
            ],
            [
                'name' => 'Caramelo',
                'species' => 'dog',
                'size' => 'medium',
                'approximate_age' => '4 years',
                'gender' => 'male',
                'photo_path' => null,
                'description' => 'Classic friendly caramel dog: loyal companion, loves children, very smart.',
                'vaccines' => [
                    ['name' => 'Rabies', 'applied_at' => '2025-05-10'],
                    ['name' => 'DHPP', 'applied_at' => '2025-05-10'],
                ],
                'status' => 'adopted',
                'rescue_date' => '2025-04-18',
                'adopter_name' => 'Carlos Eduardo Pereira',
                'adoption_date' => '2025-06-25',
            ],
            [
                'name' => 'Luna',
                'species' => 'dog',
                'size' => 'medium',
                'approximate_age' => '2 years',
                'gender' => 'female',
                'photo_path' => null,
                'description' => 'Sociable with other dogs, neutered and vaccinated.',
                'vaccines' => [
                    ['name' => 'Rabies', 'applied_at' => '2026-01-18'],
                ],
                'status' => 'available',
                'rescue_date' => '2026-01-10',
                'adopter_name' => null,
                'adoption_date' => null,
            ],
        ];

        foreach ($pets as $pet) {
            Pet::create($pet);
        }
    }
}
