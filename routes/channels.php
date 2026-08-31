<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. For presence channels, return an array of user data
| if authorized, or false if not authorized.
|
*/

// Presence channel for real-time conversation tracking & online status
Broadcast::channel('chat.{conversationId}', function (User $user, int $conversationId) {
    $isParticipant = DB::table('conversation_user')
        ->where('conversation_id', $conversationId)
        ->where('user_id', $user->id)
        ->exists();

    if ($isParticipant) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'avatar_url' => $user->avatar_url,
        ];
    }

    return false;
});

// Private channel for user-specific direct notifications
Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId;
});
