<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Message $message)
    {
        $this->message->load(['user', 'attachments', 'conversation.participants']);
    }

    public function broadcastOn(): array
    {
        // Conversation channel (used by Chat/Show.vue for real-time updates)
        $channels = [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];

        // Also broadcast on each recipient's personal channel so AppLayout
        // can play a notification sound even when the chat window isn't open.
        $senderId = $this->message->user_id;
        $recipientIds = $this->message->conversation->participants()
            ->where('users.id', '!=', $senderId)
            ->pluck('users.id');

        foreach ($recipientIds as $id) {
            $channels[] = new PrivateChannel('App.Models.User.' . $id);
        }

        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'id'              => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'parent_id'       => $this->message->parent_id,
            'body'            => $this->message->body,
            'created_at'      => $this->message->created_at->toISOString(),
            'user' => [
                'id'         => $this->message->user->id,
                'name'       => $this->message->user->name,
                'avatar_url' => $this->message->user->avatar_url,
            ],
            'attachments' => $this->message->attachments->map(fn ($a) => [
                'id'            => $a->id,
                'url'           => $a->url,
                'original_name' => $a->original_name,
                'mime_type'     => $a->mime_type,
                'is_image'      => $a->isImage(),
            ]),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
