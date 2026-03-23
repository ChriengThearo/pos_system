@extends('layouts.rbac')

@section('title', 'Create Role')

@section('content')
    <div class="page-head">
        <h1 class="page-title">Create Role</h1>
        <a href="{{ route('admin.rbac.roles.index') }}" class="btn btn-muted">Back to Roles</a>
    </div>

    <section class="card">
        <form method="POST" action="{{ route('admin.rbac.roles.store') }}" class="field-grid">
            @csrf

            <div>
                <label for="role_name">Name</label>
                <input id="role_name" name="role_name" type="text" value="{{ old('role_name') }}" maxlength="20" placeholder="Role name" required>
            </div>

            <div>
                <label for="role_description">Description</label>
                <input id="role_description" name="role_description" type="text" value="{{ old('role_description') }}" maxlength="20" placeholder="Short description">
            </div>

            <div>
                <label>Permissions</label>
                <div class="permission-grid">
                    @foreach($permissionGroups as $module => $permissions)
                        <section class="permission-group">
                            <h4>{{ $module }}</h4>
                            @foreach($permissions as $permission)
                                <label class="permission-item">
                                    <input type="checkbox" name="permissions[]" value="{{ $permission['code'] }}" @checked(collect(old('permissions', []))->contains($permission['code']))>
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
                <button type="submit" class="btn btn-primary">Save</button>
                <a href="{{ route('admin.rbac.roles.index') }}" class="btn btn-muted">Cancel</a>
            </div>
        </form>
    </section>
@endsection
