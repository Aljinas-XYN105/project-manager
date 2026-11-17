<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Denied — Workzen</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,400;0,14..32,500;0,14..32,600;0,14..32,700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:           #f8fafc;
            --bg2:          #f1f5f9;
            --surface:      #ffffff;
            --border:       #e2e8f0;
            --text:         #0f172a;
            --muted:        #64748b;
            --brand:        #7c3aed;
            --brand-bg:     #ede9fe;
            --brand-dark:   #6d28d9;
            --accent:       #7c3aed;
            --accent-bg:    #ede9fe;
            --accent-muted: #a78bfa;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg:        #0f172a;
                --bg2:       #1e293b;
                --surface:   #1e293b;
                --border:    #334155;
                --text:      #f1f5f9;
                --muted:     #94a3b8;
                --brand-bg:  #2e1065;
                --accent-bg: #2e1065;
            }
        }

        html, body {
            height: 100%;
            font-family: 'Inter', system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
        }

        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ── Topbar ── */
        .topbar {
            padding: 14px 24px;
            border-bottom: 1px solid var(--border);
            background: var(--surface);
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }
        .logo {
            width: 32px; height: 32px;
            background: var(--brand);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        .logo svg { width: 18px; height: 18px; }
        .logo-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
            letter-spacing: -0.2px;
        }

        /* ── Main ── */
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 56px 24px;
            text-align: center;
        }

        /* ── Icon ── */
        .icon-wrap {
            position: relative;
            margin: 0 auto 36px;
            width: 100px; height: 100px;
        }
        .icon-ring {
            width: 100px; height: 100px;
            border-radius: 50%;
            background: var(--accent-bg);
            display: flex; align-items: center; justify-content: center;
        }
        .icon-ring i {
            font-size: 42px;
            color: var(--accent);
        }
        .ring-pulse {
            position: absolute;
            inset: -7px;
            border-radius: 50%;
            border: 2px solid var(--accent);
            opacity: 0.25;
            animation: ring-pulse 2.6s ease-in-out infinite;
        }
        @keyframes ring-pulse {
            0%, 100% { transform: scale(1); opacity: 0.25; }
            50%       { transform: scale(1.1); opacity: 0; }
        }

        /* ── Text ── */
        .code-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--accent);
            background: var(--accent-bg);
            padding: 4px 10px;
            border-radius: 20px;
            margin-bottom: 20px;
        }

        h1 {
            font-size: 30px;
            font-weight: 700;
            letter-spacing: -0.6px;
            line-height: 1.2;
            margin-bottom: 14px;
            max-width: 420px;
        }

        .desc {
            font-size: 15px;
            color: var(--muted);
            line-height: 1.65;
            max-width: 420px;
            margin: 0 auto 36px;
        }

        /* ── Buttons ── */
        .actions {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 10px 20px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            font-family: inherit;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s, color 0.15s, box-shadow 0.15s;
            border: none;
        }

        .btn-ghost {
            background: var(--surface);
            color: var(--text);
            border: 1px solid var(--border);
            box-shadow: 0 1px 2px rgba(0,0,0,.04);
        }
        .btn-ghost:hover {
            border-color: var(--accent);
            color: var(--accent);
            box-shadow: 0 2px 6px rgba(124,58,237,.12);
        }

        .btn-primary {
            background: var(--brand);
            color: #fff;
            box-shadow: 0 1px 3px rgba(124,58,237,.35);
        }
        .btn-primary:hover {
            background: var(--brand-dark);
            box-shadow: 0 3px 10px rgba(124,58,237,.4);
        }

        /* ── Divider + help ── */
        .help-row {
            margin-top: 44px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            color: var(--muted);
        }
        .help-row::before,
        .help-row::after {
            content: '';
            flex: 1;
            height: 1px;
            background: var(--border);
            max-width: 60px;
        }

        /* ── Quicklinks ── */
        .quicklinks {
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            flex-wrap: wrap;
        }
        .ql-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            color: var(--muted);
            text-decoration: none;
            background: var(--bg2);
            border: 1px solid var(--border);
            transition: color 0.14s, border-color 0.14s, background 0.14s;
        }
        .ql-item:hover {
            color: var(--accent);
            border-color: var(--accent-muted);
            background: var(--accent-bg);
        }
        .ql-item i { font-size: 12px; }

        /* ── Footer ── */
        .footer {
            padding: 18px 24px;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: 12px;
            color: var(--muted);
            flex-shrink: 0;
        }

        @media (max-width: 480px) {
            h1 { font-size: 24px; }
            .main { padding: 40px 20px; }
        }
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
                <i class="bi bi-shield-lock-fill"></i>
            </div>
        </div>

        <span class="code-label">
            <i class="bi bi-lock-fill" style="font-size:10px;"></i> 403 &mdash; Forbidden
        </span>

        <h1>You don't have access to this</h1>

        <p class="desc">
            {{ isset($exception) && $exception->getMessage()
                ? $exception->getMessage()
                : "This page is restricted to authorized users only. If you believe this is a mistake, ask your admin to grant you access." }}
        </p>

        <div class="actions">
            <a href="{{ url()->previous() !== url()->current() ? url()->previous() : route('dashboard') }}" class="btn btn-ghost">
                <i class="bi bi-arrow-left"></i> Go back
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-primary">
                <i class="bi bi-house-door-fill"></i> Dashboard
            </a>
        </div>

        <div class="help-row">
            Think this is wrong?
        </div>

        <div class="quicklinks">
            <a href="{{ route('dashboard') }}" class="ql-item">
                <i class="bi bi-grid-1x2"></i> Dashboard
            </a>
            <a href="{{ route('projects.index') }}" class="ql-item">
                <i class="bi bi-folder2"></i> Projects
            </a>
            <a href="mailto:{{ config('mail.from.address', 'admin@workzen.app') }}" class="ql-item">
                <i class="bi bi-envelope"></i> Contact admin
            </a>
        </div>

    </main>

    <footer class="footer">
        Workzen &mdash; Project Management
    </footer>

</body>
</html>
