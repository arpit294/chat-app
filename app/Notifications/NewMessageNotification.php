<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Message $message;

    /**
     * Create a new notification instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message->load(['user', 'conversation']);
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $senderName = $this->message->user?->name ?? 'Someone';
        $conversationName = $this->message->conversation?->display_name ?? 'a conversation';

        return (new MailMessage)
            ->subject("New message from {$senderName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$senderName} sent you a message in {$conversationName}:")
            ->line("\"{$this->message->body}\"")
            ->action('View Message', url('/conversations/' . $this->message->conversation_id))
            ->line('Thank you for using ChatApp!');
    }

    /**
     * Get the array representation of the notification for database storage.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'conversation_name' => $this->message->conversation?->display_name,
            'sender_id' => $this->message->user_id,
            'sender_name' => $this->message->user?->name,
            'sender_avatar' => $this->message->user?->avatar_url,
            'body' => $this->message->body,
            'created_at' => $this->message->created_at->toISOString(),
        ];
    }
}
