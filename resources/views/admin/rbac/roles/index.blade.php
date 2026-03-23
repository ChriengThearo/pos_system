@extends('layouts.rbac')

@section('title', 'Roles')

@section('content')
    <div class="page-head">
        <h1 class="page-title">Roles</h1>
        <a href="{{ route('admin.rbac.roles.create') }}" class="btn btn-primary">Create Role</a>
    </div>

    <section class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Description</th>
                <th>Permissions</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($roles as $role)
                <tr>
                    <td>
                        <a href="{{ route('admin.rbac.roles.edit', ['groupId' => (int) $role->group_id]) }}">
                            {{ $role->role_name }}
                        </a>
                    </td>
                    <td>{{ $role->role_description ?: 'Role group' }}</td>
                    <td><span class="badge count">{{ (int) $role->permission_count }} permissions</span></td>
                    <td>
                        <div class="actions">
                            <a href="{{ route('admin.rbac.roles.edit', ['groupId' => (int) $role->group_id]) }}" class="btn btn-edit">Edit</a>
                            <form method="POST" action="{{ route('admin.rbac.roles.destroy', ['groupId' => (int) $role->group_id]) }}" onsubmit="return confirm('Delete this role?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-delete">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="muted">No roles found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endsection
