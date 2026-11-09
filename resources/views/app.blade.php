<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="alternate icon" type="image/x-icon" href="/favicon.ico">

    {{-- PWA --}}
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#7c3aed">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Workzen">
    <link rel="apple-touch-icon" href="/images/icons/apple-touch-icon-180.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/images/icons/apple-touch-icon-120.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/images/icons/apple-touch-icon-152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/icons/apple-touch-icon-180.png">
    <meta name="msapplication-TileImage" content="/images/icons/icon-144.png">
    <meta name="msapplication-TileColor" content="#7c3aed">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="h-full font-sans antialiased bg-slate-50 dark:bg-slate-950">
    @inertia
</body>
</html>
