<?php

namespace Database\Seeders;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Demo Users
        $alice = User::create([
            'name' => 'Alice Johnson',
            'email' => 'alice@example.com',
            'password' => Hash::make('password'),
            'status_message' => '🚀 Building awesome real-time apps!',
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        $bob = User::create([
            'name' => 'Bob Smith',
            'email' => 'bob@example.com',
            'password' => Hash::make('password'),
            'status_message' => '☕ Coffee & Code.',
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        $charlie = User::create([
            'name' => 'Charlie Brown',
            'email' => 'charlie@example.com',
            'password' => Hash::make('password'),
            'status_message' => '🎧 Listening to lo-fi beats.',
            'is_online' => false,
            'last_seen_at' => now()->subMinutes(30),
        ]);

        $diana = User::create([
            'name' => 'Diana Prince',
            'email' => 'diana@example.com',
            'password' => Hash::make('password'),
            'status_message' => '✨ Available for new discussions.',
            'is_online' => false,
            'last_seen_at' => now()->subHours(2),
        ]);

        // 2. Direct Chat: Alice & Bob
        $directConv = Conversation::create([
            'type' => 'direct',
            'name' => null,
        ]);

        // Attach users via conversation_user pivot
        $directConv->users()->attach([
            $alice->id => ['joined_at' => now()->subDays(1), 'last_read_at' => now()],
            $bob->id => ['joined_at' => now()->subDays(1), 'last_read_at' => now()],
        ]);

        Message::create([
            'conversation_id' => $directConv->id,
            'user_id' => $alice->id,
            'type' => 'text',
            'body' => 'Hey Bob! Welcome to our new real-time chat app 👋',
            'created_at' => now()->subMinutes(15),
        ]);

        Message::create([
            'conversation_id' => $directConv->id,
            'user_id' => $bob->id,
            'type' => 'text',
            'body' => 'Hi Alice! This looks super clean and fast! Loving Laravel Reverb ⚡',
            'created_at' => now()->subMinutes(10),
        ]);

        Message::create([
            'conversation_id' => $directConv->id,
            'user_id' => $alice->id,
            'type' => 'text',
            'body' => 'Let\'s test group chats and file sharing next!',
            'created_at' => now()->subMinutes(2),
        ]);

        // 3. Group Chat: Project Team
        $groupConv = Conversation::create([
            'type' => 'group',
            'name' => 'Project Alpha Team',
        ]);

        $groupConv->users()->attach([
            $alice->id => ['joined_at' => now()->subDays(2), 'last_read_at' => now()],
            $bob->id => ['joined_at' => now()->subDays(2), 'last_read_at' => now()],
            $charlie->id => ['joined_at' => now()->subDays(2), 'last_read_at' => now()],
            $diana->id => ['joined_at' => now()->subDays(2), 'last_read_at' => now()],
        ]);

        Message::create([
            'conversation_id' => $groupConv->id,
            'user_id' => $alice->id,
            'type' => 'text',
            'body' => 'Hello everyone! Welcome to the Project Alpha chat room 🎉',
            'created_at' => now()->subMinutes(20),
        ]);

        Message::create([
            'conversation_id' => $groupConv->id,
            'user_id' => $charlie->id,
            'type' => 'text',
            'body' => 'Excited to be here! Let me know if you need help with frontend or backend.',
            'created_at' => now()->subMinutes(5),
        ]);
    }
}
