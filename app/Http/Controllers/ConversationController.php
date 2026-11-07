<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    // Chat hub: list all conversations the auth user is part of
    public function index()
    {
        $user = auth()->user();

        $conversations = Conversation::whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
            ->with([
                'participants:id,name,avatar_url',
                'latestMessage.user:id,name',
            ])
            ->withCount(['messages as unread_count' => function ($q) use ($user) {
                $pivot = \DB::table('conversation_participants')
                    ->where('user_id', $user->id)
                    ->pluck('last_read_at', 'conversation_id');

                $q->where('user_id', '!=', $user->id)
                  ->whereRaw('messages.id NOT IN (SELECT message_id FROM message_reads WHERE user_id = ?)', [$user->id]);
            }])
            ->latest('updated_at')
            ->get();

        $users = User::where('id', '!=', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'avatar_url']);

        return view('chat.index', compact('conversations', 'users'));
    }

    // Show a conversation and mark it as read
    public function show(Conversation $conversation)
    {
        $user = auth()->user();

        abort_unless(
            $conversation->participants()->where('users.id', $user->id)->exists(),
            403, 'You are not a participant in this conversation.'
        );

        $messages = $conversation->messages()
            ->with(['user:id,name,avatar_url', 'attachments', 'replies.user:id,name,avatar_url'])
            ->whereNull('parent_id')       // top-level only; replies nested under their parent
            ->withTrashed()
            ->latest('created_at')
            ->paginate(50);

        // Mark all as read
        $conversation->participants()
            ->updateExistingPivot($user->id, ['last_read_at' => now()]);

        $conversations = Conversation::whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
            ->with(['participants:id,name,avatar_url', 'latestMessage.user:id,name'])
            ->latest('updated_at')
            ->get();

        return view('chat.show', compact('conversation', 'messages', 'conversations'));
    }

    // Start or open a DM with another user
    public function direct(User $user)
    {
        $conversation = Conversation::directBetween(auth()->user(), $user);
        return redirect()->route('chat.show', $conversation);
    }
}
