<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Determine whether the user can view the conversation and its messages.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        return $conversation->users()->where('users.id', $user->id)->exists();
    }

    /**
     * Determine whether the user can send messages to the conversation.
     */
    public function sendMessage(User $user, Conversation $conversation): bool
    {
        return $conversation->users()->where('users.id', $user->id)->exists();
    }
}
