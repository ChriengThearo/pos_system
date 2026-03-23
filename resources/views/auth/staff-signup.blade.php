<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff Sign Up</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --text: #111924;
            --muted: #607086;
            --primary: #0058ad;
            --border: #d4deeb;
            --surface: #ffffff;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Manrope", sans-serif;
            color: var(--text);
            background:
                radial-gradient(circle at 12% 6%, rgba(0, 88, 173, .2), transparent 36%),
                radial-gradient(circle at 90% 12%, rgba(227, 100, 20, .15), transparent 30%),
                linear-gradient(165deg, #f4f7fb, #eaf0f8 58%, #e4ecf6 100%);
            display: grid;
            place-items: center;
            padding: 18px;
        }
        .card {
            width: min(470px, 100%);
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 18px 44px rgba(16, 24, 32, 0.12);
            padding: 22px;
        }
        h1 {
            margin: 0 0 8px;
            font-family: "Space Grotesk", sans-serif;
            font-size: 1.7rem;
        }
        .muted {
            margin: 0;
            color: var(--muted);
            line-height: 1.5;
            font-size: .94rem;
        }
        .field { margin-top: 12px; }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 700;
            font-size: .85rem;
            color: #38506b;
        }
        input, select {
            width: 100%;
            border: 1px solid #c7d6e7;
            border-radius: 10px;
            padding: 10px 11px;
            font-size: .96rem;
            font-family: inherit;
        }
        button {
            margin-top: 14px;
            width: 100%;
            border: 0;
            border-radius: 11px;
            padding: 11px 14px;
            font-size: .95rem;
            font-weight: 800;
            color: #fff;
            background: linear-gradient(145deg, #0058ad, #0071d4);
            cursor: pointer;
        }
        .flash {
            margin-top: 12px;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: .9rem;
        }
        .success { background: #d1e7dd; color: #0f5132; border: 1px solid #badbcc; }
        .error { background: #f8d7da; color: #842029; border: 1px solid #f5c2c7; }
        ul { margin: 8px 0 0; padding-left: 18px; }
    </style>
</head>
<body>
<section class="card">
    <h1>Create User Account</h1>
    <p class="muted">
        Select employee, role, and create password.
    </p>

    @if(session('success'))
        <div class="flash success">{{ session('success') }}</div>
    @endif

    @if(session('error'))
        <div class="flash error">{{ session('error') }}</div>
    @endif

    @if($errors->any())
        <div class="flash error">
            <strong>Sign-up failed.</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($employees->isEmpty())
        <div class="flash error">
            No available employees for sign-up. If you already have an account, use login.
        </div>
    @else
        <form method="POST" action="{{ route('staff.signup.create') }}">
            @csrf
            <div class="field">
                <label for="employee_id">Employee Name</label>
                <select id="employee_id" name="employee_id" required>
                    <option value="">Select employee</option>
                    @foreach($employees as $emp)
                        <option value="{{ $emp->employee_id }}" @selected((string) old('employee_id') === (string) $emp->employee_id)>
                            {{ $emp->employee_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="role_name">Role</label>
                <select id="role_name" name="role_name" required>
                    <option value="">Select role</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->role_name }}" @selected((string) old('role_name') === (string) $role->role_name)>
                            {{ $role->role_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" required minlength="4" maxlength="20">
            </div>

            <div class="field">
                <label for="password_confirmation">Confirm Password</label>
                <input id="password_confirmation" type="password" name="password_confirmation" required minlength="4" maxlength="20">
            </div>

            <button type="submit">Create Account</button>
            <p class="muted" style="margin-top: 10px; text-align: center;">
                Already registered? <a href="{{ route('staff.login') }}" style="color: #0058ad; font-weight: 700;">Log in</a>
            </p>
        </form>
    @endif
</section>
</body>
</html>
