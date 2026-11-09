<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Offline — Workzen</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #f8fafc;
            --bg2: #f1f5f9;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
            --brand: #7c3aed;
            --brand-light: #ede9fe;
            --brand-dark: #6d28d9;
        }

        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0f172a;
                --bg2: #1e293b;
                --border: #334155;
                --text: #f1f5f9;
                --muted: #94a3b8;
                --brand-light: #2e1065;
            }
        }

        html, body { height: 100%; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        /* ── Top bar ── */
        .topbar {
            padding: 16px 24px;
            border-bottom: 1px solid var(--border);
            background: var(--bg);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .logo {
            width: 32px; height: 32px;
            background: var(--brand);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
        }
        .logo svg { width: 18px; height: 18px; }
        .logo-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--text);
        }

        /* ── Main content ── */
        .main {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 48px 24px;
            text-align: center;
        }

        .icon-ring {
            width: 96px; height: 96px;
            border-radius: 50%;
            background: var(--brand-light);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 32px;
            position: relative;
        }
        .icon-ring i {
            font-size: 40px;
            color: var(--brand);
        }
        .pulse {
            position: absolute;
            inset: -6px;
            border-radius: 50%;
            border: 2px solid var(--brand);
            opacity: 0.3;
            animation: pulse 2.4s ease-in-out infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.3; }
            50%       { transform: scale(1.1); opacity: 0; }
        }

        h1 {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 12px;
        }
        .subtitle {
            font-size: 15px;
            color: var(--muted);
            line-height: 1.6;
            max-width: 360px;
            margin: 0 auto 36px;
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: center;
        }

        .btn-primary {
            background: var(--brand);
            color: #fff;
            border: none;
            padding: 11px 28px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.15s;
        }
        .btn-primary:hover { background: var(--brand-dark); }

        .status-line {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--muted);
        }
        .status-dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #ef4444;
            flex-shrink: 0;
            animation: blink 1.8s ease-in-out infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.3; }
        }

        /* ── Tips grid ── */
        .tips {
            margin-top: 56px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            max-width: 680px;
            width: 100%;
        }
        .tip {
            background: var(--bg2);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 16px 18px;
            text-align: left;
        }
        .tip i {
            font-size: 18px;
            color: var(--brand);
            margin-bottom: 8px;
            display: block;
        }
        .tip-title {
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .tip-body {
            font-size: 12px;
            color: var(--muted);
            line-height: 1.5;
        }

        /* ── Footer ── */
        .footer {
            padding: 20px 24px;
            border-top: 1px solid var(--border);
            text-align: center;
            font-size: 12px;
            color: var(--muted);
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
        <div class="icon-ring">
            <span class="pulse"></span>
            <i class="bi bi-wifi-off"></i>
        </div>

        <h1>No internet connection</h1>
        <p class="subtitle">
            It looks like your device is offline. Check your Wi-Fi or mobile data
            and try again — your work is safe and will sync when you reconnect.
        </p>

        <div class="actions">
            <button class="btn-primary" onclick="window.location.reload()">
                <i class="bi bi-arrow-clockwise"></i> Try again
            </button>
            <div class="status-line">
                <span class="status-dot"></span>
                <span id="status-text">Waiting for connection…</span>
            </div>
        </div>

        <div class="tips">
            <div class="tip">
                <i class="bi bi-wifi"></i>
                <div class="tip-title">Check your Wi-Fi</div>
                <div class="tip-body">Make sure you're connected to a network and the signal is strong.</div>
            </div>
            <div class="tip">
                <i class="bi bi-phone"></i>
                <div class="tip-title">Mobile data</div>
                <div class="tip-body">If you're on mobile, check that data is enabled and you have coverage.</div>
            </div>
            <div class="tip">
                <i class="bi bi-arrow-repeat"></i>
                <div class="tip-title">Auto-reconnect</div>
                <div class="tip-body">This page will reload automatically once your connection is restored.</div>
            </div>
        </div>
    </main>

    <footer class="footer">
        Workzen — Project Management
    </footer>

    <script>
        // Auto-reload when connection is restored
        window.addEventListener('online', () => {
            document.getElementById('status-text').textContent = 'Connection restored — reloading…';
            setTimeout(() => window.location.reload(), 800);
        });

        // Ping every 10s as a fallback for browsers that miss the online event
        setInterval(() => {
            fetch('/favicon.ico?_=' + Date.now(), { method: 'HEAD', cache: 'no-store' })
                .then(() => {
                    document.getElementById('status-text').textContent = 'Connection restored — reloading…';
                    setTimeout(() => window.location.reload(), 800);
                })
                .catch(() => {});
        }, 10000);
    </script>
</body>
</html>
