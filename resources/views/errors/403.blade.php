<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access denied — Workzen</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-slate-50 dark:bg-slate-950 flex items-center justify-center p-6">

<div class="w-full max-w-md text-center">

    {{-- Icon badge --}}
    <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-violet-100 dark:bg-violet-900/30 mb-6">
        <i class="bi bi-shield-lock text-4xl text-violet-500 dark:text-violet-400"></i>
    </div>

    {{-- Code + heading --}}
    <p class="text-xs font-semibold tracking-widest uppercase text-violet-500 dark:text-violet-400 mb-2">403</p>
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">Access denied</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed mb-8">
        {{ $exception->getMessage() ?: "You don't have permission to view this page. Contact your admin if you think this is a mistake." }}
    </p>

    {{-- Actions --}}
    <div class="flex items-center justify-center gap-3">
        <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700
                  bg-white dark:bg-slate-800 text-sm font-medium text-slate-700 dark:text-slate-200
                  hover:border-violet-400 hover:text-violet-700 dark:hover:text-violet-300 transition-all shadow-sm">
            <i class="bi bi-arrow-left text-xs"></i> Go back
        </a>
        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-violet-600 hover:bg-violet-700
                  text-sm font-medium text-white transition-all shadow-sm">
            <i class="bi bi-house text-xs"></i> Dashboard
        </a>
    </div>

    {{-- Help text --}}
    <p class="mt-10 text-xs text-slate-400 dark:text-slate-600">
        Need access?
        <a href="mailto:{{ config('mail.from.address', 'admin@workzen.app') }}"
           class="text-violet-500 hover:underline">Contact your administrator</a>
    </p>

</div>

</body>
</html>
