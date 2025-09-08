<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'iamgerwin@live.com'],
            [
                'name' => 'gerwin',
                'password' => Hash::make('password123'),
            ]
        );
    }
}