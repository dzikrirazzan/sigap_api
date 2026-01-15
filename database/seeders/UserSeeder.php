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
        $admin = User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('kikipoiu'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
        if (!$admin->email_verified_at) {
            $admin->update(['email_verified_at' => now()]);
        }

        // Admin Dzikri
        $adminDzikri = User::firstOrCreate(
            ['email' => 'admindzikri@gmail.com'],
            [
                'name' => 'Admin Dzikri',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );
        if (!$adminDzikri->email_verified_at) {
            $adminDzikri->update(['email_verified_at' => now()]);
        }

        // Relawan
        $relawan = User::firstOrCreate(
            ['email' => 'relawan@gmail.com'],
            [
                'name' => 'Relawan Example',
                'password' => Hash::make('kikipoiu'),
                'role' => 'relawan',
                'nik' => '1234567890123456',
                'no_telp' => '081234567890',
                'email_verified_at' => now(),
            ]

        );
        if (!$relawan->email_verified_at) {
            $relawan->update(['email_verified_at' => now()]);
        }

        // User biasa
        $user = User::firstOrCreate(
            ['email' => 'user@gmail.com'],
            [
                'name' => 'User Example',
                'password' => Hash::make('kikipoiu'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );
        if (!$user->email_verified_at) {
            $user->update(['email_verified_at' => now()]);
        }

        // Relawan tambahan
        $relawan1 = User::firstOrCreate(
            ['email' => 'relawan1@gmail.com'],
            [
                'name' => 'Budi Santoso',
                'password' => Hash::make('kikipoiu'),
                'role' => 'relawan',
                'nik' => '3374010101990001',
                'no_telp' => '081234567891',
                'email_verified_at' => now(),
            ]
        );
        if (!$relawan1->email_verified_at) {
            $relawan1->update(['email_verified_at' => now()]);
        }

        $relawan2 = User::firstOrCreate(
            ['email' => 'relawan2@gmail.com'],
            [
                'name' => 'Siti Nurhaliza',
                'password' => Hash::make('kikipoiu'),
                'role' => 'relawan',
                'nik' => '3374010202990002',
                'no_telp' => '081234567892',
                'email_verified_at' => now(),
            ]
        );
        if (!$relawan2->email_verified_at) {
            $relawan2->update(['email_verified_at' => now()]);
        }

        $relawan3 = User::firstOrCreate(
            ['email' => 'relawan3@gmail.com'],
            [
                'name' => 'Ahmad Hidayat',
                'password' => Hash::make('kikipoiu'),
                'role' => 'relawan',
                'nik' => '3374010303990003',
                'no_telp' => '081234567893',
                'email_verified_at' => now(),
            ]
        );
        if (!$relawan3->email_verified_at) {
            $relawan3->update(['email_verified_at' => now()]);
        }

        $relawan4 = User::firstOrCreate(
            ['email' => 'relawan4@gmail.com'],
            [
                'name' => 'Dewi Lestari',
                'password' => Hash::make('kikipoiu'),
                'role' => 'relawan',
                'nik' => '3374010404990004',
                'no_telp' => '081234567894',
                'email_verified_at' => now(),
            ]
        );
        if (!$relawan4->email_verified_at) {
            $relawan4->update(['email_verified_at' => now()]);
        }

        $relawan5 = User::firstOrCreate(
            ['email' => 'relawan5@gmail.com'],
            [
                'name' => 'Rudi Hartono',
                'password' => Hash::make('kikipoiu'),
                'role' => 'relawan',
                'nik' => '3374010505990005',
                'no_telp' => '081234567895',
                'email_verified_at' => now(),
            ]
        );
        if (!$relawan5->email_verified_at) {
            $relawan5->update(['email_verified_at' => now()]);
        }

        // User biasa tambahan
        $user1 = User::firstOrCreate(
            ['email' => 'user1@gmail.com'],
            [
                'name' => 'Andi Wijaya',
                'password' => Hash::make('kikipoiu'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );
        if (!$user1->email_verified_at) {
            $user1->update(['email_verified_at' => now()]);
        }

        $user2 = User::firstOrCreate(
            ['email' => 'user2@gmail.com'],
            [
                'name' => 'Nina Marlina',
                'password' => Hash::make('kikipoiu'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );
        if (!$user2->email_verified_at) {
            $user2->update(['email_verified_at' => now()]);
        }

        $user3 = User::firstOrCreate(
            ['email' => 'user3@gmail.com'],
            [
                'name' => 'Joko Susilo',
                'password' => Hash::make('kikipoiu'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );
        if (!$user3->email_verified_at) {
            $user3->update(['email_verified_at' => now()]);
        }

        $user4 = User::firstOrCreate(
            ['email' => 'user4@gmail.com'],
            [
                'name' => 'Sri Wahyuni',
                'password' => Hash::make('kikipoiu'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );
        if (!$user4->email_verified_at) {
            $user4->update(['email_verified_at' => now()]);
        }

        $user5 = User::firstOrCreate(
            ['email' => 'user5@gmail.com'],
            [
                'name' => 'Eko Prasetyo',
                'password' => Hash::make('kikipoiu'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]
        );
        if (!$user5->email_verified_at) {
            $user5->update(['email_verified_at' => now()]);
        }
    }
}