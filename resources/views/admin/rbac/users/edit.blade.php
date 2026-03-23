@extends('layouts.rbac')

@section('title', 'Edit User')

@section('content')
    <div class="page-head">
        <h1 class="page-title">Edit User</h1>
        <a href="{{ route('admin.rbac.users.index') }}" class="btn btn-muted">Back to Users</a>
    </div>

    <section class="card">
        <form method="POST" action="{{ route('admin.rbac.users.update', ['userId' => (int) $user->user_id]) }}" class="field-grid">
            @csrf
            @method('PATCH')

            <div>
                <label>Employee</label>
                <input type="text" value="{{ $user->employee_name }} (#{{ (int) $user->employee_id }})" readonly>
            </div>

            <div>
                <label for="group_id">Role</label>
                <select id="group_id" name="group_id" required>
                    @foreach($roles as $role)
                        <option value="{{ (int) $role->group_id }}" @selected((int) old('group_id', (int) $user->group_id) === (int) $role->group_id)>
                            {{ $role->role_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="password">Password (Optional)</label>
                <input id="password" name="password" type="password" minlength="4" maxlength="20" placeholder="Leave empty to keep current password">
            </div>

            <div>
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="ACTIVE" @selected(old('status', mb_strtoupper((string) $user->status)) === 'ACTIVE')>ACTIVE</option>
                    <option value="INACTIVE" @selected(old('status', mb_strtoupper((string) $user->status)) === 'INACTIVE')>INACTIVE</option>
                </select>
            </div>

            <div class="actions" style="margin-top: 8px;">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('admin.rbac.users.index') }}" class="btn btn-muted">Cancel</a>
            </div>
        </form>
    </section>
@endsection
