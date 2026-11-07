@extends('layouts.app')
@section('title', 'Chat')

@section('content')
<div class="flex flex-col items-center justify-center h-[60vh] text-center">
    <div class="w-16 h-16 rounded-2xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center mb-5">
        <i class="bi bi-chat-dots text-3xl text-violet-500 dark:text-violet-400"></i>
    </div>
    <h2 class="text-xl font-semibold text-slate-800 dark:text-white mb-2">Your messages</h2>
    <p class="text-sm text-slate-500 dark:text-slate-400 mb-6 max-w-xs">
        Select a conversation from the sidebar, or start a new direct message.
    </p>
    <div x-data="{ open: false }" class="relative">
        <button @click="open = !open"
                class="btn-primary flex items-center gap-2">
            <i class="bi bi-pencil-square text-sm"></i> New message
        </button>
        <div x-show="open" x-cloak @click.outside="open = false"
             class="absolute left-1/2 -translate-x-1/2 mt-2 w-64 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-xl overflow-hidden z-50">
            <div class="px-3 py-2 border-b border-slate-100 dark:border-slate-800">
                <input type="text" placeholder="Search people…" x-model="dmSearch"
                       class="form-input text-sm py-1.5">
            </div>
            <div class="max-h-56 overflow-y-auto">
                @foreach($users as $u)
                <a href="{{ route('chat.direct', $u) }}"
                   class="flex items-center gap-3 px-3 py-2.5 hover:bg-violet-50 dark:hover:bg-violet-900/20 transition-colors"
                   x-show="!dmSearch || '{{ strtolower($u->name) }}'.includes(dmSearch.toLowerCase())">
                    <img src="{{ $u->avatar_url }}" class="w-8 h-8 rounded-full" alt="">
                    <span class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ $u->name }}</span>
                </a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
