@extends('layouts.rbac')

@section('title', 'Users')

@section('content')
    <div class="page-head">
        <h1 class="page-title">Users</h1>
        <a href="{{ route('admin.rbac.users.create') }}" class="btn btn-primary">Create User</a>
    </div>

    <section class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Username</th>
                <th>Full Name</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Permissions</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
                @php
                    $status = mb_strtolower((string) ($user->status ?? 'active'));
                    $permissionCodes = (array) ($user->permission_codes ?? []);
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('admin.rbac.users.edit', ['userId' => (int) $user->user_id]) }}">
                            user{{ (int) ($user->employee_id ?? 0) }}
                        </a>
                    </td>
                    <td>{{ $user->employee_name }}</td>
                    <td>{{ $user->phone ?: '-' }}</td>
                    <td><span class="badge role">{{ $user->role_name ?: 'UNASSIGNED' }}</span></td>
                    <td>
                        @forelse($permissionCodes as $code)
                            <span class="badge perm">{{ $code }}</span>
                        @empty
                            <span class="muted">No permissions</span>
                        @endforelse
                    </td>
                    <td>
                        <span class="badge status {{ $status === 'active' ? 'active' : 'inactive' }}">
                            {{ mb_strtoupper((string) ($user->status ?? 'ACTIVE')) }}
                        </span>
                    </td>
                    <td>
                        <div class="actions">
                            <a href="{{ route('admin.rbac.users.edit', ['userId' => (int) $user->user_id]) }}" class="btn btn-edit">Edit</a>
                            <form method="POST" action="{{ route('admin.rbac.users.destroy', ['userId' => (int) $user->user_id]) }}" onsubmit="return confirm('Delete this user account?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-delete">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="muted">No users found. Create your first RBAC user.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endsection
