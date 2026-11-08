<script>
export default {
    name: 'OrgNode',
    props: { node: Object, depth: Number, expanded: Object },
    emits: ['toggle'],
}
</script>

<template>
    <li>
        <div class="flex items-center gap-2">
            <button v-if="node.children?.length"
                    type="button"
                    @click="$emit('toggle', node.id)"
                    class="w-5 h-5 flex items-center justify-center rounded text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800">
                <i class="bi bi-chevron-right text-[10px] transition-transform" :class="expanded[node.id] ? 'rotate-90' : ''"></i>
            </button>
            <span v-else class="w-5"></span>
            <a :href="node.show_url"
               class="flex items-center gap-3 pl-2 pr-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-violet-400 hover:bg-violet-50/40 dark:hover:bg-violet-900/10 transition-colors">
                <img :src="node.avatar_url || '/images/default-avatar.png'" class="w-8 h-8 rounded-full" :alt="node.name">
                <span>
                    <span class="block text-sm font-medium text-slate-900 dark:text-white">{{ node.name }}</span>
                    <span class="block text-[11px] text-slate-500">{{ [node.job_title, node.department].filter(Boolean).join(' · ') || '—' }}</span>
                </span>
                <span v-if="node.children?.length"
                      class="ml-2 text-[10px] px-1.5 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500">
                    {{ node.children.length }}
                </span>
            </a>
        </div>
        <ul v-if="node.children?.length && expanded[node.id] && depth < 12"
            class="ml-[1.9rem] pl-5 mt-2 space-y-2 border-l-2 border-slate-100 dark:border-slate-800">
            <OrgNode v-for="child in node.children" :key="child.id"
                     :node="child" :depth="depth+1" :expanded="expanded"
                     @toggle="$emit('toggle', $event)" />
        </ul>
    </li>
</template>
