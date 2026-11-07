<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Page not found — Workzen</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full font-sans antialiased bg-slate-50 dark:bg-slate-950 flex items-center justify-center p-6">

<div class="w-full max-w-md text-center">

    <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-slate-100 dark:bg-slate-800 mb-6">
        <i class="bi bi-compass text-4xl text-slate-400 dark:text-slate-500"></i>
    </div>

    <p class="text-xs font-semibold tracking-widest uppercase text-slate-400 dark:text-slate-500 mb-2">404</p>
    <h1 class="text-2xl font-bold text-slate-900 dark:text-white mb-3">Page not found</h1>
    <p class="text-sm text-slate-500 dark:text-slate-400 leading-relaxed mb-8">
        The page you're looking for doesn't exist or may have been moved.
    </p>

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

</div>

</body>
</html>
