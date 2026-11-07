<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessageController extends Controller
{
    public function store(Request $request, Conversation $conversation)
    {
        $user = auth()->user();

        abort_unless(
            $conversation->participants()->where('users.id', $user->id)->exists(),
            403
        );

        $request->validate([
            'body'        => 'required_without:attachments|nullable|string|max:4000',
            'parent_id'   => 'nullable|integer|exists:messages,id',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:20480',
        ]);

        $message = $conversation->messages()->create([
            'user_id'   => $user->id,
            'parent_id' => $request->parent_id,
            'body'      => $request->body ?? '',
        ]);

        // Handle file uploads
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('chat-attachments/' . $conversation->id, 'public');
                $message->attachments()->create([
                    'disk'          => 'public',
                    'path'          => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type'     => $file->getMimeType(),
                    'size'          => $file->getSize(),
                ]);
            }
        }

        $conversation->touch();

        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'id'              => $message->id,
            'conversation_id' => $message->conversation_id,
            'parent_id'       => $message->parent_id,
            'body'            => $message->body,
            'created_at'      => $message->created_at->toISOString(),
            'user' => [
                'id'         => $user->id,
                'name'       => $user->name,
                'avatar_url' => $user->avatar_url,
            ],
            'attachments' => $message->attachments->map(fn ($a) => [
                'id'            => $a->id,
                'url'           => $a->url,
                'original_name' => $a->original_name,
                'mime_type'     => $a->mime_type,
                'is_image'      => $a->isImage(),
            ]),
        ]);
    }

    public function destroy(Message $message)
    {
        $user = auth()->user();

        abort_unless(
            $message->user_id === $user->id || $user->hasAnyRole(['admin', 'manager']),
            403
        );

        $message->delete();

        broadcast(new \App\Events\MessageDeleted($message->id, $message->conversation_id))->toOthers();

        return response()->json(['ok' => true]);
    }
}
