<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Task;
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
                'participants:id,name,avatar',
                'latestMessage.user:id,name',
            ])
            ->latest('updated_at')
            ->get()
            ->each(fn ($c) => $c->unread_count = $c->unreadCountFor($user));

        $users = User::where('id', '!=', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'avatar', 'job_title']);

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
            ->with(['user:id,name,avatar', 'attachments', 'replies.user:id,name,avatar'])
            ->whereNull('parent_id')       // top-level only; replies nested under their parent
            ->withTrashed()
            ->latest('created_at')
            ->paginate(50);

        // Mark all as read
        $conversation->participants()
            ->updateExistingPivot($user->id, ['last_read_at' => now()]);

        $conversations = Conversation::whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
            ->with(['participants:id,name,avatar', 'latestMessage.user:id,name'])
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

    // Create a new group conversation
    public function createGroup(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:100',
            'member_ids' => 'required|array|min:2',
            'member_ids.*' => 'integer|exists:users,id',
        ]);

        $authUser = auth()->user();

        $conversation = Conversation::create([
            'type' => 'group',
            'name' => $validated['name'],
        ]);

        // Add creator + members
        $participantIds = array_unique(array_merge([$authUser->id], $validated['member_ids']));
        $conversation->participants()->attach($participantIds, ['last_read_at' => now()]);

        return response()->json($conversation->load('participants:id,name,avatar'));
    }

    // Brief task list for @ mention autocomplete
    public function tasksBrief(Request $request)
    {
        $user = auth()->user();

        $tasks = Task::whereHas('project', fn ($q) => $q->whereHas('members', fn ($q2) => $q2->where('users.id', $user->id)))
            ->with('project:id,name')
            ->when($request->q, fn ($q, $term) => $q->where('title', 'like', "%{$term}%"))
            ->latest('updated_at')
            ->limit(50)
            ->get(['id', 'title', 'project_id']);

        return response()->json($tasks->map(fn ($t) => [
            'id'           => $t->id,
            'title'        => $t->title,
            'project_name' => $t->project?->name,
        ]));
    }
}
