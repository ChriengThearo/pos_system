<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Logging Out</title>
    <style>
        :root {
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
            width: 240px;
            height: 240px;
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
    <div class="loading-wrap" aria-live="polite">
        <img src="{{ asset('images/locked.gif') }}" alt="Logging out">
        <p>Signing you out...</p>
    </div>
    <script>
        (() => {
            const target = @json($target ?? route('staff.login'));
            setTimeout(() => {
                window.location.href = target;
            }, 1500);
        })();
    </script>
</body>
</html>
