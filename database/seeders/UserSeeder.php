<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'John Daniel Sarmiento Tejano',
            'email' => 'danieltejano@outlook.ph',
            'password' => Hash::make('daniel123')
        ]);

        User::create([
            'name' => 'Sample User',
            'email' => 'sample@translate.test',
            'password' => Hash::make('password123')
        ]);
    }
}
