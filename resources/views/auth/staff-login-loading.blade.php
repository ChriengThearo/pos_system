<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loading Dashboard</title>
    <style>
        :root {
            --bg: #f3f7ff;
            --text: #0e1724;
            --muted: #5d6f86;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: grid;
            place-items: center;
            background: #ffffff;
            color: var(--text);
            font-family: "Sora", sans-serif;
        }
        .loading-wrap {
            display: grid;
            place-items: center;
            gap: 12px;
            text-align: center;
            padding: 24px 18px;
        }
        .loading-wrap img {
            width: 280px;
            height: 280px;
            object-fit: contain;
        }
        .loading-wrap p {
            margin: 0;
            font-size: 0.95rem;
            color: var(--muted);
            letter-spacing: 0.02em;
        }
    </style>
</head>
<body>
    @php(session()->reflash())
    <div class="loading-wrap" aria-live="polite">
        <img src="{{ asset('images/SID_FB_001.gif') }}" alt="Loading">
        <p>Preparing your dashboard...</p>
    </div>
    <script>
        (() => {
            const target = @json($target ?? route('dashboard.entry'));
            setTimeout(() => {
                window.location.href = target;
            }, 2300);
        })();
    </script>
</body>
</html>
