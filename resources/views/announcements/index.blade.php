@extends('layouts.app')
@section('title', 'Announcements')

@section('content')
@php $categories = \App\Models\Announcement::CATEGORIES; @endphp
<div class="max-w-3xl mx-auto"
     x-data="{ modal: {{ $errors->any() ? 'true' : 'false' }}, action: @js(route('announcements.store')), method: 'POST',
               form: { title: @js(old('title', '')), body: @js(old('body', '')), category: @js(old('category', 'general')), expires_on: @js(old('expires_on', '')), is_pinned: {{ old('is_pinned') ? 'true' : 'false' }} },
               openNew() { this.action = @js(route('announcements.store')); this.method = 'POST'; this.form = { title: '', body: '', category: 'general', expires_on: '', is_pinned: false }; this.modal = true; },
               openEdit(a, url) { this.action = url; this.method = 'PUT'; this.form = a; this.modal = true; } }">

    <div class="page-header">
        <div>
            <h1 class="page-title">Announcements</h1>
            <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Company news, policies and events.</p>
        </div>
        @if($canManage)
        <div class="flex gap-2">
            <a href="{{ route('announcements.index', $showExpired ? [] : ['expired' => 1]) }}" class="btn-ghost btn-sm">{{ $showExpired ? 'Hide expired' : 'Show expired' }}</a>
            <button @click="openNew()" class="btn-primary"><i class="bi bi-megaphone"></i> New announcement</button>
        </div>
        @endif
    </div>

    <div class="space-y-4">
        @forelse($announcements as $a)
        @php $style = $a->style; $expired = $a->expires_on && $a->expires_on->lt(today()); @endphp
        <article id="announcement-{{ $a->id }}" class="card p-5 {{ $a->is_pinned ? 'ring-1 ring-violet-300 dark:ring-violet-800' : '' }} {{ $expired ? 'opacity-60' : '' }}">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-{{ $style['color'] }}-100 dark:bg-{{ $style['color'] }}-900/30 flex items-center justify-center flex-shrink-0">
                    <i class="bi {{ $style['icon'] }} text-{{ $style['color'] }}-600 dark:text-{{ $style['color'] }}-400"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="font-semibold text-slate-900 dark:text-white">{{ $a->title }}</h2>
                        @if($a->is_pinned)<span class="text-[10px] px-1.5 py-0.5 rounded bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-300"><i class="bi bi-pin-angle-fill"></i> Pinned</span>@endif
                        <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500">{{ $style['label'] }}</span>
                        @if($expired)<span class="text-[10px] text-slate-400">expired</span>@endif
                    </div>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $a->author->name }} · {{ $a->created_at->diffForHumans() }}@if($a->expires_on && ! $expired) · until {{ $a->expires_on->format('M j') }}@endif</p>
                    <div class="mt-3 text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line">{{ $a->body }}</div>
                </div>
                @if($canManage)
                <div class="flex flex-shrink-0">
                    <button type="button" class="btn-ghost btn-sm"
                            @click="openEdit({{ Js::from(['title' => $a->title, 'body' => $a->body, 'category' => $a->category, 'expires_on' => $a->expires_on?->toDateString() ?? '', 'is_pinned' => $a->is_pinned]) }}, @js(route('announcements.update', $a)))">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <form method="POST" action="{{ route('announcements.destroy', $a) }}" onsubmit="return confirm('Delete this announcement?')">
                        @csrf @method('DELETE')
                        <button class="btn-ghost btn-sm text-rose-500"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
                @endif
            </div>
        </article>
        @empty
        <div class="card empty-state py-16">
            <i class="bi bi-megaphone text-4xl text-slate-300 dark:text-slate-600"></i>
            <p class="text-sm text-slate-500 mt-3">No announcements right now.</p>
        </div>
        @endforelse
    </div>

    @if($announcements->hasPages())
    <div class="mt-4">{{ $announcements->links() }}</div>
    @endif

    @if($canManage)
    <div x-show="modal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/50" @keydown.escape.window="modal = false">
        <form method="POST" :action="action" @click.away="modal = false" class="card w-full max-w-lg p-6 space-y-4">
            @csrf
            <input type="hidden" name="_method" :value="method">
            <h3 class="font-semibold text-slate-900 dark:text-white" x-text="method === 'PUT' ? 'Edit announcement' : 'New announcement'"></h3>
            @if($errors->any())
            <div class="alert-danger text-sm"><i class="bi bi-exclamation-triangle-fill"></i><span>{{ $errors->first() }}</span></div>
            @endif
            <div>
                <label class="form-label">Title</label>
                <input name="title" x-model="form.title" required maxlength="160" class="form-input">
            </div>
            <div>
                <label class="form-label">Message</label>
                <textarea name="body" x-model="form.body" rows="6" required maxlength="5000" class="form-input resize-y"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Category</label>
                    <select name="category" x-model="form.category" class="form-select">
                        @foreach($categories as $k => $c)<option value="{{ $k }}">{{ $c['label'] }}</option>@endforeach
                    </select>
                </div>
                <div>
                    <label class="form-label">Show until <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input type="date" name="expires_on" x-model="form.expires_on" min="{{ today()->toDateString() }}" class="form-input">
                </div>
            </div>
            <div class="flex flex-wrap gap-4 text-sm text-slate-700 dark:text-slate-300">
                <label class="flex items-center gap-2"><input type="checkbox" name="is_pinned" value="1" x-model="form.is_pinned" class="rounded text-violet-600"> Pin to top</label>
                <label class="flex items-center gap-2" x-show="method === 'POST'"><input type="checkbox" name="notify" value="1" checked class="rounded text-violet-600"> Notify everyone</label>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" @click="modal = false" class="btn-ghost">Cancel</button>
                <button class="btn-primary"><i class="bi bi-send"></i> <span x-text="method === 'PUT' ? 'Save' : 'Publish'"></span></button>
            </div>
        </form>
    </div>
    @endif
</div>
@endsection
