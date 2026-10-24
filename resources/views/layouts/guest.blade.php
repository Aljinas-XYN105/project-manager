<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Workzen') }} — {{ $title ?? 'Sign in' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .guest-left {
            background: linear-gradient(160deg, #1e1b4b 0%, #2d2572 50%, #1a1745 100%);
        }
        .guest-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 50% 40% at 90% 15%, rgba(139,92,246,0.22) 0%, transparent 65%),
                radial-gradient(ellipse 40% 50% at 10% 85%, rgba(99,102,241,0.18) 0%, transparent 65%);
            pointer-events: none;
        }
        .logo-filter { filter: brightness(0) invert(1); }
        .dot-grid-overlay {
            background-image: radial-gradient(circle, rgba(255,255,255,0.12) 1px, transparent 1px);
            background-size: 24px 24px;
        }
    </style>
</head>
<body class="font-sans antialiased min-h-screen flex bg-white">

{{-- ═══════════════════════════════════════════
     LEFT PANEL — Branding (desktop only)
════════════════════════════════════════════ --}}
<div class="hidden lg:flex lg:w-[45%] xl:w-[48%] relative overflow-hidden flex-col p-14"
     style="background: linear-gradient(160deg, #1e1b4b 0%, #2d2572 50%, #1a1745 100%);">

    {{-- Dot grid overlay --}}
    <div class="dot-grid-overlay absolute inset-0 opacity-50 pointer-events-none select-none"></div>

    {{-- Logo — blended directly, no card --}}
    <div class="relative z-10 mb-12">
        <img src="{{ asset('images/workzen-logo.png') }}" alt="Workzen"
             class="logo-filter h-12 w-auto" style="max-width: 200px;">
    </div>

    {{-- Hero content --}}
    <div class="relative z-10 flex-1 flex flex-col justify-center space-y-6">

        <div class="space-y-4">
            {{-- Badge --}}
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-[11px] font-semibold tracking-widest uppercase"
                  style="background:rgba(255,255,255,0.1); border:1px solid rgba(255,255,255,0.18); color:rgba(255,255,255,0.7);">
                <span class="w-1.5 h-1.5 rounded-full bg-violet-300 animate-pulse"></span>
                Work Smart. Stay in Sync.
            </span>

            {{-- Headline --}}
            <div>
                <h1 class="text-4xl xl:text-5xl font-black text-white leading-[1.1] tracking-tight">
                    Everything<br>
                    <span style="color: transparent; background: linear-gradient(135deg, #a78bfa, #818cf8, #c4b5fd); -webkit-background-clip: text; background-clip: text;">
                        your team
                    </span><br>
                    needs.
                </h1>
            </div>

            <p class="text-sm leading-relaxed" style="color: rgba(255,255,255,0.5); max-width: 280px;">
                Sprints, tasks, time tracking, standups, leave, and people — all in one workspace.
            </p>
        </div>

        {{-- Feature pills --}}
        <div class="flex flex-wrap gap-2">
            @foreach(['Kanban', 'Time Tracking', 'People & HR', 'Bug Tracker', 'Leave Mgmt', 'Reports'] as $feat)
            <span class="text-[11px] font-medium px-2.5 py-1 rounded-full"
                  style="background:rgba(255,255,255,0.07); border:1px solid rgba(255,255,255,0.13); color:rgba(255,255,255,0.6);">
                {{ $feat }}
            </span>
            @endforeach
        </div>
    </div>

    {{-- Footer --}}
    <p class="relative z-10 text-xs" style="color:rgba(255,255,255,0.25);">
        &copy; {{ date('Y') }} Workzen &middot; Built for teams
    </p>
</div>

{{-- ═══════════════════════════════════════════
     RIGHT PANEL — Form (white)
════════════════════════════════════════════ --}}
<div class="flex-1 flex flex-col bg-white">

    {{-- Mobile logo --}}
    <div class="lg:hidden flex items-center justify-center pt-10 pb-4">
        <img src="{{ asset('images/workzen-logo.png') }}" alt="Workzen" class="h-9 w-auto">
    </div>

    {{-- Form area --}}
    <div class="flex-1 flex items-center justify-center px-8 py-10 lg:px-14">
        <div class="w-full max-w-[400px]">
            {{ $slot }}
        </div>
    </div>

    {{-- Footer --}}
    <div class="pb-8 text-center">
        <p class="text-xs text-slate-400">
            &copy; {{ date('Y') }} Workzen &mdash; Secure connection
            <i class="bi bi-shield-check ml-1 text-emerald-500"></i>
        </p>
    </div>
</div>

</body>
</html>
