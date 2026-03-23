@extends('layouts.ecommerce')

@section('title', 'Employees')

@section('content')
    <section class="page-hero">
        <div>
            <p class="hero-kicker">EMPLOYEE DIRECTORY</p>
            <h1 class="headline">Employees</h1>
            <p class="subtle">Manage employee records from <code>EMPLOYEES</code> and <code>JOBS</code>.</p>
        </div>
        <div class="hero-actions">
            <span class="chip chip-strong">{{ $employees->total() }} employees</span>
            @if($canManageEmployees)
                <a href="#employee-add-panel" class="btn btn-primary">Add Employee</a>
            @endif
        </div>
    </section>

    <section class="card search-card">
        <form method="GET" action="{{ route('employees.index') }}" class="search-grid">
            <div>
                <label for="q">Search name / phone / job title</label>
                <input id="q" type="text" name="q" value="{{ $q }}" placeholder="e.g. Dara or SALES">
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="{{ route('employees.index') }}" class="btn btn-muted">Reset</a>
                <a href="{{ route('employees.search') }}" class="btn btn-muted">Quick Search</a>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="grid grid-4">
            <article class="stat">
                <div class="label">Male</div>
                <div class="value">{{ number_format((int) ($genderCounts['male'] ?? 0)) }}</div>
            </article>
            <article class="stat">
                <div class="label">Female</div>
                <div class="value">{{ number_format((int) ($genderCounts['female'] ?? 0)) }}</div>
            </article>
            <article class="stat">
                <div class="label">Other</div>
                <div class="value">{{ number_format((int) ($genderCounts['other'] ?? 0)) }}</div>
            </article>
            <article class="stat">
                <div class="label">Total</div>
                <div class="value">{{ number_format((int) ($genderCounts['total'] ?? 0)) }}</div>
            </article>
        </div>
    </section>

    <section class="card">
        <div class="actions" style="justify-content: space-between; align-items: center;">
            <div class="tab-switch" role="tablist" aria-label="Employee panels">
                <button type="button" class="tab-btn active" id="show-employee-list" role="tab" aria-selected="true">Employee List</button>
                @if($canManageEmployees)
                    <button type="button" class="tab-btn" id="show-employee-add" role="tab" aria-selected="false">Add Employee</button>
                @endif
            </div>
            <span class="subtle">Switch between list and create form.</span>
        </div>
    </section>

    <div class="grid">
        <section class="card" id="employee-list-panel">
            <div class="actions" style="justify-content: space-between;">
                <h2 style="margin-top: 0;">Employee List</h2>
                <span class="chip">{{ $employees->total() }} total</span>
            </div>
            <div class="table-wrap soft" style="margin-top: 12px;">
                <table class="employee-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Birth Date</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Job</th>
                        <th>Salary</th>
                        <th>Remarks</th>
                        <th>Photo</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($employees as $employee)
                        <tr>
                            <td><strong>#{{ $employee->employee_id }}</strong></td>
                            <td>
                                {{ $employee->employee_name }}
                            </td>
                            <td>
                                {{ $employee->gender }}
                            </td>
                            <td>
                                {{ $employee->birth_date ? \Illuminate\Support\Carbon::parse($employee->birth_date)->format('Y-m-d') : 'N/A' }}
                            </td>
                            <td>
                                {{ $employee->phone }}
                            </td>
                            <td>
                                {{ $employee->address }}
                            </td>
                            <td>
                                {{ $employee->job_title }}
                            </td>
                            <td>
                                {{ $employee->salary }}
                            </td>
                            <td>
                                {{ $employee->remarks }}
                            </td>
                            <td>
                                @if($employee->photo_path)
                                    <img src="{{ asset($employee->photo_path) }}" alt="Employee photo" class="employee-photo">
                                @else
                                    <span class="subtle">No photo</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('employees.show', ['employeeId' => (int) $employee->employee_id]) }}" class="btn btn-muted">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="subtle">No employees found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pager" style="margin-top: 12px;">
                {{ $employees->links('pagination.orbit') }}
            </div>
        </section>

        @if($canManageEmployees)
        <section class="card" id="employee-add-panel" style="display: none;">
            <h2 style="margin-top: 0;">Add Employee</h2>
            <p class="subtle">Create a new employee record. All fields are required, including the photo.</p>

            <form method="POST" action="{{ route('employees.store') }}" class="field-grid" style="margin-top: 12px;" enctype="multipart/form-data">
                @csrf
                <div>
                    <label for="employee_name">Name</label>
                    <input id="employee_name" name="employee_name" type="text" required>
                </div>
                <div>
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="" disabled selected>Select gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div>
                    <label for="birth_date">Birth Date</label>
                    <input id="birth_date" name="birth_date" type="date" required>
                </div>
                <div>
                    <label for="phone">Phone</label>
                    <input id="phone" name="phone" type="text" required>
                </div>
                <div>
                    <label for="address">Address</label>
                    <input id="address" name="address" type="text" required>
                </div>
                <div>
                    <label for="salary">Salary</label>
                    <input id="salary" name="salary" type="number" step="0.01" min="0" required>
                </div>
                <div>
                    <label for="remarks">Remarks</label>
                    <input id="remarks" name="remarks" type="text" required>
                </div>
                <div>
                    <label for="job_id">Job</label>
                    <select id="job_id" name="job_id" required>
                        @foreach($jobs as $job)
                            <option value="{{ $job->job_id }}">{{ $job->job_title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="photo">Photo</label>
                    <input id="photo" name="photo" type="file" accept="image/*" required>
                </div>
                <div class="actions" style="align-items: end;">
                    <button type="submit" class="btn btn-primary">Create</button>
                </div>
            </form>
        </section>
        @endif
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const listBtn = document.getElementById('show-employee-list');
            const addBtn = document.getElementById('show-employee-add');
            const listPanel = document.getElementById('employee-list-panel');
            const addPanel = document.getElementById('employee-add-panel');

            if (!listBtn || !listPanel) return;

            const activate = (target) => {
                const showList = target === 'list';
                listPanel.style.display = showList ? '' : 'none';
                if (addPanel) addPanel.style.display = showList ? 'none' : '';
                listBtn.classList.toggle('active', showList);
                listBtn.setAttribute('aria-selected', showList ? 'true' : 'false');
                if (addBtn) {
                    addBtn.classList.toggle('active', !showList);
                    addBtn.setAttribute('aria-selected', showList ? 'false' : 'true');
                }
            };

            listBtn.addEventListener('click', () => activate('list'));
            if (addBtn) {
                addBtn.addEventListener('click', () => activate('add'));
            }
        });
    </script>
@endsection
