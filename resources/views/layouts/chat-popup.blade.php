<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token"   content="{{ csrf_token() }}">
    @auth
    <meta name="user-name"   content="{{ auth()->user()->name }}">
    <meta name="user-avatar" content="{{ auth()->user()->avatar_url }}">
    @endauth
    <title>@yield('title', 'Chat') — Workzen</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        body { overflow: hidden; }
    </style>
</head>
<body class="h-screen font-sans antialiased bg-slate-50 dark:bg-slate-950 overflow-hidden"
      x-data="{ darkMode: $persist(false) }"
      x-init="document.documentElement.classList.toggle('dark', darkMode)">
    @yield('content')
    @stack('scripts')
</body>
</html>
