<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Error — Workzen</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:           #fff8f8;
            --bg2:          #fef2f2;
            --surface:      #ffffff;
            --border:       #fecaca;
            --border-soft:  #e2e8f0;
            --text:         #0f172a;
            --muted:        #64748b;
            --brand:        #7c3aed;
            --brand-dark:   #6d28d9;
            --accent:       #ef4444;
            --accent-bg:    #fef2f2;
            --accent-muted: #fca5a5;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg:         #0f172a;
                --bg2:        #1e293b;
                --surface:    #1e293b;
                --border:     #7f1d1d;
                --border-soft:#334155;
                --text:       #f1f5f9;
                --muted:      #94a3b8;
                --accent-bg:  #1c0a0a;
            }
        }

        html, body {
            height: 100%;
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        body { display: flex; flex-direction: column; min-height: 100vh; }

        .topbar {
            padding: 14px 24px;
            border-bottom: 1px solid var(--border-soft);
            background: var(--surface);
            display: flex; align-items: center; gap: 10px;
            flex-shrink: 0;
        }
        .logo { width: 32px; height: 32px; background: var(--brand); border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .logo svg { width: 18px; height: 18px; }
        .logo-name { font-size: 15px; font-weight: 700; color: var(--text); letter-spacing: -0.2px; }

        .main {
            flex: 1;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            padding: 56px 24px; text-align: center;
        }

        .icon-wrap { position: relative; margin: 0 auto 36px; width: 100px; height: 100px; }
        .icon-ring {
            width: 100px; height: 100px; border-radius: 50%;
            background: var(--accent-bg);
            display: flex; align-items: center; justify-content: center;
            border: 1px solid var(--border);
        }
        .icon-ring i { font-size: 42px; color: var(--accent); }
        .ring-pulse {
            position: absolute; inset: -7px; border-radius: 50%;
            border: 2px solid var(--accent); opacity: 0.2;
            animation: ring-pulse 2.6s ease-in-out infinite;
        }
        @keyframes ring-pulse {
            0%, 100% { transform: scale(1); opacity: 0.2; }
            50%       { transform: scale(1.1); opacity: 0; }
        }

        .code-label {
            display: inline-flex; align-items: center; gap: 6px;
            font-size: 11px; font-weight: 700; letter-spacing: 0.12em; text-transform: uppercase;
            color: var(--accent); background: var(--accent-bg);
            padding: 4px 10px; border-radius: 20px;
            margin-bottom: 20px;
            border: 1px solid var(--border);
        }

        h1 { font-size: 30px; font-weight: 700; letter-spacing: -0.6px; line-height: 1.2; margin-bottom: 14px; max-width: 420px; }
        .desc { font-size: 15px; color: var(--muted); line-height: 1.65; max-width: 420px; margin: 0 auto 36px; }

        .actions { display: flex; align-items: center; justify-content: center; gap: 10px; flex-wrap: wrap; }

        .btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 20px; border-radius: 10px;
            font-size: 14px; font-weight: 600; font-family: inherit;
            text-decoration: none; cursor: pointer;
            transition: background 0.15s, border-color 0.15s, color 0.15s;
            border: none;
        }
        .btn-ghost {
            background: var(--surface); color: var(--text);
            border: 1px solid var(--border-soft);
            box-shadow: 0 1px 2px rgba(0,0,0,.04);
        }
        .btn-ghost:hover { border-color: var(--brand); color: var(--brand); }
        .btn-primary { background: var(--brand); color: #fff; }
        .btn-primary:hover { background: var(--brand-dark); }

        .error-ref {
            margin-top: 44px;
            padding: 14px 20px;
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 10px;
            font-size: 12px;
            color: var(--muted);
            font-family: monospace;
            max-width: 400px;
            text-align: left;
        }
        .error-ref strong { display: block; font-family: 'Inter', sans-serif; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px; color: var(--accent); }

        .footer { padding: 18px 24px; border-top: 1px solid var(--border-soft); text-align: center; font-size: 12px; color: var(--muted); flex-shrink: 0; }

        @media (max-width: 480px) { h1 { font-size: 24px; } .main { padding: 40px 20px; } }
    </style>
</head>
<body>

    <header class="topbar">
        <div class="logo">
            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M4 6l4 12 4-8 4 8 4-12" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <span class="logo-name">Workzen</span>
    </header>

    <main class="main">

        <div class="icon-wrap">
            <span class="ring-pulse"></span>
            <div class="icon-ring">
                <i class="bi bi-exclamation-triangle-fill"></i>
            </div>
        </div>

        <span class="code-label">
            <i class="bi bi-bug" style="font-size:10px;"></i> 500 &mdash; Server Error
        </span>

        <h1>Something went wrong on our end</h1>

        <p class="desc">
            We hit an unexpected error while processing your request.
            Our team has been notified. Please try again in a moment.
        </p>

        <div class="actions">
            <a href="javascript:location.reload()" class="btn btn-ghost">
                <i class="bi bi-arrow-clockwise"></i> Try again
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                <i class="bi bi-house-door-fill"></i> Dashboard
            </a>
        </div>

        @if(isset($exception) && config('app.debug') && $exception->getMessage())
        <div class="error-ref">
            <strong>Debug info</strong>
            {{ $exception->getMessage() }}
        </div>
        @endif

    </main>

    <footer class="footer">
        Workzen &mdash; Project Management
    </footer>

</body>
</html>
