<script setup>
import { ref } from 'vue'
import { Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import OrgNode from './OrgNode.vue'

const props = defineProps({
    tree: Array,
    index_url: String,
})

const page = usePage()
const canManageUsers = page.props.auth?.user?.permissions?.includes('users.manage') || page.props.auth?.user?.roles?.includes('admin')

function buildExpanded(nodes, depth) {
    const state = {}
    for (const node of nodes ?? []) {
        state[node.id] = depth < 2
        Object.assign(state, buildExpanded(node.children, depth + 1))
    }
    return state
}
const expanded = ref(buildExpanded(props.tree, 0))

function toggle(id) { expanded.value[id] = !expanded.value[id] }

const hasAnyManager = props.tree.some(n => n.children?.length > 0)
</script>

<template>
    <AppLayout title="Org Chart">
        <div>
            <div class="page-header">
                <div>
                    <h1 class="page-title">Org chart</h1>
                    <p class="text-sm text-slate-500 dark:text-slate-400 mt-0.5">Built from each member's "Reports to" setting.</p>
                </div>
                <div class="tab-bar">
                    <Link :href="index_url" class="tab-item"><i class="bi bi-grid-3x3-gap"></i> Directory</Link>
                    <span class="tab-item active"><i class="bi bi-diagram-3"></i> Org chart</span>
                </div>
            </div>

            <div v-if="!hasAnyManager" class="alert-info mb-5">
                <i class="bi bi-info-circle-fill"></i>
                <span>No reporting lines are set yet.
                    <span v-if="canManageUsers">Set "Reports to" when <a :href="route('users.index')" class="underline">editing members</a>.</span>
                    <span v-else>Ask an admin to set "Reports to" for each member.</span>
                </span>
            </div>

            <div class="card p-6 overflow-x-auto">
                <ul class="space-y-2 min-w-max">
                    <OrgNode v-for="node in tree" :key="node.id"
                             :node="node" :depth="0" :expanded="expanded"
                             @toggle="toggle" />
                </ul>
            </div>
        </div>
    </AppLayout>
</template>
