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

        // Relawan tambahan
        User::firstOrCreate(
            ['email' => 'relawan1@gmail.com'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('kikipoiu'),
                'role' => 'relawan',
                'nik' => '3374010101990001',
                'no_telp' => '081234567891',
            ]
        );

        User::firstOrCreate(
            ['email' => 'relawan2@gmail.com'],
            [
                'name' => 'Siti Nurhaliza',
                'password' => Hash::make('kikipoiu'),
                'role' => 'relawan',
                'nik' => '3374010202990002',
                'no_telp' => '081234567892',
            ]
        );

        User::firstOrCreate(
            ['email' => 'relawan3@gmail.com'],
            [
                'name' => 'Ahmad Hidayat',
                'password' => Hash::make('kikipoiu'),
                'role' => 'relawan',
                'nik' => '3374010303990003',
                'no_telp' => '081234567893',
            ]
        );

        User::firstOrCreate(
            ['email' => 'relawan4@gmail.com'],
            [
                'name' => 'Dewi Lestari',
                'password' => Hash::make('kikipoiu'),
                'role' => 'relawan',
                'nik' => '3374010404990004',
                'no_telp' => '081234567894',
            ]
        );

        User::firstOrCreate(
            ['email' => 'relawan5@gmail.com'],
            [
                'name' => 'Rudi Hartono',
                'password' => Hash::make('kikipoiu'),
                'role' => 'relawan',
                'nik' => '3374010505990005',
                'no_telp' => '081234567895',
            ]
        );

        // User biasa tambahan
        User::firstOrCreate(
            ['email' => 'user1@gmail.com'],
            [
                'name' => 'Andi Wijaya',
                'password' => Hash::make('kikipoiu'),
                'role' => 'user',
            ]
        );

        User::firstOrCreate(
            ['email' => 'user2@gmail.com'],
            [
                'name' => 'Nina Marlina',
                'password' => Hash::make('kikipoiu'),
                'role' => 'user',
            ]
        );

        User::firstOrCreate(
            ['email' => 'user3@gmail.com'],
            [
                'name' => 'Joko Susilo',
                'password' => Hash::make('kikipoiu'),
                'role' => 'user',
            ]
        );

        User::firstOrCreate(
            ['email' => 'user4@gmail.com'],
            [
                'name' => 'Sri Wahyuni',
                'password' => Hash::make('kikipoiu'),
                'role' => 'user',
            ]
        );

        User::firstOrCreate(
            ['email' => 'user5@gmail.com'],
            [
                'name' => 'Eko Prasetyo',
                'password' => Hash::make('kikipoiu'),
                'role' => 'user',
            ]
        );
    }
}
