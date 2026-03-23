@extends('layouts.ecommerce')

@section('title', 'Employee Detail')

@section('content')
    <section class="card">
        <div class="actions" style="justify-content: space-between; align-items: flex-start;">
            <div>
                <h1 class="headline">Employee Detail</h1>
                <p class="subtle">View and manage a single employee record.</p>
            </div>
            <div class="actions">
                <a href="{{ route('employees.index') }}" class="btn btn-muted">Back</a>
            </div>
        </div>
    </section>

    <section class="card" style="margin-top: 14px;">
        <div class="actions" style="justify-content: space-between; align-items: center;">
            <div>
                <h2 style="margin-top: 0;">Employee #{{ $employee->employee_id }}</h2>
                <div class="subtle">{{ $employee->employee_name }}</div>
            </div>
            @if($employee->photo_path)
                <img src="{{ asset($employee->photo_path) }}" alt="Employee photo" style="width: 72px; height: 72px; border-radius: 12px; object-fit: cover;">
            @endif
        </div>
    </section>

    <section class="card" style="margin-top: 14px;">
        <h3 style="margin-top: 0;">Details</h3>

        <form id="employee-update-form" method="POST" action="{{ route('employees.update', ['employeeId' => (int) $employee->employee_id]) }}" class="field-grid" style="margin-top: 12px;" enctype="multipart/form-data">
            @csrf
            @method('PATCH')
            <div>
                <label for="employee_name">Name</label>
                <input id="employee_name" name="employee_name" type="text" value="{{ $employee->employee_name }}" {{ $canManageEmployees ? '' : 'disabled' }} required>
            </div>
            <div>
                <label for="gender">Gender</label>
                <select id="gender" name="gender" {{ $canManageEmployees ? '' : 'disabled' }} required>
                    <option value="Male" @selected(strcasecmp((string) $employee->gender, 'Male') === 0)>Male</option>
                    <option value="Female" @selected(strcasecmp((string) $employee->gender, 'Female') === 0)>Female</option>
                    <option value="Other" @selected(strcasecmp((string) $employee->gender, 'Other') === 0)>Other</option>
                </select>
            </div>
            <div>
                <label for="birth_date">Birth Date</label>
                <input id="birth_date" name="birth_date" type="date" value="{{ $employee->birth_date ? \Illuminate\Support\Carbon::parse($employee->birth_date)->format('Y-m-d') : '' }}" {{ $canManageEmployees ? '' : 'disabled' }} required>
            </div>
            <div>
                <label for="phone">Phone</label>
                <input id="phone" name="phone" type="text" value="{{ $employee->phone }}" {{ $canManageEmployees ? '' : 'disabled' }} required>
            </div>
            <div>
                <label for="address">Address</label>
                <input id="address" name="address" type="text" value="{{ $employee->address }}" {{ $canManageEmployees ? '' : 'disabled' }} required>
            </div>
            <div>
                <label for="salary">Salary</label>
                <input id="salary" name="salary" type="number" step="0.01" min="0" value="{{ $employee->salary }}" {{ $canManageEmployees ? '' : 'disabled' }} required>
            </div>
            <div>
                <label for="remarks">Remarks</label>
                <input id="remarks" name="remarks" type="text" value="{{ $employee->remarks }}" {{ $canManageEmployees ? '' : 'disabled' }} required>
            </div>
            <div>
                <label for="job_id">Job</label>
                <select id="job_id" name="job_id" {{ $canManageEmployees ? '' : 'disabled' }} required>
                    @foreach($jobs as $job)
                        <option value="{{ $job->job_id }}" @selected((string) $employee->job_id === (string) $job->job_id)>
                            {{ $job->job_title }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="photo">Photo</label>
                <input id="photo" name="photo" type="file" accept="image/*" {{ $canManageEmployees ? '' : 'disabled' }}>
            </div>

        </form>

        @if($canManageEmployees)
            <div class="actions" style="align-items: end; margin-top: 12px;">
                <button type="submit" class="btn btn-primary" form="employee-update-form">Save</button>
                <form method="POST" action="{{ route('employees.destroy', ['employeeId' => (int) $employee->employee_id]) }}" onsubmit="return confirm('Delete employee #{{ (int) $employee->employee_id }}?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        @else
            <div class="subtle" style="margin-top: 12px;">You have read-only access.</div>
        @endif
    </section>
@endsection
