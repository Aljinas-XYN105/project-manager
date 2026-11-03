@extends('layouts.app')
@section('title', $project->name)

@section('content')
<div class="page-header">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center flex-shrink-0"
             style="background-color: {{ $project->color }}25;">
            <div class="w-4 h-4 rounded-full" style="background-color: {{ $project->color }};"></div>
        </div>
        <div>
            <h1 class="page-title">{{ $project->name }}</h1>
            <div class="flex items-center gap-2 mt-1">
                <span class="badge-{{ $project->status }}">{{ ucfirst(str_replace('_',' ',$project->status)) }}</span>
                <span class="badge-{{ $project->priority }}">{{ ucfirst($project->priority) }}</span>
                @if($project->is_overdue)
                <span class="badge bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">
                    <i class="bi bi-exclamation-triangle-fill mr-1"></i>Overdue
                </span>
                @endif
            </div>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route('projects.notes', $project) }}" class="btn-ghost btn-sm">
            <i class="bi bi-journal-text"></i> Notes
        </a>
        <a href="{{ route('projects.sprints.index', $project) }}" class="btn-ghost btn-sm">
            <i class="bi bi-speedometer2"></i> Sprints
        </a>
        <a href="{{ route('projects.tasks.kanban', $project) }}" class="btn-secondary btn-sm">
            <i class="bi bi-kanban"></i> Board
        </a>
        <a href="{{ route('projects.tasks.create', $project) }}" class="btn-primary btn-sm">
            <i class="bi bi-plus-lg"></i> Add Task
        </a>
        @can('update', $project)
        <a href="{{ route('projects.edit', $project) }}" class="btn-ghost btn-sm">
            <i class="bi bi-pencil"></i>
        </a>
        @endcan
    </div>
</div>

