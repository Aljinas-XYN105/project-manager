<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel for each conversation — only participants may subscribe
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    return \App\Models\Conversation::where('id', $conversationId)
        ->whereHas('participants', fn ($q) => $q->where('users.id', $user->id))
        ->exists();
});

// Presence channel for the chat hub — all authenticated users
Broadcast::channel('chat.presence', function ($user) {
    return ['id' => $user->id, 'name' => $user->name, 'avatar_url' => $user->avatar_url];
});
