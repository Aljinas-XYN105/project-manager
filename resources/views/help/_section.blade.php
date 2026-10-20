<div class="space-y-2.5">
    @foreach($items as $item)
    <div x-data="{ open: false }"
         class="border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden">
        <button @click="open = !open"
                class="w-full flex items-center justify-between gap-3 px-4 py-3.5 text-left
                       bg-slate-50 dark:bg-slate-800/50 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors">
            <span class="font-medium text-sm text-slate-800 dark:text-slate-200">{{ $item['q'] }}</span>
            <i class="bi bi-chevron-down text-xs text-slate-400 flex-shrink-0 transition-transform duration-200"
               :class="open && 'rotate-180'"></i>
        </button>
        <div x-show="open" x-collapse x-cloak
             class="px-4 py-3.5 text-sm text-slate-600 dark:text-slate-400 leading-relaxed bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800">
            {{ $item['a'] }}
        </div>
    </div>
    @endforeach
</div>
