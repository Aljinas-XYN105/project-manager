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
<div class="flex-1 flex flex-col bg-white min-h-screen lg:min-h-0">

    {{-- Mobile: full-page layout --}}
    <div class="lg:hidden flex flex-col min-h-screen">

        {{-- Mobile top bar with logo on purple bg --}}
        <div class="flex flex-col items-center justify-center pt-12 pb-10 px-6"
             style="background: linear-gradient(160deg, #1e1b4b 0%, #2d2572 100%);">
            <img src="{{ asset('images/workzen-logo.png') }}" alt="Workzen"
                 class="h-10 w-auto" style="filter: brightness(0) invert(1);">
            <p class="mt-3 text-xs font-medium tracking-widest uppercase"
               style="color: rgba(255,255,255,0.5);">Your team workspace</p>
        </div>

        {{-- Mobile form --}}
        <div class="flex-1 px-6 py-8">
            {{ $slot }}
        </div>

        {{-- Mobile footer --}}
        <div class="pb-8 text-center">
            <p class="text-xs text-slate-400">
                &copy; {{ date('Y') }} Workzen &mdash; Secure connection
                <i class="bi bi-shield-check ml-1 text-emerald-500"></i>
            </p>
        </div>
    </div>

    {{-- Desktop: centered form --}}
    <div class="hidden lg:flex flex-1 flex-col">
        <div class="flex-1 flex items-center justify-center p-16">
            <div class="w-full" style="max-width: 400px;">
                {{ $slot }}
            </div>
        </div>
        <div class="pb-8 text-center">
            <p class="text-xs text-slate-400">
                &copy; {{ date('Y') }} Workzen &mdash; Secure connection
                <i class="bi bi-shield-check ml-1 text-emerald-500"></i>
            </p>
        </div>
    </div>
</div>

</body>
</html>
