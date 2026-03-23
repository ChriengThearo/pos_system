<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Employee Search</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 24px; }
            form { margin-bottom: 16px; }
            input[type="text"] { padding: 6px 10px; width: 260px; }
            button { padding: 6px 12px; }
            table { border-collapse: collapse; width: 420px; }
            th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
            th { background: #f5f5f5; }
            .muted { color: #666; }
        </style>
    </head>
    <body>
        <h1>Employee Search</h1>

        <form method="GET" action="{{ route('employees.search') }}">
            <input
                type="text"
                name="q"
                placeholder="Enter name"
                value="{{ $q }}"
                data-employee-search
                data-endpoint="{{ route('employees.search.data') }}"
            >
            <button type="submit">Search</button>
        </form>

        <div class="muted" data-employee-count>
            @if ($q !== '')
                Results for "{{ $q }}": {{ $employees->count() }}
            @else
                All employees: {{ $employees->count() }}
            @endif
        </div>

        @if ($employees->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>EMP_ID</th>
                        <th>EMP_NAME</th>
                    </tr>
                </thead>
                <tbody data-employee-rows>
                    @foreach ($employees as $emp)
                        <tr>
                            <td>{{ $emp->emp_id }}</td>
                            <td>{{ $emp->emp_name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="muted" data-employee-empty style="display: none;">No matching employees.</p>
        @else
            <p class="muted" data-employee-empty>No matching employees.</p>
        @endif

        <script src="{{ asset('js/employee-search.js') }}"></script>
    </body>
</html>
