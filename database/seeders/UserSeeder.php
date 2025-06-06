<?php

namespace Database\Seeders;

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
        // Admin utama
        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('kikipoiu'),
                'role' => 'admin',
            ]
        );

        // Admin Dzikri
        User::firstOrCreate(
            ['email' => 'admindzikri@gmail.com'],
            [
                'name' => 'Admin Dzikri',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Relawan
        User::firstOrCreate(
            ['email' => 'relawan@gmail.com'],
            [
                'name' => 'Relawan Example',
                'password' => Hash::make('kikipoiu'),
                'role' => 'relawan',
                'nik' => '1234567890123456',
                'no_telp' => '081234567890',
            ]
        );

        // User biasa
        User::firstOrCreate(
            ['email' => 'user@gmail.com'],
            [
                'name' => 'User Example',
                'password' => Hash::make('kikipoiu'),
                'role' => 'user',
            ]
        );
    }
}