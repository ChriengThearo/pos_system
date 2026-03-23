<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --text: #0e1724;
            --muted: #5d6f86;
            --primary: #0b5ed7;
            --primary-dark: #0949a3;
            --accent: #ff8a3d;
            --border: rgba(15, 35, 62, 0.18);
            --surface: rgba(255, 255, 255, 0.88);
            --shadow: rgba(10, 28, 54, 0.22);
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Sora", sans-serif;
            color: var(--text);
            background: radial-gradient(circle at 15% 18%, rgba(11, 94, 215, 0.18), transparent 40%),
                        radial-gradient(circle at 85% 12%, rgba(255, 138, 61, 0.2), transparent 34%),
                        linear-gradient(160deg, #f3f7ff 0%, #e8f0ff 46%, #ecf3ff 100%);
        }
        .scene {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 28px 16px 40px;
            position: relative;
            overflow: hidden;
        }
        .scene::before {
            content: "";
            position: absolute;
            inset: -25%;
            background: conic-gradient(from 120deg, rgba(11, 94, 215, 0.25), rgba(0, 162, 255, 0.2), rgba(255, 138, 61, 0.28), rgba(11, 94, 215, 0.25));
            filter: blur(60px);
            opacity: 0.6;
            animation: aurora 18s ease-in-out infinite;
            z-index: 0;
        }
        .scene::after {
            content: "";
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.35), transparent 45%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.2), transparent 35%);
            opacity: 0.5;
            animation: glow 12s ease-in-out infinite;
            z-index: 0;
        }
        .orb {
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            filter: blur(4px);
            opacity: 0.55;
            animation: float 16s ease-in-out infinite;
            z-index: 0;
        }
        .orb.one {
            background: radial-gradient(circle, rgba(11, 94, 215, 0.6), transparent 70%);
            top: -90px;
            left: -60px;
        }
        .orb.two {
            background: radial-gradient(circle, rgba(255, 138, 61, 0.65), transparent 70%);
            bottom: -120px;
            right: -40px;
            animation-delay: -4s;
        }
        .orb.three {
            background: radial-gradient(circle, rgba(17, 154, 140, 0.5), transparent 70%);
            top: 40%;
            right: -120px;
            width: 210px;
            height: 210px;
            animation-delay: -8s;
        }
        .card {
            position: relative;
            width: min(470px, 100%);
            background: var(--surface);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 22px;
            padding: 28px 28px 24px;
            box-shadow: 0 24px 60px var(--shadow);
            backdrop-filter: blur(18px);
            animation: cardIn 700ms ease-out;
            z-index: 1;
        }
        .card::before {
            content: "";
            position: absolute;
            inset: -2px;
            border-radius: inherit;
            background: linear-gradient(130deg, rgba(11, 94, 215, 0.25), rgba(255, 138, 61, 0.3));
            z-index: -2;
        }
        .card::after {
            content: "";
            position: absolute;
            inset: 2px;
            border-radius: inherit;
            background: rgba(255, 255, 255, 0.72);
            z-index: -1;
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }
        .brand-mark {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: conic-gradient(from 120deg, #0b5ed7, #00a2ff, #ff8a3d, #0b5ed7);
            box-shadow: 0 10px 22px rgba(11, 94, 215, 0.3);
            animation: spin 12s linear infinite;
        }
        .brand-copy {
            display: grid;
            gap: 2px;
        }
        .brand-copy span {
            font-size: 0.78rem;
            letter-spacing: 0.24em;
            color: var(--muted);
            text-transform: uppercase;
        }
        h1 {
            margin: 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: 1.8rem;
            letter-spacing: 0.2px;
        }
        .muted {
            margin: 6px 0 0;
            color: var(--muted);
            line-height: 1.6;
            font-size: 0.92rem;
        }
        .muted code {
            font-family: "Space Grotesk", sans-serif;
            background: rgba(11, 94, 215, 0.1);
            color: var(--primary-dark);
            padding: 2px 6px;
            border-radius: 6px;
        }
        .form {
            margin-top: 18px;
            display: grid;
            gap: 14px;
        }
        label {
            display: block;
            margin-bottom: 7px;
            font-weight: 600;
            font-size: 0.85rem;
            color: #2d4760;
        }
        .control {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.95rem;
            font-family: inherit;
            background: rgba(255, 255, 255, 0.9);
            transition: border 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
        }
        .control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(11, 94, 215, 0.18);
            transform: translateY(-1px);
        }
        .select-control {
            appearance: none;
            background-image:
                linear-gradient(45deg, transparent 50%, #5d6f86 50%),
                linear-gradient(135deg, #5d6f86 50%, transparent 50%);
            background-position: calc(100% - 18px) 50%, calc(100% - 12px) 50%;
            background-size: 6px 6px;
            background-repeat: no-repeat;
            padding-right: 38px;
        }
        button {
            margin-top: 6px;
            width: 100%;
            border: 0;
            border-radius: 14px;
            padding: 12px 16px;
            font-size: 0.98rem;
            font-weight: 700;
            letter-spacing: 0.02em;
            color: #fff;
            background: linear-gradient(120deg, #0b5ed7, #0f78ff, #00a2ff);
            background-size: 200% 200%;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            animation: gradientShift 10s ease infinite;
        }
        button:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 26px rgba(11, 94, 215, 0.32);
        }
        button:active {
            transform: translateY(0);
        }
        .flash {
            margin-top: 14px;
            border-radius: 12px;
            padding: 12px 14px;
            font-size: 0.9rem;
        }
        .success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .error { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        ul { margin: 8px 0 0; padding-left: 18px; }
        .footer {
            margin-top: 12px;
            text-align: center;
            font-size: 0.9rem;
            color: var(--muted);
        }
        .link {
            color: var(--primary);
            font-weight: 700;
            text-decoration: none;
        }
        .link:hover {
            text-decoration: underline;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0) scale(1); }
            50% { transform: translateY(22px) translateX(14px) scale(1.04); }
        }
        @keyframes aurora {
            0% { transform: rotate(0deg) translate3d(0, 0, 0); }
            50% { transform: rotate(12deg) translate3d(3%, -2%, 0); }
            100% { transform: rotate(0deg) translate3d(0, 0, 0); }
        }
        @keyframes glow {
            0%, 100% { opacity: 0.45; }
            50% { opacity: 0.7; }
        }
        @keyframes cardIn {
            from { opacity: 0; transform: translateY(14px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        @media (max-width: 480px) {
            .card { padding: 22px 20px; }
            h1 { font-size: 1.55rem; }
        }
        @media (prefers-reduced-motion: reduce) {
            .orb, .card, .brand-mark, button { animation: none; }
            * { transition: none; }
        }
    </style>
</head>
<body>
<div class="scene">
    <div class="orb one"></div>
    <div class="orb two"></div>
    <div class="orb three"></div>

    <section class="card">
        <div class="brand">
            <div class="brand-mark"></div>
            <div class="brand-copy">
                <span>Staff Portal</span>
                <h1>Authorized User Login</h1>
            </div>
        </div>
        <p class="muted"></p>

        @if(session('success'))
            <div class="flash success">{{ session('success') }}</div>
        @endif

        @if(session('error'))
            <div class="flash error">{{ session('error') }}</div>
        @endif

        @if($errors->any())
            <div class="flash error">
                <strong>Login failed.</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($employees->isEmpty())
            <div class="flash error">
                No staff account exists yet. Please create one first.
            </div>
            <p class="footer">
                <a class="link" href="{{ route('staff.signup') }}">Go to sign up</a>
            </p>
        @else
            <form class="form" method="POST" action="{{ route('staff.login.attempt') }}">
                @csrf
                <div class="field">
                    <label for="employee_id">Employee Name</label>
                    <select id="employee_id" name="employee_id" required class="control select-control">
                        <option value="">Select employee</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->employee_id }}" @selected((string) old('employee_id') === (string) $emp->employee_id)>
                                {{ $emp->employee_name }} ({{ $emp->job_title }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input class="control" id="password" type="password" name="password" placeholder="Enter password" required maxlength="20">
                </div>
                <button type="submit">Log In</button>
            </form>
        @endif
    </section>
</div>
</body>
</html>
