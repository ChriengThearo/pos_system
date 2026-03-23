@extends('layouts.rbac')

@section('title', 'Edit Role')

@section('content')
    <div class="page-head">
        <h1 class="page-title">Edit Role</h1>
        <a href="{{ route('admin.rbac.roles.index') }}" class="btn btn-muted">Back to Roles</a>
    </div>

    <section class="card">
        <form method="POST" action="{{ route('admin.rbac.roles.update', ['groupId' => (int) $role->group_id]) }}" class="field-grid">
            @csrf
            @method('PATCH')

            <div>
                <label for="role_name">Name</label>
                <input id="role_name" name="role_name" type="text" value="{{ old('role_name', $role->role_name) }}" maxlength="20" required>
            </div>

            <div>
                <label for="role_description">Description</label>
                <input id="role_description" name="role_description" type="text" value="{{ old('role_description', $role->role_description) }}" maxlength="20">
            </div>

            <div>
                <label>Permissions</label>
                <div class="permission-grid">
                    @foreach($permissionGroups as $module => $permissions)
                        <section class="permission-group">
                            <h4>{{ $module }}</h4>
                            @foreach($permissions as $permission)
                                <label class="permission-item">
                                    <input
                                        type="checkbox"
                                        name="permissions[]"
                                        value="{{ $permission['code'] }}"
                                        @checked(collect(old('permissions', $selectedPermissions))->contains($permission['code']))
                                    >
                                    <span>
                                        <strong>{{ $permission['code'] }}</strong>
                                        - {{ $permission['description'] }}
                                    </span>
                                </label>
                            @endforeach
                        </section>
                    @endforeach
                </div>
            </div>

            <div class="actions" style="margin-top: 8px;">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('admin.rbac.roles.index') }}" class="btn btn-muted">Cancel</a>
            </div>
        </form>
    </section>
@endsection
