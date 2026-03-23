@extends('layouts.rbac')

@section('title', 'RBAC Dashboard')

@section('content')
    @php
        $canManageUsers = \App\Support\StaffAuth::can('users.manage');
        $canManageRoles = \App\Support\StaffAuth::can('roles.manage');
        $canManagePermissions = \App\Support\StaffAuth::can('permissions.manage');
    @endphp
    <section class="dashboard-hero">
        <div>
            <p class="hero-kicker">RBAC CONTROL CENTER</p>
            <h1 class="hero-title">RBAC Dashboard</h1>
            <p class="hero-sub">
                Create and manage staff access, roles, and permission mappings in one place.
            </p>
        </div>
        <div class="actions hero-actions">
            @if($canManageUsers)
                <a href="{{ route('admin.rbac.users.create') }}" class="btn btn-primary">Create User</a>
            @endif
            @if($canManageRoles)
                <a href="{{ route('admin.rbac.roles.create') }}" class="btn btn-primary">Create Role</a>
            @endif
            @if($canManagePermissions)
                <a href="{{ route('admin.rbac.permissions.create') }}" class="btn btn-primary">Create Permission</a>
            @endif
        </div>
    </section>

    <section class="metrics metrics-tiles">
        <article class="metric">
            <div class="label">Users</div>
            <div class="value">{{ number_format((int) ($metrics['users'] ?? 0)) }}</div>
        </article>
        <article class="metric">
            <div class="label">Roles</div>
            <div class="value">{{ number_format((int) ($metrics['roles'] ?? 0)) }}</div>
        </article>
        <article class="metric">
            <div class="label">Permissions</div>
            <div class="value">{{ number_format((int) ($metrics['permissions'] ?? 0)) }}</div>
        </article>
        <article class="metric">
            <div class="label">Role-Permission Links</div>
            <div class="value">{{ number_format((int) ($metrics['mappings'] ?? 0)) }}</div>
        </article>
    </section>

    <section class="data-grid">
        <article class="data-card">
            <div class="data-head">
                <h3>Roles</h3>
                <a class="data-link" href="{{ route('admin.rbac.roles.index') }}">View all</a>
            </div>
            @if(($rolesPreview ?? collect())->isEmpty())
                <p class="muted">No roles found in Oracle.</p>
            @else
                <ul class="data-list">
                    @foreach($rolesPreview as $role)
                        <li class="data-item">
                            <span>{{ $role->role_name }}</span>
                            <span class="data-code">ROLE</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>

        <article class="data-card">
            <div class="data-head">
                <h3>Permissions</h3>
                <a class="data-link" href="{{ route('admin.rbac.permissions.index') }}">View all</a>
            </div>
            @if(($permissionsPreview ?? collect())->isEmpty())
                <p class="muted">No permissions found in Oracle.</p>
            @else
                <ul class="data-list">
                    @foreach($permissionsPreview as $permission)
                        <li class="data-item">
                            <span>{{ $permission->title ?: $permission->code }}</span>
                            <span class="data-code">{{ $permission->code }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>

        <article class="data-card">
            <div class="data-head">
                <h3>Role-Permission Links</h3>
                <a class="data-link" href="{{ route('admin.rbac.roles.index') }}">View all</a>
            </div>
            @if(($mappingsPreview ?? collect())->isEmpty())
                <p class="muted">No mappings found in Oracle.</p>
            @else
                <ul class="data-list">
                    @foreach($mappingsPreview as $mapping)
                        <li class="data-item">
                            <span>{{ $mapping->role_name }}</span>
                            <span class="data-code">{{ $mapping->code }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </article>
    </section>

@endsection
