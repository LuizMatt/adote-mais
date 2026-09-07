<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Administrator User (full access to register and delete pets)
        User::updateOrCreate(
            ['email' => 'admin@adotamais.local'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
            ]
        );

        // Agent User (pet assistance, updates, adoption workflow)
        User::updateOrCreate(
            ['email' => 'agent@adotamais.local'],
            [
                'name' => 'Zoonosis Agent',
                'password' => Hash::make('agent123'),
                'role' => 'agent',
            ]
        );
    }
}
