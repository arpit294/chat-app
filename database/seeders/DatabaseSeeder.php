<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with fresh demo users.
     */
    public function run(): void
    {
        // Clean Demo Users (Fresh Start - Zero Old Messages)
        User::updateOrCreate(
            ['email' => 'alice@example.com'],
            [
                'name' => 'Alice Johnson',
                'password' => Hash::make('password'),
                'status_message' => '🚀 Building awesome real-time apps!',
                'is_online' => true,
                'last_seen_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'bob@example.com'],
            [
                'name' => 'Bob Smith',
                'password' => Hash::make('password'),
                'status_message' => '☕ Coffee & Code.',
                'is_online' => true,
                'last_seen_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['email' => 'charlie@example.com'],
            [
                'name' => 'Charlie Brown',
                'password' => Hash::make('password'),
                'status_message' => '🎧 Listening to lo-fi beats.',
                'is_online' => false,
                'last_seen_at' => now()->subMinutes(30),
            ]
        );

        User::updateOrCreate(
            ['email' => 'diana@example.com'],
            [
                'name' => 'Diana Prince',
                'password' => Hash::make('password'),
                'status_message' => '✨ Available for new discussions.',
                'is_online' => false,
                'last_seen_at' => now()->subHours(2),
            ]
        );
    }
}
