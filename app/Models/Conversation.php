<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
    ];

    protected $appends = [
        'avatar_url',
        'display_name',
    ];

    /**
     * Conversation belongs to many users through conversation_user pivot table.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_user')
            ->withPivot(['joined_at', 'last_read_at'])
            ->withTimestamps();
    }

    /**
     * Conversation has many messages.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Relationship for the latest message in the conversation.
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function isDirect(): bool
    {
        return $this->type === 'direct';
    }

    public function isGroup(): bool
    {
        return $this->type === 'group';
    }

    /**
     * Get the other user in a 1-on-1 direct chat.
     */
    public function getOtherUser(?int $currentUserId = null): ?User
    {
        if (!$this->isDirect()) {
            return null;
        }

        $userId = $currentUserId ?? Auth::id();
        return $this->users->firstWhere('id', '!=', $userId);
    }

    /**
     * Get display name for the conversation.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->isGroup()) {
            return $this->name ?? 'Unnamed Group';
        }

        $other = $this->getOtherUser();
        return $other ? $other->name : 'Direct Chat';
    }

    /**
     * Get avatar URL for the conversation.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->isGroup()) {
            return 'https://ui-avatars.com/api/?name=' . urlencode($this->name ?? 'Group') . '&background=6f42c1&color=fff&size=128';
        }

        $other = $this->getOtherUser();
        return $other ? $other->avatar_url : 'https://ui-avatars.com/api/?name=Chat&background=0d6efd&color=fff&size=128';
    }
}
