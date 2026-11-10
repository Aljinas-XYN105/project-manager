<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConversationController extends Controller
{
    // Chat hub: list all conversations the auth user is part of
    public function index()
    {
        $user = auth()->user();

        $conversations = Conversation::whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
            ->with([
                'participants:id,name,avatar,last_seen_at,availability',
                'latestMessage.user:id,name',
            ])
            ->get()
            ->each(fn ($c) => $c->unread_count = $c->unreadCountFor($user))
            ->sortByDesc(fn ($c) => $c->latestMessage?->created_at?->timestamp ?? 0)
            ->values();

        $users = User::where('id', '!=', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'avatar', 'job_title', 'last_seen_at', 'availability']);

        $authId = $user->id;

        return Inertia::render('Chat/Index', [
            'conversations' => $conversations->map(fn ($c) => [
                'id'            => $c->id,
                'is_group'      => $c->is_group,
                'name'          => $c->name,
                'unread_count'  => $c->unread_count,
                'participants'  => $c->participants->map(fn ($p) => [
                    'id'           => $p->id,
                    'name'         => $p->name,
                    'avatar_url'   => $p->avatar_url,
                    'availability' => $p->effective_availability,
                    'is_online'    => $p->effective_availability !== 'offline',
                ]),
                'latest_message'=> $c->latestMessage ? [
                    'body'      => $c->latestMessage->body,
                    'is_mine'   => $c->latestMessage->user_id === $authId,
                    'user'      => $c->latestMessage->user ? ['name' => $c->latestMessage->user->name] : null,
                    'time_label'=> $c->latestMessage->created_at->diffForHumans(null, true, true),
                ] : null,
                'show_url' => route('chat.show', $c),
            ]),
            'users' => $users->map(fn ($u) => [
                'id'           => $u->id,
                'name'         => $u->name,
                'avatar_url'   => $u->avatar_url,
                'job_title'    => $u->job_title,
                'availability' => $u->effective_availability,
                'is_online'    => $u->effective_availability !== 'offline',
                'direct_url'   => route('chat.direct', $u),
            ]),
            'popup_url'       => route('chat.popup'),
            'group_store_url' => route('chat.groups.store'),
        ]);
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
            ->with(['user:id,name,avatar,last_seen_at,availability', 'attachments', 'replies.user:id,name,avatar'])
            ->whereNull('parent_id')       // top-level only; replies nested under their parent
            ->withTrashed()
            ->latest('created_at')
            ->paginate(50);

        // Mark all as read
        $conversation->participants()
            ->updateExistingPivot($user->id, ['last_read_at' => now()]);

        // Also load participants with status for header
        $conversation->load('participants:id,name,avatar,last_seen_at,availability');

        $users = User::where('id', '!=', $user->id)->where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'avatar', 'job_title']);

        $authId = $user->id;
        $other  = $conversation->type === 'direct'
            ? $conversation->participants->firstWhere('id', '!=', $authId)
            : null;

        $statusLabel = '';
        if ($other) {
            if ($other->effective_availability !== 'offline') {
                $statusLabel = ucfirst($other->availability ?? 'Online');
            } elseif ($other->last_seen_at) {
                $statusLabel = 'Last seen ' . $other->last_seen_at->diffForHumans();
            } else {
                $statusLabel = 'Offline';
            }
        }

        return Inertia::render('Chat/Show', [
            'conversation' => [
                'id'           => $conversation->id,
                'is_group'     => $conversation->is_group,
                'name'         => $conversation->name,
                'participants' => $conversation->participants->map(fn ($p) => [
                    'id'           => $p->id,
                    'name'         => $p->name,
                    'avatar_url'   => $p->avatar_url,
                    'availability' => $p->effective_availability,
                    'is_online'    => $p->effective_availability !== 'offline',
                ]),
                'other'        => $other ? [
                    'id'           => $other->id,
                    'name'         => $other->name,
                    'avatar_url'   => $other->avatar_url,
                    'is_online'    => $other->effective_availability !== 'offline',
                    'status_label' => $statusLabel,
                ] : null,
                'member_count' => $conversation->participants->count(),
                'store_url'    => route('chat.messages.store', $conversation),
                'popup_url'    => route('chat.popup'),
            ],
            'messages' => $messages->through(fn ($m) => [
                'id'          => $m->id,
                'body'        => $m->body,
                'is_deleted'  => (bool) $m->deleted_at,
                'is_mine'     => $m->user_id === $authId,
                'user'        => $m->user ? ['id' => $m->user->id, 'name' => $m->user->name, 'avatar_url' => $m->user->avatar_url] : null,
                'created_at'  => $m->created_at->format('g:i A'),
                'created_date'=> $m->created_at->toDateString(),
                'destroy_url' => route('chat.messages.destroy', $m),
            ]),
            'users' => $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'avatar_url' => $u->avatar_url]),
            'back_url' => route('chat.index'),
        ]);
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

    // Open the entire chat hub in a standalone popup window
    public function popupHub(Request $request)
    {
        $user = auth()->user();

        $conversations = Conversation::whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
            ->with(['participants:id,name,avatar,last_seen_at,availability', 'latestMessage.user:id,name'])
            ->get()
            ->each(fn ($c) => $c->unread_count = $c->unreadCountFor($user))
            ->sortByDesc(fn ($c) => $c->latestMessage?->created_at?->timestamp ?? 0)
            ->values();

        $users = User::where('id', '!=', $user->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'avatar', 'job_title', 'last_seen_at', 'availability']);

        // Pre-open a specific conversation (e.g. from pop-out button on a show page)
        $openConvId = (int) $request->get('open', 0);
        $openConv   = $openConvId ? $conversations->firstWhere('id', $openConvId) : $conversations->first();

        $openConvSerialized = $openConv ? [
            'id'           => $openConv->id,
            'is_group'     => $openConv->is_group,
            'name'         => $openConv->name,
            'participants' => $openConv->participants->map(fn ($p) => [
                'id'           => $p->id,
                'name'         => $p->name,
                'avatar_url'   => $p->avatar_url,
                'availability' => $p->effective_availability,
            ]),
            'window_url' => route('chat.window', $openConv),
        ] : null;

        return Inertia::render('Chat/PopupHub', [
            'conversations' => $conversations->map(fn ($c) => [
                'id'           => $c->id,
                'is_group'     => $c->is_group,
                'name'         => $c->name,
                'unread_count' => $c->unread_count,
                'participants' => $c->participants->map(fn ($p) => [
                    'id'           => $p->id,
                    'name'         => $p->name,
                    'avatar_url'   => $p->avatar_url,
                    'availability' => $p->effective_availability,
                    'is_online'    => $p->is_online ?? false,
                ]),
                'latest_message' => $c->latestMessage ? [
                    'body'    => $c->latestMessage->body,
                    'is_mine' => $c->latestMessage->user_id === $user->id,
                    'sent_at' => $c->latestMessage->created_at->diffForHumans(null, true, true),
                ] : null,
                'window_url' => route('chat.window', $c),
            ]),
            'users' => $users->map(fn ($u) => [
                'id'         => $u->id,
                'name'       => $u->name,
                'avatar_url' => $u->avatar_url,
                'job_title'  => $u->job_title,
                'is_online'  => $u->is_online ?? false,
                'availability' => $u->effective_availability,
                'direct_url' => route('chat.direct', $u),
            ]),
            'open_conv'   => $openConvSerialized,
            'chat_index_url' => route('chat.index'),
            'groups_store_url' => route('chat.groups.store'),
        ]);
    }

    // Open chat in a standalone popup window (no app chrome)
    public function window(Conversation $conversation)
    {
        $user = auth()->user();

        abort_unless(
            $conversation->participants()->where('users.id', $user->id)->exists(),
            403, 'You are not a participant in this conversation.'
        );

        $messages = $conversation->messages()
            ->with(['user:id,name,avatar,last_seen_at,availability', 'attachments', 'replies.user:id,name,avatar'])
            ->whereNull('parent_id')
            ->withTrashed()
            ->latest('created_at')
            ->paginate(50);

        $conversation->participants()
            ->updateExistingPivot($user->id, ['last_read_at' => now()]);

        $conversation->load('participants:id,name,avatar,last_seen_at,availability');

        $users = User::where('id', '!=', $user->id)->where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'avatar', 'job_title']);

        return Inertia::render('Chat/Window', [
            'conversation' => [
                'id'           => $conversation->id,
                'is_group'     => $conversation->is_group,
                'name'         => $conversation->name,
                'participants' => $conversation->participants->map(fn ($p) => [
                    'id'           => $p->id,
                    'name'         => $p->name,
                    'avatar_url'   => $p->avatar_url,
                    'availability' => $p->effective_availability,
                    'is_online'    => $p->is_online ?? false,
                ]),
                'store_url' => route('chat.messages.store', $conversation),
            ],
            'messages' => $messages->through(fn ($m) => [
                'id'          => $m->id,
                'body'        => $m->body,
                'deleted_at'  => $m->deleted_at?->diffForHumans(),
                'user'        => $m->user ? ['id' => $m->user->id, 'name' => $m->user->name, 'avatar_url' => $m->user->avatar_url] : null,
                'created_at'  => $m->created_at->format('M j, g:i A'),
                'destroy_url' => route('chat.messages.destroy', $m),
            ]),
            'users' => $users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'avatar_url' => $u->avatar_url]),
        ]);
    }

    // Rename a group conversation
    public function rename(Request $request, Conversation $conversation)
    {
        abort_unless($conversation->type === 'group', 403);
        abort_unless($conversation->participants()->where('users.id', auth()->id())->exists(), 403);

        $validated = $request->validate(['name' => 'required|string|max:100']);
        $conversation->update(['name' => $validated['name']]);

        return response()->json(['name' => $conversation->name]);
    }

    // Add a member to a group conversation
    public function addMember(Request $request, Conversation $conversation)
    {
        abort_unless($conversation->type === 'group', 403);
        abort_unless($conversation->participants()->where('users.id', auth()->id())->exists(), 403);

        $validated = $request->validate(['user_id' => 'required|integer|exists:users,id']);

        if (!$conversation->participants()->where('users.id', $validated['user_id'])->exists()) {
            $conversation->participants()->attach($validated['user_id'], ['last_read_at' => now()]);
        }

        return response()->json($conversation->load('participants:id,name,avatar,last_seen_at,availability'));
    }

    // Remove a member from a group conversation
    public function removeMember(Conversation $conversation, User $user)
    {
        abort_unless($conversation->type === 'group', 403);
        abort_unless($conversation->participants()->where('users.id', auth()->id())->exists(), 403);
        abort_if($user->id === auth()->id(), 422, 'Cannot remove yourself.');

        $conversation->participants()->detach($user->id);

        return response()->json(['ok' => true]);
    }

    // Brief list for @ mention autocomplete — tasks, subtasks, members
    public function tasksBrief(Request $request)
    {
        $user = auth()->user();
        $q    = $request->q;

        $isAdmin = $user->can('tasks.view-all');

        // Top-level tasks
        $tasks = Task::whereNull('parent_id')
            ->when(!$isAdmin, fn ($q) => $q->whereHas('project', fn ($q) => $q->whereHas('members', fn ($q2) => $q2->where('users.id', $user->id))))
            ->with('project:id,name')
            ->when($q, fn ($query, $term) => $query->where('title', 'like', "%{$term}%"))
            ->latest('updated_at')
            ->limit(30)
            ->get(['id', 'title', 'project_id'])
            ->map(fn ($t) => [
                'kind'         => 'task',
                'id'           => $t->id,
                'title'        => $t->title,
                'project_name' => $t->project?->name,
            ]);

        // Subtasks
        $subtasks = Task::whereNotNull('parent_id')
            ->when(!$isAdmin, fn ($q) => $q->whereHas('project', fn ($q) => $q->whereHas('members', fn ($q2) => $q2->where('users.id', $user->id))))
            ->with('parent:id,title')
            ->when($q, fn ($query, $term) => $query->where('title', 'like', "%{$term}%"))
            ->latest('updated_at')
            ->limit(20)
            ->get(['id', 'title', 'project_id', 'parent_id'])
            ->map(fn ($t) => [
                'kind'        => 'subtask',
                'id'          => $t->id,
                'title'       => $t->title,
                'parent_name' => $t->parent?->title,
            ]);

        // Members (workspace users)
        $members = User::where('id', '!=', $user->id)
            ->where('is_active', true)
            ->when($q, fn ($query, $term) => $query->where('name', 'like', "%{$term}%"))
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'avatar', 'job_title'])
            ->map(fn ($u) => [
                'kind'      => 'member',
                'id'        => $u->id,
                'title'     => $u->name,
                'subtitle'  => $u->job_title,
                'avatar_url' => $u->avatar_url,
            ]);

        return response()->json([
            'tasks'    => $tasks,
            'subtasks' => $subtasks,
            'members'  => $members,
        ]);
    }
}
