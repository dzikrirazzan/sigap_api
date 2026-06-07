<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    private function markEmailVerified(User $user): void
    {
        if (!$user->hasVerifiedEmail()) {
            $user->email_verified_at = now();
            $user->save();
        }
    }

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
        $regularUser = User::firstOrCreate(
            ['email' => 'user@gmail.com'],
            [
                'name' => 'User Example',
                'password' => Hash::make('kikipoiu'),
                'role' => 'user',
            ]
        );
        $this->markEmailVerified($regularUser);

        // User demo mahasiswa
        $studentUser = User::firstOrCreate(
            ['email' => 'dzikrirazzan@students.undip.ac.id'],
            [
                'name' => 'Dzikri Razzan Athallah',
                'password' => Hash::make('#4thDimension'),
                'role' => 'user',
                'nim' => '24060122140123',
                'jurusan' => 'Informatika',
                'no_telp' => '081234567891',
            ]
        );
        $this->markEmailVerified($studentUser);
    }
}
