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
<body class="font-sans antialiased min-h-screen flex">

{{-- Left panel: branding (hidden on mobile) --}}
<div class="hidden lg:flex lg:w-[48%] xl:w-[50%] relative overflow-hidden flex-col p-12"
     style="background: linear-gradient(135deg, #0b0d1f 0%, #13103a 55%, #0c0f28 100%)">

    {{-- Background blobs --}}
    <div class="absolute inset-0 pointer-events-none select-none">
        <div class="absolute -top-48 -left-32 w-[520px] h-[520px] bg-violet-700/30 rounded-full blur-3xl"></div>
        <div class="absolute top-1/2 -right-24 w-[380px] h-[380px] bg-indigo-600/20 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 left-1/3 w-[440px] h-[440px] bg-purple-800/25 rounded-full blur-3xl"></div>
        {{-- Dot grid --}}
        <svg class="absolute inset-0 w-full h-full opacity-[0.06]" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="dots" width="28" height="28" patternUnits="userSpaceOnUse">
                    <circle cx="1.5" cy="1.5" r="1.5" fill="white"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#dots)" />
        </svg>
        <div class="absolute inset-y-0 right-0 w-24 bg-gradient-to-r from-transparent to-black/20"></div>
    </div>

    {{-- Logo --}}
    <div class="relative z-10 mb-16">
        <div class="inline-flex items-center bg-white rounded-2xl px-5 py-3 shadow-xl"
             style="box-shadow: 0 4px 32px rgba(109,40,217,0.4), 0 1px 4px rgba(0,0,0,0.4);">
            <img src="{{ asset('images/workzen-logo.png') }}" alt="Workzen" class="h-10 w-auto">
        </div>
    </div>

    {{-- Hero --}}
    <div class="relative z-10 flex-1 flex flex-col justify-center space-y-10">
        <div class="space-y-5">
            <span class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium text-violet-300 border border-violet-500/30 bg-violet-500/10">
                <span class="w-1.5 h-1.5 rounded-full bg-violet-400 animate-pulse"></span>
                Work Smart. Stay in Sync.
            </span>
            <h1 class="text-4xl xl:text-5xl font-bold text-white leading-[1.1] tracking-tight">
                Everything your<br>
                <span class="text-transparent bg-clip-text bg-gradient-to-r from-violet-400 via-purple-300 to-indigo-400">team needs</span>
            </h1>
            <p class="text-slate-400 text-base leading-relaxed max-w-xs">
                Sprints, tasks, time tracking, daily standups, leave management — all in one place.
            </p>
        </div>

        {{-- Feature grid --}}
        <div class="grid grid-cols-2 gap-3">
            @foreach([
                ['bi-kanban', 'Kanban & Sprints'],
                ['bi-clock-history', 'Time Tracking'],
                ['bi-people-fill', 'People & HR'],
                ['bi-calendar2-check', 'Leave Management'],
                ['bi-bug', 'Bug Tracker'],
                ['bi-bar-chart-line', 'Reports'],
            ] as [$icon, $label])
            <div class="flex items-center gap-3 text-slate-300 text-sm font-medium">
                <div class="w-8 h-8 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center flex-shrink-0">
                    <i class="bi {{ $icon }} text-violet-400"></i>
                </div>
                {{ $label }}
            </div>
            @endforeach
        </div>
    </div>

    {{-- Footer --}}
    <p class="relative z-10 mt-12 text-slate-600 text-xs">&copy; {{ date('Y') }} Workzen. Built for teams.</p>
</div>

{{-- Right panel: form (white background) --}}
<div class="flex-1 flex flex-col bg-white">

    {{-- Mobile logo --}}
    <div class="lg:hidden flex items-center justify-center pt-10 pb-6">
        <img src="{{ asset('images/workzen-logo.png') }}" alt="Workzen" class="h-9 w-auto">
    </div>

    {{-- Centered form area --}}
    <div class="flex-1 flex flex-col items-center justify-center px-6 py-12 lg:px-16">
        <div class="w-full max-w-md">
            {{ $slot }}
        </div>
    </div>

    {{-- Footer --}}
    <div class="flex items-center justify-center pb-8">
        <p class="text-center text-xs text-slate-400">
            &copy; {{ date('Y') }} Workzen &mdash; Secure connection
            <span class="inline-flex items-center gap-1 ml-1 text-emerald-500">
                <i class="bi bi-shield-check"></i>
            </span>
        </p>
    </div>
</div>

</body>
</html>
