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
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; height: 100vh; display: flex; align-items: center; justify-content: center; }
        .card { text-align: center; padding: 48px 40px; max-width: 400px; }
        .icon-wrap { width: 72px; height: 72px; border-radius: 20px; background: #ede9fe; display: flex; align-items: center; justify-content: center; margin: 0 auto 24px; }
        .icon-wrap i { font-size: 32px; color: #7c3aed; }
        h1 { font-size: 22px; font-weight: 700; margin-bottom: 10px; }
        p { font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 28px; }
        button { background: #7c3aed; color: white; border: none; padding: 10px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; font-family: inherit; }
        button:hover { background: #6d28d9; }
    </style>
</head>
<body>
    <div class="card">
        <div class="icon-wrap"><i class="bi bi-wifi-off"></i></div>
        <h1>You're offline</h1>
        <p>It looks like you've lost your internet connection. Check your connection and try again.</p>
        <button onclick="window.location.reload()">Try again</button>
    </div>
</body>
</html>