{{-- Stats --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="stat-card">
        <span class="text-xs text-slate-500 font-medium">Total Tasks</span>
        <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total_tasks'] }}</span>
    </div>
    <div class="stat-card">
        <span class="text-xs text-slate-500 font-medium">Completed</span>
        <span class="text-2xl font-bold text-green-600">{{ $stats['done'] }}</span>
    </div>
    <div class="stat-card">
        <span class="text-xs text-slate-500 font-medium">In Progress</span>
        <span class="text-2xl font-bold text-blue-600">{{ $stats['in_progress'] }}</span>
    </div>
    <div class="stat-card">
        <span class="text-xs text-slate-500 font-medium">Hours Logged</span>
        <span class="text-2xl font-bold text-slate-900 dark:text-white">{{ $stats['total_hours'] }}h</span>
    </div>
</div>

{{-- Progress --}}
<div class="card p-5 mb-6">
    <div class="flex items-center justify-between mb-2">
        <span class="text-sm font-semibold text-slate-900 dark:text-white">Overall Progress</span>
        <span class="text-lg font-bold text-violet-600">{{ $stats['progress'] }}%</span>
    </div>
    <div class="h-3 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
        <div class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 rounded-full transition-all duration-700"
             style="width: {{ $stats['progress'] }}%"></div>
    </div>
    <div class="flex justify-between text-xs text-slate-400 mt-2">
        <span>{{ $stats['done'] }} of {{ $stats['total_tasks'] }} tasks done</span>
        @if($project->due_date)
        <span class="{{ $project->is_overdue ? 'text-red-500' : '' }}">
            Due {{ $project->due_date->format('M j, Y') }}
        </span>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
    {{-- Task Status Breakdown --}}
    <div class="xl:col-span-2">
        <div class="card overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
                <h3 class="font-semibold text-slate-900 dark:text-white">Tasks by Status</h3>
                <a href="{{ route('projects.tasks.index', $project) }}" class="text-xs text-violet-500 hover:underline">View all</a>
            </div>
            <div class="p-5 space-y-3">
                @foreach([
                    ['label'=>'To Do','key'=>'todo','color'=>'bg-slate-400'],
                    ['label'=>'In Progress','key'=>'in_progress','color'=>'bg-blue-500'],
                    ['label'=>'In Review','key'=>'in_review','color'=>'bg-yellow-500'],
                    ['label'=>'Done','key'=>'done','color'=>'bg-green-500'],
                ] as $row)
                @php $count = $stats[$row['key']]; $pct = $stats['total_tasks'] > 0 ? round($count/$stats['total_tasks']*100) : 0; @endphp
                <div>
                    <div class="flex justify-between text-sm mb-1">
                        <span class="text-slate-700 dark:text-slate-300">{{ $row['label'] }}</span>
                        <span class="font-medium text-slate-900 dark:text-white">{{ $count }} <span class="text-slate-400 font-normal">({{ $pct }}%)</span></span>
                    </div>
                    <div class="h-2 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden">
                        <div class="h-full {{ $row['color'] }} rounded-full transition-all duration-500" style="width:{{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Members --}}
    <div class="card overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between">
            <h3 class="font-semibold text-slate-900 dark:text-white">Members</h3>
            @can('manageMember', $project)
            <button x-data @click="$dispatch('open-modal', 'add-member')" class="text-xs text-violet-500 hover:underline">+ Add</button>
            @endcan
        </div>
        <div class="divide-y divide-slate-50 dark:divide-slate-700">
            @foreach($project->members as $member)
            <div class="flex items-center gap-3 px-5 py-3">
                <img src="{{ $member->avatar_url }}" class="w-8 h-8 rounded-full">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-900 dark:text-white truncate">{{ $member->name }}</p>
                    <p class="text-xs text-slate-400">{{ ucfirst($member->pivot->role) }}</p>
                </div>
                @can('manageMember', $project)
                @if($member->id !== $project->owner_id)
                <form method="POST" action="{{ route('projects.members.remove', [$project, $member]) }}">
                    @csrf @method('DELETE')
                    <button class="text-slate-300 hover:text-red-500 transition-colors">
                        <i class="bi bi-x-lg text-xs"></i>
                    </button>
                </form>
                @endif
                @endcan
            </div>
            @endforeach
        </div>
    </div>
</div>

{{-- Project Attachments --}}
@php
$projectAttachmentsJson = $project->attachments->map(fn($a) => [
    'id'             => $a->id,
    'original_name'  => $a->original_name,
    'mime_type'      => $a->mime_type,
    'size_formatted' => $a->size_formatted,
    'is_image'       => $a->is_image,
    'is_video'       => $a->is_video,
    'url'            => $a->url,
    'uploader_name'  => $a->user->name,
    'created_ago'    => $a->created_at->diffForHumans(),
]);
@endphp

<div class="card overflow-hidden mt-6"
     x-data="attachmentManager('{{ route('attachments.project.store', $project) }}', '{{ route('attachments.destroy', ':id') }}')">
    <div class="px-5 py-3.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
        <h3 class="font-semibold text-slate-900 dark:text-white flex items-center gap-2">
            <i class="bi bi-paperclip text-slate-400 text-sm"></i>
            Project Files & Attachments
            <span class="text-xs font-normal text-slate-400" x-text="'(' + files.length + ')'"></span>
        </h3>
        <label class="btn-ghost btn-sm cursor-pointer">
            <i class="bi bi-plus-lg text-sm"></i> Upload
            <input type="file" class="sr-only" multiple @change="upload($event.target.files)">
        </label>
    </div>

    {{-- Drop zone --}}
    <div class="px-5 py-3 border-b border-dashed border-slate-200 dark:border-slate-700 transition-colors"
         :class="dragging ? 'bg-violet-50 dark:bg-violet-900/10 border-violet-400' : ''"
         @dragover.prevent="dragging = true"
         @dragleave.prevent="dragging = false"
         @drop.prevent="dragging = false; upload($event.dataTransfer.files)">
        <div class="flex items-center justify-center gap-2 py-2 text-center">
            <i class="bi bi-cloud-upload text-slate-300 dark:text-slate-600 text-xl" :class="dragging ? 'text-violet-400' : ''"></i>
            <p class="text-xs text-slate-400" :class="dragging ? 'text-violet-500' : ''">
                Drop files here or <label class="text-violet-500 hover:text-violet-600 cursor-pointer font-medium">
                    browse<input type="file" class="sr-only" multiple @change="upload($event.target.files)">
                </label>
                <span class="text-slate-300 dark:text-slate-600"> · images, PDFs, videos, docs · max 100 MB</span>
            </p>
        </div>
    </div>

    {{-- Upload progress --}}
    <template x-if="uploading">
        <div class="px-5 py-3 flex items-center gap-3 border-b border-slate-50 dark:border-slate-800 bg-violet-50/50 dark:bg-violet-900/10">
            <div class="w-8 h-8 rounded-xl bg-violet-100 dark:bg-violet-900/30 flex items-center justify-center flex-shrink-0">
                <i class="bi bi-arrow-up text-violet-500 text-sm animate-pulse"></i>
            </div>
            <div class="flex-1">
                <p class="text-xs font-medium text-violet-700 dark:text-violet-300" x-text="uploadStatus"></p>
                <div class="w-full bg-slate-200 dark:bg-slate-700 rounded-full h-1 mt-1.5">
                    <div class="bg-violet-500 h-1 rounded-full transition-all duration-300"
                         :style="'width: ' + uploadProgress + '%'"></div>
                </div>
            </div>
        </div>
    </template>

    {{-- File grid --}}
    <div class="p-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3">
        <template x-if="files.length === 0 && !uploading">
            <div class="col-span-full py-8 text-center">
                <i class="bi bi-cloud-upload text-3xl text-slate-300 dark:text-slate-600 block mb-2"></i>
                <p class="text-sm text-slate-400">No project files yet — drag and drop or click Upload</p>
            </div>
        </template>
        <template x-for="att in files" :key="att.id">
            <div class="group relative rounded-xl border border-slate-200 dark:border-slate-700 overflow-hidden bg-slate-50 dark:bg-slate-800/50 hover:border-violet-300 dark:hover:border-violet-700 transition-all">
                {{-- Preview area --}}
                <div class="h-28 flex items-center justify-center bg-slate-100 dark:bg-slate-800">
                    <template x-if="att.is_image">
                        <img :src="att.url" :alt="att.original_name" class="h-full w-full object-cover">
                    </template>
                    <template x-if="att.is_video">
                        <div class="flex flex-col items-center gap-1 text-violet-500">
                            <i class="bi bi-play-circle-fill text-3xl"></i>
                            <span class="text-[10px] font-medium text-slate-400">Video</span>
                        </div>
                    </template>
                    <template x-if="!att.is_image && !att.is_video">
                        <div class="flex flex-col items-center gap-1">
                            <i class="text-3xl"
                               :class="att.mime_type && att.mime_type.includes('pdf')
                                   ? 'bi bi-file-earmark-pdf text-rose-400'
                                   : att.mime_type && att.mime_type.includes('word')
                                   ? 'bi bi-file-earmark-word text-blue-400'
                                   : att.mime_type && (att.mime_type.includes('sheet')||att.mime_type.includes('excel'))
                                   ? 'bi bi-file-earmark-excel text-emerald-400'
                                   : att.mime_type && att.mime_type.includes('zip')
                                   ? 'bi bi-file-earmark-zip text-amber-400'
                                   : 'bi bi-file-earmark text-slate-400'"></i>
                        </div>
                    </template>
                </div>
                {{-- Info --}}
                <div class="px-3 py-2">
                    <p class="text-xs font-medium text-slate-900 dark:text-white truncate" x-text="att.original_name"></p>
                    <p class="text-[10px] text-slate-400 mt-0.5">
                        <span x-text="att.size_formatted"></span> · <span x-text="att.uploader_name"></span>
                    </p>
                </div>
                {{-- Hover actions --}}
                <div class="absolute top-2 right-2 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                    <a :href="att.url" target="_blank"
                       class="w-7 h-7 flex items-center justify-center rounded-lg bg-white/90 dark:bg-slate-900/90 shadow text-slate-600 hover:text-violet-600">
                        <i class="bi bi-download text-xs"></i>
                    </a>
                    <button @click="remove(att.id)"
                            class="w-7 h-7 flex items-center justify-center rounded-lg bg-white/90 dark:bg-slate-900/90 shadow text-slate-400 hover:text-rose-500">
                        <i class="bi bi-trash text-xs"></i>
                    </button>
                </div>
            </div>
        </template>
    </div>
</div>

@push('scripts')
@php
$projectAttachmentsJson = $project->attachments->map(fn($a) => [
    'id'             => $a->id,
    'original_name'  => $a->original_name,
    'mime_type'      => $a->mime_type,
    'size_formatted' => $a->size_formatted,
    'is_image'       => $a->is_image,
    'is_video'       => $a->is_video,
    'url'            => $a->url,
    'uploader_name'  => $a->user->name,
    'created_ago'    => $a->created_at->diffForHumans(),
]);
@endphp
<script>
function attachmentManager(uploadUrl, destroyUrlTemplate) {
    return {
        files: @json($projectAttachmentsJson),
        uploading: false,
        uploadProgress: 0,
        uploadStatus: '',
        dragging: false,

        async upload(fileList) {
            if (!fileList || !fileList.length) return;
            this.uploading = true;
            this.uploadProgress = 0;

            for (let i = 0; i < fileList.length; i++) {
                const file = fileList[i];
                this.uploadStatus = `Uploading ${file.name}… (${i + 1}/${fileList.length})`;

                const form = new FormData();
                form.append('file', file);
                form.append('_token', document.querySelector('meta[name=csrf-token]').content);

                try {
                    const res = await new Promise((resolve, reject) => {
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', uploadUrl);
                        xhr.upload.onprogress = e => {
                            if (e.lengthComputable)
                                this.uploadProgress = Math.round((e.loaded / e.total) * 100);
                        };
                        xhr.onload  = () => xhr.status >= 200 && xhr.status < 300
                            ? resolve(JSON.parse(xhr.responseText))
                            : reject(new Error(xhr.responseText));
                        xhr.onerror = () => reject(new Error('Network error'));
                        xhr.send(form);
                    });

                    const att = res.attachment;
                    this.files.push({
                        id:             att.id,
                        original_name:  att.original_name,
                        mime_type:      att.mime_type,
                        size_formatted: att.size_formatted,
                        is_image:       att.is_image,
                        is_video:       att.is_video,
                        url:            res.url,
                        uploader_name:  att.user?.name ?? 'You',
                        created_ago:    'just now',
                    });
                    window.toast(`${file.name} uploaded`, 'success');
                } catch {
                    window.toast(`Failed to upload ${file.name}`, 'error');
                }
            }
            this.uploading = false;
            this.uploadProgress = 0;
        },

        async remove(id) {
            if (!confirm('Delete this attachment?')) return;
            const url   = destroyUrlTemplate.replace(':id', id);
            const token = document.querySelector('meta[name=csrf-token]').content;
            try {
                const res = await fetch(url, {
                    method: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                });
                if (res.ok) {
                    this.files = this.files.filter(f => f.id !== id);
                    window.toast('File deleted', 'success');
                }
            } catch {
                window.toast('Could not delete file', 'error');
            }
        },
    };
}
</script>
@endpush
@endsection
