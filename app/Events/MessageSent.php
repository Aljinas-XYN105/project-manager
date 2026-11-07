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
        $this->message->load(['user', 'attachments']);
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conversation.' . $this->message->conversation_id),
        ];
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
