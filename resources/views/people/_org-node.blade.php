@php
    $children = in_array($person->id, $seen) ? collect() : ($byManager->get($person->id) ?? collect())->sortBy('name');
    $seen[] = $person->id;
@endphp
<li x-data="{ open: {{ $depth < 2 ? 'true' : 'false' }} }">
    <div class="flex items-center gap-2">
        @if($children->isNotEmpty())
        <button type="button" @click="open = !open" class="w-5 h-5 flex items-center justify-center rounded text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
            <i class="bi bi-chevron-right text-[10px] transition-transform" :class="open && 'rotate-90'"></i>
        </button>
        @else
        <span class="w-5"></span>
        @endif
        <a href="{{ route('users.show', $person) }}"
           class="flex items-center gap-3 pl-2 pr-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-violet-400 hover:bg-violet-50/40 dark:hover:bg-violet-900/10 transition-colors">
            <img src="{{ $person->avatar_url }}" class="w-8 h-8 rounded-full" alt="">
            <span>
                <span class="block text-sm font-medium text-slate-900 dark:text-white">{{ $person->name }}</span>
                <span class="block text-[11px] text-slate-500">{{ collect([$person->job_title, $person->department])->filter()->implode(' · ') ?: '—' }}</span>
            </span>
            @if($children->isNotEmpty())
            <span class="ml-2 text-[10px] px-1.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500">{{ $children->count() }}</span>
            @endif
        </a>
    </div>
    @if($children->isNotEmpty() && $depth < 12)
    <ul x-show="open" x-collapse class="ml-[1.9rem] pl-5 mt-2 space-y-2 border-l-2 border-slate-100 dark:border-slate-800">
        @foreach($children as $child)
            @include('people._org-node', ['person' => $child, 'depth' => $depth + 1, 'seen' => $seen])
        @endforeach
    </ul>
    @endif
</li>
