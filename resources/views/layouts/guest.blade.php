<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ProManage') }} — {{ $title ?? 'Sign in' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased min-h-screen bg-[#0D0F14] text-slate-100 flex">

{{-- Left panel: branding (hidden on mobile) --}}
<div class="hidden lg:flex lg:w-[52%] xl:w-[55%] relative overflow-hidden flex-col justify-between p-12 bg-gradient-to-br from-slate-900 via-violet-950 to-slate-900">

    {{-- Background decoration --}}
    <div class="absolute inset-0 pointer-events-none select-none">
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-violet-600/20 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 right-0 w-80 h-80 bg-violet-700/15 rounded-full blur-3xl translate-x-1/2 -translate-y-1/2"></div>
        <div class="absolute -bottom-24 left-1/3 w-72 h-72 bg-indigo-700/20 rounded-full blur-3xl"></div>
        {{-- Grid lines --}}
        <svg class="absolute inset-0 w-full h-full opacity-[0.04]" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse">
                    <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="1"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid)" />
        </svg>
    </div>

    {{-- Logo / wordmark --}}
    <div class="relative z-10 flex items-center gap-3">
        <div class="w-9 h-9 bg-violet-600 rounded-xl flex items-center justify-center shadow-lg shadow-violet-900/50">
            <i class="bi bi-layers-half text-white text-lg"></i>
        </div>
        <span class="text-xl font-bold text-white tracking-tight">ProManage</span>
    </div>

    {{-- Hero copy --}}
    <div class="relative z-10 space-y-8">
        <div class="space-y-4">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 bg-violet-600/20 border border-violet-500/30 rounded-full text-violet-300 text-xs font-medium">
                <span class="w-1.5 h-1.5 bg-violet-400 rounded-full animate-pulse"></span>
                Project Management &amp; HR Platform
            </div>
            <h1 class="text-4xl xl:text-5xl font-bold text-white leading-tight">
                Everything your<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-400 to-indigo-400">team needs</span>
            </h1>
            <p class="text-slate-400 text-lg leading-relaxed max-w-sm">
                Sprints, tasks, time tracking, daily standups, leave management, and people — all in one place.
            </p>
        </div>

        {{-- Feature bullets --}}
        <div class="grid grid-cols-2 gap-3">
            @foreach([
                ['bi-kanban', 'Kanban & Sprints'],
                ['bi-clock-history', 'Time Tracking'],
                ['bi-people-fill', 'People & HR'],
                ['bi-calendar2-check', 'Leave Management'],
                ['bi-bug', 'Bug Tracker'],
                ['bi-bar-chart-line', 'Reports'],
            ] as [$icon, $label])
            <div class="flex items-center gap-2.5 text-slate-300 text-sm">
                <div class="w-7 h-7 bg-slate-800/60 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="bi {{ $icon }} text-violet-400 text-sm"></i>
                </div>
                {{ $label }}
            </div>
            @endforeach
        </div>
    </div>

    {{-- Footer --}}
    <div class="relative z-10 text-slate-600 text-sm">
        &copy; {{ date('Y') }} ProManage. Built for teams.
    </div>
</div>

{{-- Right panel: form --}}
<div class="flex-1 flex flex-col items-center justify-center px-6 py-12 lg:px-12 bg-[#0D0F14]">

    {{-- Mobile logo --}}
    <div class="lg:hidden flex items-center gap-2 mb-10">
        <div class="w-8 h-8 bg-violet-600 rounded-xl flex items-center justify-center">
            <i class="bi bi-layers-half text-white"></i>
        </div>
        <span class="text-lg font-bold text-white">ProManage</span>
    </div>

    <div class="w-full max-w-md">
        {{ $slot }}
    </div>

    <p class="mt-8 text-center text-xs text-slate-600">
        &copy; {{ date('Y') }} ProManage &mdash; Secure connection
        <span class="inline-flex items-center gap-1 ml-1 text-emerald-600">
            <i class="bi bi-shield-check"></i>
        </span>
    </p>
</div>

</body>
</html>
