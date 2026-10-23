<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Workzen') }} — {{ $title ?? 'Sign in' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased min-h-screen bg-[#0D0F14] text-slate-100 flex">

{{-- Left panel: branding (hidden on mobile) --}}
<div class="hidden lg:flex lg:w-[52%] xl:w-[55%] relative overflow-hidden flex-col justify-between p-12 bg-gradient-to-br from-[#0a0c1a] via-[#120d2e] to-[#0d1022]">

    {{-- Background decoration --}}
    <div class="absolute inset-0 pointer-events-none select-none">
        {{-- Primary glow blobs --}}
        <div class="absolute -top-40 -left-24 w-[500px] h-[500px] bg-violet-700/25 rounded-full blur-3xl"></div>
        <div class="absolute top-1/3 right-[-100px] w-[400px] h-[400px] bg-indigo-600/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-32 left-1/4 w-[420px] h-[420px] bg-purple-800/20 rounded-full blur-3xl"></div>
        <div class="absolute bottom-1/4 right-1/4 w-56 h-56 bg-violet-500/10 rounded-full blur-2xl"></div>
        {{-- Subtle grid --}}
        <svg class="absolute inset-0 w-full h-full opacity-[0.035]" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="grid" width="48" height="48" patternUnits="userSpaceOnUse">
                    <path d="M 48 0 L 0 0 0 48" fill="none" stroke="white" stroke-width="0.8"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid)" />
        </svg>
        {{-- Top border glow --}}
        <div class="absolute top-0 left-0 right-0 h-px bg-gradient-to-r from-transparent via-violet-500/40 to-transparent"></div>
    </div>

    {{-- Logo / wordmark --}}
    <div class="relative z-10">
        <div class="inline-block bg-white rounded-[20px] px-7 py-[18px] shadow-2xl shadow-violet-950/60"
             style="box-shadow: 0 8px 40px rgba(109,40,217,0.35), 0 2px 8px rgba(0,0,0,0.5);">
            <img src="{{ asset('images/workzen-logo.png') }}" alt="Workzen" class="h-16 w-auto">
        </div>
    </div>

    {{-- Hero copy --}}
    <div class="relative z-10 space-y-8">
        <div class="space-y-5">
            <div class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-violet-500/15 border border-violet-400/25 rounded-full text-violet-300 text-xs font-medium tracking-wide">
                <span class="w-1.5 h-1.5 bg-violet-400 rounded-full animate-pulse"></span>
                Work Smart. Stay in Sync.
            </div>
            <h1 class="text-4xl xl:text-[52px] font-bold text-white leading-[1.1] tracking-tight">
                Everything your<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-400 via-purple-400 to-indigo-400">team needs</span>
            </h1>
            <p class="text-slate-400 text-base leading-relaxed max-w-[340px]">
                Sprints, tasks, time tracking, daily standups, leave management, and people — all in one place.
            </p>
        </div>

        {{-- Feature bullets --}}
        <div class="grid grid-cols-2 gap-2.5">
            @foreach([
                ['bi-kanban', 'Kanban & Sprints'],
                ['bi-clock-history', 'Time Tracking'],
                ['bi-people-fill', 'People & HR'],
                ['bi-calendar2-check', 'Leave Management'],
                ['bi-bug', 'Bug Tracker'],
                ['bi-bar-chart-line', 'Reports'],
            ] as [$icon, $label])
            <div class="flex items-center gap-2.5 text-slate-300/80 text-sm">
                <div class="w-8 h-8 bg-violet-500/10 border border-violet-500/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <i class="bi {{ $icon }} text-violet-400 text-sm"></i>
                </div>
                {{ $label }}
            </div>
            @endforeach
        </div>
    </div>

    {{-- Footer --}}
    <div class="relative z-10 text-slate-600 text-xs">
        &copy; {{ date('Y') }} Workzen. Built for teams.
    </div>
</div>

{{-- Right panel: form --}}
<div class="flex-1 flex flex-col items-center justify-center px-6 py-12 lg:px-12 bg-[#0D0F14]">

    {{-- Mobile logo --}}
    <div class="lg:hidden flex items-center justify-center mb-10">
        <div class="bg-white rounded-2xl px-4 py-2 shadow-lg">
            <img src="{{ asset('images/workzen-logo.png') }}" alt="Workzen" class="h-8 w-auto">
        </div>
    </div>

    <div class="w-full max-w-md">
        {{ $slot }}
    </div>

    <p class="mt-8 text-center text-xs text-slate-600">
        &copy; {{ date('Y') }} Workzen &mdash; Secure connection
        <span class="inline-flex items-center gap-1 ml-1 text-emerald-600">
            <i class="bi bi-shield-check"></i>
        </span>
    </p>
</div>

</body>
</html>
