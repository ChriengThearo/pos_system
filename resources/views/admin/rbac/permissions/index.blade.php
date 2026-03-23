@extends('layouts.rbac')

@section('title', 'Permissions')

@section('content')
    <div class="page-head">
        <h1 class="page-title">Permissions</h1>
        <a href="{{ route('admin.rbac.permissions.create') }}" class="btn btn-primary">Create Permission</a>
    </div>

    <section class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>Code</th>
                <th>Name</th>
                <th>Module</th>
                <th>Description</th>
                <th>Roles</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @forelse($permissions as $permission)
                <tr>
                    <td>
                        <a href="{{ route('admin.rbac.permissions.edit', ['formId' => (int) $permission->form_id]) }}">
                            {{ $permission->code }}
                        </a>
                    </td>
                    <td>{{ $permission->name }}</td>
                    <td>{{ $permission->module }}</td>
                    <td>{{ $permission->description }}</td>
                    <td><span class="badge count">{{ (int) $permission->role_count }}</span></td>
                    <td>
                        <div class="actions">
                            <a href="{{ route('admin.rbac.permissions.edit', ['formId' => (int) $permission->form_id]) }}" class="btn btn-edit">Edit</a>
                            <form method="POST" action="{{ route('admin.rbac.permissions.destroy', ['formId' => (int) $permission->form_id]) }}" onsubmit="return confirm('Delete this permission?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-delete">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted">No permissions found.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </section>
@endsection
