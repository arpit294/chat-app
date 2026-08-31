<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Message $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message->load(['user', 'attachments']);
    }

    /**
     * Get the channels the event should broadcast on (Presence Channel).
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel('chat.' . $this->message->conversation_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    /**
     * Get the data that should be broadcasted including attachment info.
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'user_id' => $this->message->user_id,
            'body' => $this->message->body,
            'type' => $this->message->type,
            'created_at' => $this->message->created_at->toISOString(),
            'formatted_time' => $this->message->formatted_time,
            'formatted_date' => $this->message->formatted_date,
            'sender' => [
                'id' => $this->message->user?->id,
                'name' => $this->message->user?->name ?? 'User',
                'avatar_url' => $this->message->user?->avatar_url,
            ],
            'attachments' => $this->message->attachments->map(fn($att) => [
                'id' => $att->id,
                'original_name' => $att->original_name,
                'mime_type' => $att->mime_type,
                'file_size' => $att->file_size,
                'formatted_size' => $att->formatted_size,
                'url' => $att->url,
                'thumbnail_url' => $att->thumbnail_url,
                'is_image' => $att->is_image,
                'is_pdf' => $att->is_pdf,
            ])->values(),
        ];
    }
}
