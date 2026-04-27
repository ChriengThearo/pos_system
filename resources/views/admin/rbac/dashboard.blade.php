@extends('layouts.rbac')

@section('title', 'RBAC Dashboard')

@section('content')
    @php
        $canManageUsers = \App\Support\StaffAuth::can('users.manage');
        $canManageRoles = \App\Support\StaffAuth::can('roles.manage');
        $canManagePermissions = \App\Support\StaffAuth::can('permissions.manage');
        $canReadUsers = $canManageUsers || \App\Support\StaffAuth::can('users.read');
        $canReadRoles = $canManageRoles || \App\Support\StaffAuth::can('roles.read');
        $canReadPermissions = $canManagePermissions || \App\Support\StaffAuth::can('permissions.read');

        $usersCount = (int) ($metrics['users'] ?? 0);
        $rolesCount = (int) ($metrics['roles'] ?? 0);
        $permissionsCount = (int) ($metrics['permissions'] ?? 0);
        $mappingsCount = (int) ($metrics['mappings'] ?? 0);

        $metricCards = [
            ['label' => 'Users', 'value' => $usersCount, 'hint' => 'Staff account records', 'tone' => 'users', 'color' => '#0f5f97'],
            ['label' => 'Roles', 'value' => $rolesCount, 'hint' => 'Access control groups', 'tone' => 'roles', 'color' => '#2c7a5a'],
            ['label' => 'Permissions', 'value' => $permissionsCount, 'hint' => 'Feature-level abilities', 'tone' => 'permissions', 'color' => '#0e8fa6'],
            ['label' => 'Mappings', 'value' => $mappingsCount, 'hint' => 'Role-permission links', 'tone' => 'mappings', 'color' => '#d1811f'],
        ];

        $maxMetric = max(1, $usersCount, $rolesCount, $permissionsCount, $mappingsCount);
        $totalMetric = max(1, $usersCount + $rolesCount + $permissionsCount + $mappingsCount);
        $avgUsersPerRole = $rolesCount > 0 ? number_format($usersCount / $rolesCount, 1) : '0.0';
        $avgPermissionsPerRole = $rolesCount > 0 ? number_format($mappingsCount / $rolesCount, 1) : '0.0';
        $mappingCoverage = ($rolesCount > 0 && $permissionsCount > 0)
            ? min(100, (int) round(($mappingsCount / ($rolesCount * $permissionsCount)) * 100))
            : 0;

        $quickLinks = collect([
            [
                'show' => $canReadUsers,
                'label' => 'Users',
                'description' => 'Manage staff login access',
                'route' => route('admin.rbac.users.index'),
                'code' => 'USR',
                'count' => $usersCount,
            ],
            [
                'show' => $canReadRoles,
                'label' => 'Roles',
                'description' => 'Organize permissions into groups',
                'route' => route('admin.rbac.roles.index'),
                'code' => 'ROL',
                'count' => $rolesCount,
            ],
            [
                'show' => $canReadPermissions,
                'label' => 'Permissions',
                'description' => 'Define actionable system abilities',
                'route' => route('admin.rbac.permissions.index'),
                'code' => 'PER',
                'count' => $permissionsCount,
            ],
        ])->filter(fn (array $item): bool => $item['show'])->values();
    @endphp

    <style>
        .rbacdash {
            --dash-navy: #0e2a47;
            --dash-blue: #0f5f97;
            --dash-cyan: #0ca6b8;
            --dash-orange: #f39c2c;
            --dash-ink: #10273d;
            --dash-muted: #60768f;
            --dash-surface: #ffffff;
            --dash-border: #d5e1ed;
            --dash-shadow: 0 16px 40px rgba(14, 42, 71, 0.12);
            display: grid;
            gap: 14px;
            animation: dash-enter 0.45s ease both;
        }

        .rbacdash * {
            min-width: 0;
        }

        .rbacdash-hero {
            position: relative;
            isolation: isolate;
            overflow: hidden;
            border-radius: 18px;
            padding: 24px;
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) auto;
            gap: 16px;
            color: #edf6ff;
            background:
                radial-gradient(circle at 12% 25%, rgba(255, 255, 255, 0.18), transparent 34%),
                radial-gradient(circle at 88% 20%, rgba(243, 156, 44, 0.3), transparent 38%),
                linear-gradient(140deg, #0d2742 0%, #0f5b8e 48%, #0a7aa8 100%);
            box-shadow: 0 24px 48px rgba(10, 32, 56, 0.22);
        }

        .rbacdash-hero::before {
            content: "";
            position: absolute;
            right: -64px;
            bottom: -80px;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(243, 156, 44, 0.36), transparent 64%);
            filter: blur(4px);
            z-index: -1;
        }

        .rbacdash-kicker {
            margin: 0;
            font-size: 0.72rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: rgba(236, 246, 255, 0.86);
            font-weight: 800;
        }

        .rbacdash-title {
            margin: 6px 0 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: clamp(1.9rem, 3vw, 2.55rem);
            line-height: 1.12;
        }

        .rbacdash-sub {
            margin: 10px 0 0;
            max-width: 640px;
            font-size: 0.96rem;
            color: rgba(236, 246, 255, 0.88);
            line-height: 1.55;
        }

        .rbacdash-pill-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 14px;
        }

        .rbacdash-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.22);
            background: rgba(255, 255, 255, 0.14);
            color: #f6fbff;
            font-size: 0.76rem;
            font-weight: 700;
            padding: 5px 10px;
        }

        .rbacdash-hero-actions {
            display: inline-flex;
            align-items: flex-start;
            justify-content: flex-end;
            flex-wrap: wrap;
            gap: 8px;
        }

        .rbacdash-hero-actions .btn {
            border-radius: 10px;
            font-size: 0.82rem;
            padding: 8px 12px;
        }

        .rbacdash-hero-actions .btn-primary {
            background: linear-gradient(140deg, #f4a21c, #f5bf4d);
            color: #192733;
            box-shadow: 0 10px 20px rgba(18, 35, 52, 0.2);
        }

        .rbacdash-hero-actions .btn-muted {
            border: 1px solid rgba(255, 255, 255, 0.34);
            background: rgba(255, 255, 255, 0.08);
            color: #f1f8ff;
        }

        .rbacdash-metrics {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .rbacdash-metric {
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            padding: 13px 14px;
            background:
                linear-gradient(165deg, #ffffff 0%, #f7fbff 100%);
            box-shadow: var(--dash-shadow);
            position: relative;
            overflow: hidden;
            animation: dash-enter 0.4s ease both;
        }

        .rbacdash-metric::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--tone-color, #0f5f97);
        }

        .rbacdash-metric.tone-users { --tone-color: #0f5f97; }
        .rbacdash-metric.tone-roles { --tone-color: #2c7a5a; }
        .rbacdash-metric.tone-permissions { --tone-color: #0e8fa6; }
        .rbacdash-metric.tone-mappings { --tone-color: #d1811f; }

        .rbacdash-metric-label {
            margin: 0;
            font-size: 0.72rem;
            letter-spacing: 0.11em;
            text-transform: uppercase;
            color: var(--dash-muted);
            font-weight: 700;
        }

        .rbacdash-metric-value {
            margin: 6px 0 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: clamp(1.55rem, 2.4vw, 1.95rem);
            color: var(--dash-ink);
            line-height: 1.05;
        }

        .rbacdash-metric-hint {
            margin: 6px 0 0;
            font-size: 0.82rem;
            color: var(--dash-muted);
        }

        .rbacdash-main-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
            gap: 12px;
        }

        .rbacdash-panel {
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            background: var(--dash-surface);
            box-shadow: var(--dash-shadow);
            padding: 15px;
            display: grid;
            gap: 12px;
            animation: dash-enter 0.45s ease both;
        }

        .rbacdash-panel-head {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 10px;
        }

        .rbacdash-panel-head h3 {
            margin: 0;
            font-size: 1.02rem;
            font-family: "Space Grotesk", sans-serif;
            color: var(--dash-ink);
        }

        .rbacdash-panel-head p {
            margin: 4px 0 0;
            font-size: 0.88rem;
            color: var(--dash-muted);
        }

        .rbacdash-highlight {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid #d4e6f8;
            background: #eef6ff;
            color: #1b4f7d;
            padding: 4px 10px;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            white-space: nowrap;
        }

        .rbacdash-bars {
            display: grid;
            gap: 10px;
        }

        .rbacdash-bar-row {
            display: grid;
            grid-template-columns: 100px minmax(0, 1fr) 42px;
            gap: 10px;
            align-items: center;
        }

        .rbacdash-bar-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--dash-ink);
        }

        .rbacdash-track {
            border-radius: 999px;
            height: 9px;
            background: #ebf2fa;
            overflow: hidden;
        }

        .rbacdash-fill {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, var(--tone-color, #0f5f97), rgba(255, 255, 255, 0.62));
            width: var(--bar-size, 0%);
            transition: width .35s ease;
        }

        .rbacdash-share {
            text-align: right;
            font-size: 0.78rem;
            color: var(--dash-muted);
            font-weight: 700;
        }

        .rbacdash-insights {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 9px;
        }

        .rbacdash-insight {
            border: 1px solid #dce6f1;
            border-radius: 11px;
            background: linear-gradient(170deg, #f9fcff 0%, #f2f8ff 100%);
            padding: 8px 10px;
        }

        .rbacdash-insight-label {
            margin: 0;
            font-size: 0.69rem;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #61768c;
            font-weight: 700;
        }

        .rbacdash-insight-value {
            margin: 4px 0 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: 1.1rem;
            color: #123253;
        }

        .rbacdash-link-grid {
            display: grid;
            gap: 9px;
        }

        .rbacdash-link {
            border: 1px solid #d9e6f2;
            border-radius: 12px;
            padding: 10px 11px;
            background: linear-gradient(165deg, #ffffff 0%, #f6fbff 100%);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            transition: transform .16s ease, border-color .2s ease, box-shadow .2s ease;
        }

        .rbacdash-link:hover {
            transform: translateY(-2px);
            border-color: #b7d4ee;
            box-shadow: 0 12px 22px rgba(15, 95, 151, 0.14);
        }

        .rbacdash-link strong {
            display: block;
            font-size: 0.9rem;
            color: #123253;
        }

        .rbacdash-link span {
            display: block;
            margin-top: 2px;
            color: #63778d;
            font-size: 0.8rem;
        }

        .rbacdash-link-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            border-radius: 999px;
            border: 1px solid #cddff1;
            background: #edf5ff;
            color: #204a71;
            font-size: 0.74rem;
            font-weight: 800;
            padding: 4px 9px;
            white-space: nowrap;
        }

        .rbacdash-lists {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .rbacdash-list-card {
            border: 1px solid var(--dash-border);
            border-radius: 14px;
            background: var(--dash-surface);
            box-shadow: var(--dash-shadow);
            padding: 14px;
            display: grid;
            gap: 10px;
            animation: dash-enter 0.45s ease both;
        }

        .rbacdash-list-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .rbacdash-list-head h3 {
            margin: 0;
            font-size: 1rem;
            color: #10273d;
            font-family: "Space Grotesk", sans-serif;
        }

        .rbacdash-list-link {
            color: #155484;
            font-size: 0.79rem;
            font-weight: 800;
        }

        .rbacdash-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 7px;
        }

        .rbacdash-item {
            border: 1px solid #dce8f3;
            border-radius: 10px;
            background: #f7fbff;
            padding: 9px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .rbacdash-item-title {
            margin: 0;
            font-size: 0.87rem;
            color: #132c47;
            font-weight: 700;
        }

        .rbacdash-item-sub {
            margin: 2px 0 0;
            font-size: 0.76rem;
            color: #64788d;
        }

        .rbacdash-code {
            border-radius: 999px;
            border: 1px solid #c9ddf1;
            background: #e9f3ff;
            color: #1f4e7b;
            font-family: "Space Grotesk", sans-serif;
            font-size: 0.73rem;
            font-weight: 700;
            padding: 3px 8px;
            max-width: 170px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .rbacdash-empty {
            margin: 0;
            border-radius: 10px;
            border: 1px dashed #c8d7e8;
            background: #f7fbff;
            color: #5f7286;
            font-size: 0.86rem;
            padding: 11px;
        }

        @keyframes dash-enter {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 1120px) {
            .rbacdash-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .rbacdash-main-grid,
            .rbacdash-lists {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 760px) {
            .rbacdash-hero {
                grid-template-columns: 1fr;
                padding: 18px 16px;
            }

            .rbacdash-hero-actions {
                justify-content: flex-start;
            }

            .rbacdash-metrics,
            .rbacdash-insights {
                grid-template-columns: 1fr;
            }

            .rbacdash-bar-row {
                grid-template-columns: 88px minmax(0, 1fr) 36px;
                gap: 8px;
            }
        }
    </style>

    <section class="rbacdash">
        <section class="rbacdash-hero">
            <div>
                <p class="rbacdash-kicker">Access Governance</p>
                <h1 class="rbacdash-title">Admin RBAC Dashboard</h1>
                <p class="rbacdash-sub">
                    Centralize user access, role design, and permission coverage with a clearer control panel built for daily admin work.
                </p>
                <div class="rbacdash-pill-row">
                    <span class="rbacdash-pill">Oracle Sync</span>
                    <span class="rbacdash-pill">{{ number_format($totalMetric) }} total records</span>
                    <span class="rbacdash-pill">Coverage {{ $mappingCoverage }}%</span>
                </div>
            </div>
            <div class="rbacdash-hero-actions">
                @if($canManageUsers)
                    <a href="{{ route('admin.rbac.users.create') }}" class="btn btn-primary">Create User</a>
                @endif
                @if($canManageRoles)
                    <a href="{{ route('admin.rbac.roles.create') }}" class="btn btn-primary">Create Role</a>
                @endif
                @if($canManagePermissions)
                    <a href="{{ route('admin.rbac.permissions.create') }}" class="btn btn-primary">Create Permission</a>
                @endif
                <a href="{{ route('admin.rbac.users.index') }}" class="btn btn-muted">Manage Access</a>
            </div>
        </section>

        <section class="rbacdash-metrics">
            @foreach($metricCards as $card)
                <article class="rbacdash-metric tone-{{ $card['tone'] }}">
                    <p class="rbacdash-metric-label">{{ $card['label'] }}</p>
                    <p class="rbacdash-metric-value">{{ number_format($card['value']) }}</p>
                    <p class="rbacdash-metric-hint">{{ $card['hint'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="rbacdash-main-grid">
            <article class="rbacdash-panel">
                <div class="rbacdash-panel-head">
                    <div>
                        <h3>Access Distribution</h3>
                        <p>Relative size of each RBAC dataset.</p>
                    </div>
                    <span class="rbacdash-highlight">Live</span>
                </div>
                <div class="rbacdash-bars">
                    @foreach($metricCards as $card)
                        @php
                            $barSize = $card['value'] > 0 ? max(8, (int) round(($card['value'] / $maxMetric) * 100)) : 0;
                            $share = (int) round(($card['value'] / $totalMetric) * 100);
                        @endphp
                        <div class="rbacdash-bar-row" style="--tone-color: {{ $card['color'] }};">
                            <span class="rbacdash-bar-label">{{ $card['label'] }}</span>
                            <div class="rbacdash-track">
                                <span class="rbacdash-fill" style="--bar-size: {{ $barSize }}%;"></span>
                            </div>
                            <span class="rbacdash-share">{{ $share }}%</span>
                        </div>
                    @endforeach
                </div>
                <div class="rbacdash-insights">
                    <article class="rbacdash-insight">
                        <p class="rbacdash-insight-label">Users / Role</p>
                        <p class="rbacdash-insight-value">{{ $avgUsersPerRole }}</p>
                    </article>
                    <article class="rbacdash-insight">
                        <p class="rbacdash-insight-label">Mappings / Role</p>
                        <p class="rbacdash-insight-value">{{ $avgPermissionsPerRole }}</p>
                    </article>
                    <article class="rbacdash-insight">
                        <p class="rbacdash-insight-label">Coverage</p>
                        <p class="rbacdash-insight-value">{{ $mappingCoverage }}%</p>
                    </article>
                </div>
            </article>

            <article class="rbacdash-panel">
                <div class="rbacdash-panel-head">
                    <div>
                        <h3>Quick Access</h3>
                        <p>Jump to core access management modules.</p>
                    </div>
                </div>
                <div class="rbacdash-link-grid">
                    @foreach($quickLinks as $link)
                        <a href="{{ $link['route'] }}" class="rbacdash-link">
                            <div>
                                <strong>{{ $link['label'] }}</strong>
                                <span>{{ $link['description'] }}</span>
                            </div>
                            <span class="rbacdash-link-badge">
                                {{ $link['code'] }} {{ number_format($link['count']) }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </article>
        </section>

        <section class="rbacdash-lists">
            <article class="rbacdash-list-card">
                <div class="rbacdash-list-head">
                    <h3>Roles</h3>
                    <a class="rbacdash-list-link" href="{{ route('admin.rbac.roles.index') }}">View all</a>
                </div>
                @if(($rolesPreview ?? collect())->isEmpty())
                    <p class="rbacdash-empty">No roles found in Oracle.</p>
                @else
                    <ul class="rbacdash-list">
                        @foreach($rolesPreview as $role)
                            <li class="rbacdash-item">
                                <div>
                                    <p class="rbacdash-item-title">{{ $role->role_name }}</p>
                                    <p class="rbacdash-item-sub">Role group</p>
                                </div>
                                <span class="rbacdash-code">ROLE</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>

            <article class="rbacdash-list-card">
                <div class="rbacdash-list-head">
                    <h3>Permissions</h3>
                    <a class="rbacdash-list-link" href="{{ route('admin.rbac.permissions.index') }}">View all</a>
                </div>
                @if(($permissionsPreview ?? collect())->isEmpty())
                    <p class="rbacdash-empty">No permissions found in Oracle.</p>
                @else
                    <ul class="rbacdash-list">
                        @foreach($permissionsPreview as $permission)
                            <li class="rbacdash-item">
                                <div>
                                    <p class="rbacdash-item-title">{{ $permission->title ?: $permission->code }}</p>
                                    <p class="rbacdash-item-sub">Permission code</p>
                                </div>
                                <span class="rbacdash-code">{{ $permission->code }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>

            <article class="rbacdash-list-card">
                <div class="rbacdash-list-head">
                    <h3>Role-Permission Links</h3>
                    <a class="rbacdash-list-link" href="{{ route('admin.rbac.roles.index') }}">View all</a>
                </div>
                @if(($mappingsPreview ?? collect())->isEmpty())
                    <p class="rbacdash-empty">No mappings found in Oracle.</p>
                @else
                    <ul class="rbacdash-list">
                        @foreach($mappingsPreview as $mapping)
                            <li class="rbacdash-item">
                                <div>
                                    <p class="rbacdash-item-title">{{ $mapping->role_name }}</p>
                                    <p class="rbacdash-item-sub">Mapped permission</p>
                                </div>
                                <span class="rbacdash-code">{{ $mapping->code }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>
        </section>
    </section>
@endsection
