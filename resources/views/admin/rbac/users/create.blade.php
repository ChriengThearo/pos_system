@extends('layouts.rbac')

@section('title', 'Create User')

@section('content')
    <div class="page-head">
        <h1 class="page-title">Create User</h1>
        <a href="{{ route('admin.rbac.users.index') }}" class="btn btn-muted">Back to Users</a>
    </div>

    <section class="card rbac-form-card">
        <div class="form-strip">
            <strong>RBAC User Setup</strong>
            <span>Keep status ACTIVE for immediate access.</span>
        </div>
        <div class="form-hero">
            <div class="hero-icon">U</div>
            <div>
                <p class="eyebrow">RBAC USERS</p>
                <h2 class="hero-title">Create a new user</h2>
                <p class="muted">
                    Assign an employee to a role and set their initial access status. Credentials can be updated later.
                </p>
                <div class="chip-row">
                    <span class="chip">Employee</span>
                    <span class="chip">Role</span>
                    <span class="chip">Status</span>
                </div>
            </div>
        </div>
        <form method="POST" action="{{ route('admin.rbac.users.store') }}" class="field-grid form-grid">
            @csrf

            <div class="form-panel full">
                <div>
                    <label for="employee_id">Employee</label>
                    <select id="employee_id" name="employee_id" required>
                        <option value="">Select employee</option>
                        @foreach($employees as $employee)
                            <option value="{{ (int) $employee->employee_id }}" @selected((int) old('employee_id') === (int) $employee->employee_id)>
                                {{ $employee->employee_name }} (#{{ (int) $employee->employee_id }})
                            </option>
                        @endforeach
                    </select>
                    <div class="helper">Choose the staff member who will receive access.</div>
                </div>

                <div>
                    <label for="group_id">Role</label>
                    <select id="group_id" name="group_id" required>
                        <option value="">Select role</option>
                        @foreach($roles as $role)
                            <option value="{{ (int) $role->group_id }}" @selected((int) old('group_id') === (int) $role->group_id)>
                                {{ $role->role_name }}
                            </option>
                        @endforeach
                    </select>
                    <div class="helper">Role controls the permissions assigned to the user.</div>
                </div>

                <div>
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password" required minlength="4" maxlength="20" placeholder="Password (max 20 chars)">
                    <div class="helper">Use a strong password and update it regularly.</div>
                </div>

                <div>
                    <label for="status">Status</label>
                    <select id="status" name="status" required>
                        <option value="ACTIVE" @selected(old('status', $defaultStatus) === 'ACTIVE')>ACTIVE</option>
                        <option value="INACTIVE" @selected(old('status', $defaultStatus) === 'INACTIVE')>INACTIVE</option>
                    </select>
                    <div class="helper">Inactive users cannot sign in until reactivated.</div>
                </div>
            </div>

            <div class="actions full" style="margin-top: 8px;">
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('admin.rbac.users.index') }}" class="btn btn-muted">Cancel</a>
            </div>
        </form>
    </section>
@endsection
