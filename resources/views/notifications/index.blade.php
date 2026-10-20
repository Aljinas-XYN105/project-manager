@extends('layouts.app')
@section('title', 'Notifications')
@section('content')

@php
$typeConfig = [
    'task_assigned'      => ['icon' => 'bi-person-plus-fill',      'bg' => 'bg-violet-100 dark:bg-violet-900/30',  'color' => 'text-violet-600 dark:text-violet-400'],
    'task_commented'     => ['icon' => 'bi-chat-left-text-fill',    'bg' => 'bg-sky-100 dark:bg-sky-900/30',        'color' => 'text-sky-600 dark:text-sky-400'],
    'task_status_changed'=> ['icon' => 'bi-arrow-right-circle-fill','bg' => 'bg-emerald-100 dark:bg-emerald-900/30','color' => 'text-emerald-600 dark:text-emerald-400'],
    'project_invite'     => ['icon' => 'bi-folder-plus-fill',       'bg' => 'bg-amber-100 dark:bg-amber-900/30',   'color' => 'text-amber-600 dark:text-amber-400'],
    'leave_requested'    => ['icon' => 'bi-calendar-minus-fill',    'bg' => 'bg-orange-100 dark:bg-orange-900/30', 'color' => 'text-orange-600 dark:text-orange-400'],
    'leave_reviewed'     => ['icon' => 'bi-calendar-check-fill',    'bg' => 'bg-teal-100 dark:bg-teal-900/30',    'color' => 'text-teal-600 dark:text-teal-400'],
    'announcement'       => ['icon' => 'bi-megaphone-fill',         'bg' => 'bg-rose-100 dark:bg-rose-900/30',    'color' => 'text-rose-600 dark:text-rose-400'],
    '_default'           => ['icon' => 'bi-bell-fill',              'bg' => 'bg-slate-100 dark:bg-slate-800',      'color' => 'text-slate-500 dark:text-slate-400'],
];
$unreadCount = auth()->user()->unreadNotifications()->count();
@endphp

<div class="max-w-2xl">
    <div class="page-header">
        <div>
            <h1 class="page-title flex items-center gap-2.5">
                <span class="w-9 h-9 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center">
                    <i class="bi bi-bell-fill text-violet-600 dark:text-violet-400"></i>
                </span>
                Notifications
                @if($unreadCount > 0)
                <span class="text-sm font-normal text-violet-500 ml-1">{{ $unreadCount }} unread</span>
                @endif
            </h1>
        </div>
        @if($unreadCount > 0)
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            <button type="submit" class="btn-secondary btn-sm">
                <i class="bi bi-check2-all"></i> Mark all read
            </button>
        </form>
        @endif
    </div>

    <div class="card overflow-hidden">
        @forelse($notifications as $n)
        @php
            $data   = $n->data;
            $type   = $data['type'] ?? '_default';
            $cfg    = $typeConfig[$type] ?? $typeConfig['_default'];
            $isRead = (bool) $n->read_at;
        @endphp
        <div class="flex items-start gap-4 px-5 py-4 border-b border-slate-50 dark:border-slate-800
                    hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors
                    {{ $isRead ? 'opacity-70' : '' }}"
             x-data>
            {{-- Icon --}}
            <div class="w-9 h-9 rounded-xl {{ $cfg['bg'] }} flex items-center justify-center flex-shrink-0 mt-0.5">
                <i class="bi {{ $cfg['icon'] }} {{ $cfg['color'] }} text-sm"></i>
            </div>

            {{-- Content --}}
            <div class="flex-1 min-w-0">
                <p class="text-sm {{ $isRead ? 'text-slate-500 dark:text-slate-400' : 'text-slate-900 dark:text-white font-medium' }} leading-snug">
                    {{ $data['message'] ?? 'New notification' }}
                </p>
                @if(!empty($data['task_title']))
                <p class="text-xs text-slate-400 mt-0.5 truncate">
                    <i class="bi bi-check-square text-[10px] mr-1"></i>{{ $data['task_title'] }}
                </p>
                @endif
                <p class="text-[11px] text-slate-400 mt-1">{{ $n->created_at->diffForHumans() }}</p>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-1.5 flex-shrink-0 mt-0.5">
                @if(!$isRead)
                <form method="POST" action="{{ route('notifications.read-all') }}" class="hidden" id="read-all-form">@csrf</form>
                <button onclick="
                    fetch('/notifications/{{ $n->id }}/read', {
                        method: 'PATCH',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                    }).then(() => this.closest('div[x-data]').classList.add('opacity-70'));
                    this.remove();"
                        class="text-[11px] text-violet-500 hover:text-violet-700 dark:hover:text-violet-300 font-medium whitespace-nowrap">
                    Mark read
                </button>
                @endif
                @if(!empty($data['url']))
                <a href="{{ $data['url'] }}" class="w-7 h-7 flex items-center justify-center rounded-lg
                           hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-400 hover:text-slate-700
                           dark:hover:text-slate-200 transition-colors" title="View">
                    <i class="bi bi-arrow-right text-sm"></i>
                </a>
                @endif
                <button
                    onclick="
                        fetch('/notifications/{{ $n->id }}', {
                            method: 'DELETE',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                        }).then(() => this.closest('div[x-data]').remove());"
                    class="w-7 h-7 flex items-center justify-center rounded-lg
                           hover:bg-rose-100 dark:hover:bg-rose-900/30 text-slate-300 dark:text-slate-600
                           hover:text-rose-500 transition-colors" title="Dismiss">
                    <i class="bi bi-x-lg text-xs"></i>
                </button>
            </div>

            {{-- Unread dot --}}
            @if(!$isRead)
            <div class="w-2 h-2 rounded-full bg-violet-500 flex-shrink-0 mt-2"></div>
            @endif
        </div>
        @empty
        <div class="py-20 text-center">
            <div class="w-16 h-16 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center mx-auto mb-4">
                <i class="bi bi-bell-slash text-3xl text-slate-300 dark:text-slate-600"></i>
            </div>
            <h3 class="text-base font-semibold text-slate-900 dark:text-white">You're all caught up!</h3>
            <p class="text-sm text-slate-400 mt-1">No notifications right now.</p>
        </div>
        @endforelse
    </div>

    @if($notifications->hasPages())
    <div class="mt-4">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
