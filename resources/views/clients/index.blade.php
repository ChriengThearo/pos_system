@extends('layouts.ecommerce')

@section('title', 'Clients')

@section('content')

    {{-- ══════════════════════════════════════════════════════════════════
         DSS ANALYTICS DASHBOARD
    ══════════════════════════════════════════════════════════════════ --}}
    <section class="card" id="dss-dashboard">
        <div class="actions" style="justify-content: space-between; align-items: flex-start; margin-bottom: 4px;">
            <div>
                <h2 style="margin: 0; font-family: 'Space Grotesk', sans-serif; font-size: 1.15rem;">
                    <i class="bi bi-graph-up-arrow" style="color: var(--primary); margin-right: 6px;"></i>
                    Client Analytics Dashboard
                    <span class="chip" style="margin-left: 8px; font-size: .68rem; background: rgba(0,85,165,.08); color: var(--primary); border-color: rgba(0,85,165,.2);">DSS</span>
                </h2>
                <p class="subtle" style="margin: 4px 0 0; font-size: .84rem;">Decision support insights — top clients, spending trends, and retention metrics.</p>
            </div>
            <div class="actions">
                <button id="dss-refresh-btn" class="btn btn-muted" style="font-size:.8rem; padding:6px 10px;" title="Refresh analytics">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
                <button id="dss-toggle-btn" class="btn btn-muted" style="font-size:.8rem; padding:6px 10px;" title="Collapse dashboard">
                    <i class="bi bi-chevron-up" id="dss-chevron"></i>
                </button>
            </div>
        </div>

        <div id="dss-body">
            {{-- KPI Cards --}}
            <div class="grid" style="grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 14px;" id="dss-kpi-grid">
                {{-- Loading skeletons --}}
                @for($i = 0; $i < 6; $i++)
                    <article class="stat dss-skeleton" style="min-height: 74px;">
                        <div class="dss-skel-line" style="width:55%; height:10px; margin-bottom:10px;"></div>
                        <div class="dss-skel-line" style="width:75%; height:22px;"></div>
                    </article>
                @endfor
            </div>

            {{-- Charts Row --}}
            <div class="grid grid-2" style="margin-top: 14px; gap: 14px;" id="dss-charts-row">

                {{-- Top Clients Bar Chart --}}
                <div class="card" style="padding: 16px; box-shadow: none; border: 1px solid var(--border);">
                    <div class="actions" style="justify-content: space-between; margin-bottom: 10px;">
                        <h3 style="margin: 0; font-size: .95rem;">
                            <i class="bi bi-bar-chart-horizontal-fill" style="color: var(--accent); margin-right:5px;"></i>
                            Top 10 Clients by Spending
                        </h3>
                        <span class="chip" style="font-size:.72rem;">Descending</span>
                    </div>
                    <div id="dss-top-clients-loading" style="text-align:center; padding:40px 0; color:var(--muted); font-size:.9rem;">
                        <i class="bi bi-hourglass-split"></i> Loading chart…
                    </div>
                    <div id="dss-top-clients-empty" style="display:none; text-align:center; padding:40px 0; color:var(--muted); font-size:.9rem;">
                        <i class="bi bi-people"></i> No spending data found.
                    </div>
                    <canvas id="dss-top-clients-chart" style="display:none; max-height:320px;"></canvas>
                </div>

                {{-- Spending History Line Chart --}}
                <div class="card" style="padding: 16px; box-shadow: none; border: 1px solid var(--border);">
                    <div class="actions" style="justify-content: space-between; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                        <h3 style="margin: 0; font-size: .95rem;">
                            <i class="bi bi-graph-up" style="color: var(--success); margin-right:5px;"></i>
                            Spending History
                        </h3>
                        <div class="actions" style="gap:6px; flex-wrap:wrap;">
                            <select id="dss-history-client" style="width:auto; font-size:.78rem; padding:4px 8px; border-radius:8px; min-width:110px;">
                                <option value="">All Clients</option>
                            </select>
                            <div id="dss-range-tabs" style="display:inline-flex; gap:4px; flex-wrap:wrap;">
                                <button class="dss-range-btn active" data-range="30" style="font-size:.75rem; padding:4px 9px; border-radius:8px; border:1px solid var(--border); background:#edf3fa; cursor:pointer; font-weight:700;">30d</button>
                                <button class="dss-range-btn" data-range="7"   style="font-size:.75rem; padding:4px 9px; border-radius:8px; border:1px solid var(--border); background:#fff; cursor:pointer; font-weight:700;">7d</button>
                                <button class="dss-range-btn" data-range="90"  style="font-size:.75rem; padding:4px 9px; border-radius:8px; border:1px solid var(--border); background:#fff; cursor:pointer; font-weight:700;">90d</button>
                                <button class="dss-range-btn" data-range="365" style="font-size:.75rem; padding:4px 9px; border-radius:8px; border:1px solid var(--border); background:#fff; cursor:pointer; font-weight:700;">12m</button>
                                <button class="dss-range-btn" data-range="custom" style="font-size:.75rem; padding:4px 9px; border-radius:8px; border:1px solid var(--border); background:#fff; cursor:pointer; font-weight:700;">Custom</button>
                            </div>
                        </div>
                    </div>
                    <div id="dss-custom-range" style="display:none; gap:8px; margin-bottom:8px;" class="actions">
                        <div>
                            <label style="font-size:.75rem; margin-bottom:3px;">From</label>
                            <input type="date" id="dss-from-date" style="font-size:.8rem; padding:5px 8px; border-radius:8px; width:auto;">
                        </div>
                        <div>
                            <label style="font-size:.75rem; margin-bottom:3px;">To</label>
                            <input type="date" id="dss-to-date" style="font-size:.8rem; padding:5px 8px; border-radius:8px; width:auto;">
                        </div>
                        <div style="display:flex; align-items:flex-end;">
                            <button id="dss-apply-range" class="btn btn-primary" style="font-size:.78rem; padding:5px 10px;">Apply</button>
                        </div>
                    </div>
                    <div id="dss-history-loading" style="text-align:center; padding:40px 0; color:var(--muted); font-size:.9rem;">
                        <i class="bi bi-hourglass-split"></i> Loading chart…
                    </div>
                    <div id="dss-history-empty" style="display:none; text-align:center; padding:40px 0; color:var(--muted); font-size:.9rem;">
                        <i class="bi bi-calendar-x"></i> No data for this period.
                    </div>
                    <canvas id="dss-history-chart" style="display:none; max-height:280px;"></canvas>
                </div>
            </div>

            {{-- DSS Insights Panel --}}
            <div class="card" id="dss-insights-panel" style="padding: 16px; box-shadow: none; border: 1px solid var(--border); margin-top: 14px; display:none;">
                <h3 style="margin: 0 0 12px; font-size: .95rem;">
                    <i class="bi bi-lightbulb-fill" style="color: #f0a500; margin-right:5px;"></i>
                    DSS Insights & Recommendations
                </h3>
                <div class="grid grid-2" style="gap: 10px;" id="dss-insights-body">
                </div>
            </div>
        </div>
    </section>

    {{-- DSS Styles --}}
    <style>
        .dss-skeleton { position: relative; overflow: hidden; }
        .dss-skeleton::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.6), transparent);
            animation: dss-shimmer 1.4s infinite;
        }
        .dss-skel-line {
            border-radius: 6px;
            background: #e2e8f0;
            display: block;
        }
        @keyframes dss-shimmer {
            0%   { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        .dss-kpi-card { transition: transform .15s ease, box-shadow .15s ease; }
        .dss-kpi-card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(15,23,42,.13); }
        .dss-insight-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 12px;
            border-radius: 12px;
            background: var(--surface-soft);
            border: 1px solid var(--border);
        }
        .dss-insight-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }
        .dss-range-btn.active {
            background: var(--primary) !important;
            color: #fff !important;
            border-color: var(--primary) !important;
        }
        @media (max-width: 760px) {
            #dss-charts-row { grid-template-columns: 1fr !important; }
            #dss-kpi-grid   { grid-template-columns: repeat(2, 1fr) !important; }
        }
    </style>

    <section class="card">
        <div class="actions" style="justify-content: space-between; align-items: flex-start;">
            <div>
                <h1 class="headline">Clients & Types</h1>
                <p class="subtle">Everything here is loaded from <code>CLIENTS</code> and <code>CLIENT_TYPE</code>.</p>
            </div>
            <div class="actions">
                <span class="chip">Oracle</span>
                <span class="chip">RBAC</span>
            </div>
        </div>

        @php
            $toDiscountPercent = static function (float $raw): float {
                if ($raw <= 0) {
                    return 0.0;
                }
                if ($raw >= 10) {
                    return $raw;
                }
                if ($raw >= 1) {
                    return $raw * 10;
                }

                return $raw * 100;
            };
            $avgDiscountPercent = isset($metrics['avg_discount_percent'])
                ? (float) $metrics['avg_discount_percent']
                : $toDiscountPercent((float) ($metrics['avg_discount'] ?? 0));
        @endphp
        <div class="grid grid-3" style="margin-top: 14px;">
            <article class="stat">
                <div class="label">Clients</div>
                <div class="value">{{ number_format((int) $metrics['clients']) }}</div>
            </article>
            <article class="stat">
                <div class="label">Client Types</div>
                <div class="value">{{ number_format((int) $metrics['types']) }}</div>
            </article>
            <article class="stat">
                <div class="label">Avg Discount</div>
                <div class="value">{{ number_format($avgDiscountPercent, 2) }}%</div>
            </article>
        </div>

        <form method="GET" action="{{ route('clients.index') }}" class="field-grid" style="margin-top: 14px;">
            <div>
                <label for="q">Search all client columns</label>
                <input id="q" type="text" name="q" value="{{ $q }}" placeholder="e.g. 2567, Bou, Gold, Battambang, 0.05" data-endpoint="{{ route('clients.data') }}">
            </div>
            <div>
                <label for="client_type_filter">Client Type</label>
                <select id="client_type_filter" name="type">
                    <option value="">All types</option>
                    @foreach($clientTypes as $type)
                        <option value="{{ $type->clienttype_id }}" @selected((string) ($selectedType ?? '') === (string) $type->clienttype_id)>
                            {{ $type->type_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="actions" style="align-items: end;">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="{{ route('clients.index') }}" class="btn btn-muted">Reset</a>
            </div>
        </form>
    </section>

    <section class="card" style="margin-top: 14px;">
        <div class="actions" style="justify-content: space-between; align-items: center;">
            <div class="actions">
                <button type="button" class="btn btn-primary" id="show-client-list">Client List</button>
                @if($canManageClients)
                    <button type="button" class="btn btn-muted" id="show-client-add">Add Client</button>
                @endif
                <button type="button" class="btn btn-muted" id="show-client-types">Client Types</button>
            </div>
            <span class="subtle">Use the buttons to switch between client sections.</span>
        </div>
    </section>

    <div class="grid">
        <section class="card" id="client-list-panel">
            <div class="actions" style="justify-content: space-between;">
                <h2 style="margin-top: 0;">Client List</h2>
                <span class="chip"><span id="clients-total">{{ $clients->total() }}</span> total</span>
            </div>
            <div class="table-wrap" style="margin-top: 12px;">
                <table>
                    <thead>
                    <tr>
                        <th>Client No</th>
                        <th>Client Name</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Type</th>
                        <th>Discount</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody id="clients-table-body">
                @forelse($clients as $client)
                    <tr>
                        @php
                            $discountValue = (float) ($client->discount ?? 0);
                            $discountPercent = $toDiscountPercent($discountValue);
                        @endphp
                        <td>
                            <strong>#{{ $client->client_no }}</strong>
                        </td>
                        <td>
                            {{ $client->client_name ?: 'N/A' }}
                        </td>
                        <td>
                            <div>{{ $client->phone ?: 'N/A' }}</div>
                            <div class="subtle">{{ $client->city ?: 'N/A' }}</div>
                        </td>
                        <td>
                            {{ $client->address ?: 'N/A' }}
                        </td>
                        <td>
                            {{ $client->type_name ?: 'N/A' }}
                            @if(($client->type_name ?? null) !== null && (string) $client->type_name !== '')
                                <div class="subtle">
                                    {{ number_format((int) ($typeClientCounts[(string) ($client->client_type ?? '')] ?? 0)) }} clients
                                </div>
                            @endif
                        </td>
                        <td>
                            {{ number_format($discountPercent, 2) }}%
                        </td>
                        <td>
                            @if($canManageClients)
                                <button
                                    type="button"
                                    class="btn btn-muted js-client-detail"
                                    data-client-no="{{ (int) $client->client_no }}"
                                    data-client-name="{{ (string) ($client->client_name ?? '') }}"
                                    data-phone="{{ (string) ($client->phone ?? '') }}"
                                    data-city="{{ (string) ($client->city ?? '') }}"
                                    data-address="{{ (string) ($client->address ?? '') }}"
                                    data-client-type="{{ (string) ($client->client_type ?? '') }}"
                                    data-type-name="{{ (string) ($client->type_name ?? '') }}"
                                    data-discount="{{ (float) ($client->discount ?? 0) }}"
                                >
                                    Detail
                                </button>
                            @else
                                <span class="subtle">Read only</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="subtle" id="clients-empty">No clients found.</td>
                    </tr>
                @endforelse
                    </tbody>
                </table>
            </div>
            <div class="pager" style="margin-top: 12px;">
                {{ $clients->links('pagination.orbit') }}
            </div>
        </section>

        @if($canManageClients)
            <section class="card" id="client-add-panel" style="display: none;">
                <h2 style="margin-top: 0;">Add Client</h2>
                <p class="subtle">Create a new client record.</p>

                <form method="POST" action="{{ route('clients.store') }}" class="field-grid" style="margin-top: 12px;">
                    @csrf
                    <input type="hidden" name="create_client" value="1">
                    <div>
                        <label for="new-client-name">Client Name</label>
                        <input id="new-client-name" name="client_name" type="text" value="{{ old('client_name') }}" required>
                    </div>
                    <div>
                        <label for="new-client-phone">Phone</label>
                        <input id="new-client-phone" name="phone" type="text" value="{{ old('phone') }}" required>
                    </div>
                    <div>
                        <label for="new-client-city">City</label>
                        <input id="new-client-city" name="city" type="text" value="{{ old('city') }}">
                    </div>
                    <div>
                        <label for="new-client-address">Address</label>
                        <input id="new-client-address" name="address" type="text" value="{{ old('address') }}">
                    </div>
                    <div>
                        <label for="new-client-type">Client Type</label>
                        <select id="new-client-type" name="client_type">
                            <option value="">None</option>
                            @foreach($clientTypes as $type)
                                <option value="{{ $type->clienttype_id }}" @selected((string) old('client_type') === (string) $type->clienttype_id)>
                                    {{ $type->type_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="new-client-discount">Discount (%)</label>
                        <input id="new-client-discount" name="discount" type="number" step="0.01" min="0" value="{{ old('discount') }}">
                    </div>
                    <div class="actions" style="align-items: end;">
                        <button type="submit" class="btn btn-primary">Create Client</button>
                    </div>
                </form>
            </section>
        @endif

        <section class="card" id="client-types-panel" style="display: none;">
            <h2 style="margin-top: 0;">Client Types</h2>
            <p class="subtle">Create or update client type discounts.</p>

            @if($canManageTypes)
                <form method="POST" action="{{ route('client-types.create') }}" class="field-grid" style="margin-top: 12px;">
                    @csrf
                    <div>
                        <label for="type_name">Type name</label>
                        <input id="type_name" name="type_name" type="text" placeholder="e.g. VIP" required>
                    </div>
                    <div>
                        <label for="discount_rate">Discount rate</label>
                        <input id="discount_rate" name="discount_rate" type="number" step="0.01" min="0" required>
                    </div>
                    <div class="actions" style="align-items: end;">
                        <button type="submit" class="btn btn-primary">Add Type</button>
                    </div>
                </form>
            @endif

            <div class="table-wrap" style="margin-top: 12px;">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Discount</th>
                        <th>Clients</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                @forelse($clientTypes as $type)
                    <tr>
                        <td>{{ $type->clienttype_id }}</td>
                        <td>
                            @if($canManageTypes)
                                <input type="text" form="type-{{ $type->clienttype_id }}" name="type_name" value="{{ $type->type_name }}">
                            @else
                                {{ $type->type_name }}
                            @endif
                        </td>
                        <td>
                            @if($canManageTypes)
                                <input type="number" step="0.01" min="0" form="type-{{ $type->clienttype_id }}" name="discount_rate" value="{{ $type->discount_rate }}">
                            @else
                                {{ $type->discount_rate }}
                            @endif
                        </td>
                        <td>{{ number_format((int) ($typeClientCounts[(string) $type->clienttype_id] ?? 0)) }}</td>
                        <td>
                            @if($canManageTypes)
                                <form id="type-{{ $type->clienttype_id }}" method="POST" action="{{ route('client-types.update', ['clientTypeId' => (int) $type->clienttype_id]) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-muted">Save</button>
                                </form>
                            @else
                                <span class="subtle">Read only</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="subtle">No client types found.</td>
                    </tr>
                @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    @if($canManageClients)
        <div id="client-detail-modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); z-index: 50; padding: 24px; overflow-y: auto;">
            <div class="card" role="dialog" aria-modal="true" aria-labelledby="client-detail-title" style="max-width: 780px; margin: 0 auto;">
                <div class="actions" style="justify-content: space-between;">
                    <div>
                        <h2 id="client-detail-title" style="margin-top: 0;">Client Detail</h2>
                        <p class="subtle" style="margin-top: 6px;">Review and update client information.</p>
                    </div>
                    <span class="chip" id="detail-client-no-chip">#</span>
                </div>

                <form
                    id="client-detail-form"
                    method="POST"
                    data-update-template="{{ route('clients.update', ['clientNo' => '__CLIENT__']) }}"
                    style="margin-top: 12px;"
                >
                    @csrf
                    @method('PATCH')
                    <div class="grid grid-2">
                        <div>
                            <label for="detail-client-no">Client No</label>
                            <input id="detail-client-no" type="text" readonly>
                        </div>
                        <div>
                            <label for="detail-client-name">Client Name</label>
                            <input id="detail-client-name" name="client_name" type="text" required>
                        </div>
                        <div>
                            <label for="detail-client-phone">Phone</label>
                            <input id="detail-client-phone" name="phone" type="text" required>
                        </div>
                        <div>
                            <label for="detail-client-city">City</label>
                            <input id="detail-client-city" name="city" type="text">
                        </div>
                        <div>
                            <label for="detail-client-address">Address</label>
                            <input id="detail-client-address" name="address" type="text">
                        </div>
                        <div>
                            <label for="detail-client-type">Client Type</label>
                            <select id="detail-client-type" name="client_type">
                                <option value="">None</option>
                                @foreach($clientTypes as $type)
                                    <option value="{{ $type->clienttype_id }}" data-discount="{{ $type->discount_rate }}">
                                        {{ $type->type_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="detail-client-discount">Discount (%)</label>
                            <input id="detail-client-discount" name="discount" type="number" step="0.01" min="0">
                        </div>
                    </div>

                    <div class="actions" style="margin-top: 12px;">
                        <button type="submit" class="btn btn-primary">Save Change</button>
                        <button type="button" class="btn btn-muted" id="cancel-client-detail">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const listBtn = document.getElementById('show-client-list');
            const typesBtn = document.getElementById('show-client-types');
            const addBtn = document.getElementById('show-client-add');
            const listPanel = document.getElementById('client-list-panel');
            const addPanel = document.getElementById('client-add-panel');
            const typesPanel = document.getElementById('client-types-panel');
            const searchInput = document.getElementById('q');
            const tableBody = document.getElementById('clients-table-body');
            const totalEl = document.getElementById('clients-total');
            const typeFilter = document.getElementById('client_type_filter');
            const canManageClients = @json($canManageClients);
            const typeClientCounts = @json($typeClientCounts ?? []);
            const openAddByError = @json((bool) old('create_client')) && @json(
                $errors->has('client_name') ||
                $errors->has('phone') ||
                $errors->has('address') ||
                $errors->has('city') ||
                $errors->has('client_type') ||
                $errors->has('discount')
            );

            const detailModal = document.getElementById('client-detail-modal');
            const detailForm = document.getElementById('client-detail-form');
            const detailClientNoChip = document.getElementById('detail-client-no-chip');
            const detailClientNo = document.getElementById('detail-client-no');
            const detailClientName = document.getElementById('detail-client-name');
            const detailClientPhone = document.getElementById('detail-client-phone');
            const detailClientCity = document.getElementById('detail-client-city');
            const detailClientAddress = document.getElementById('detail-client-address');
            const detailClientType = document.getElementById('detail-client-type');
            const detailClientDiscount = document.getElementById('detail-client-discount');
            const cancelClientDetail = document.getElementById('cancel-client-detail');

            if (listBtn && typesBtn && listPanel && typesPanel) {
                const activate = (target) => {
                    const showList = target === 'list';
                    const showAdd = target === 'add';
                    const showTypes = target === 'types';
                    listPanel.style.display = showList ? '' : 'none';
                    if (addPanel) addPanel.style.display = showAdd ? '' : 'none';
                    typesPanel.style.display = showTypes ? '' : 'none';
                    listBtn.classList.toggle('btn-primary', showList);
                    listBtn.classList.toggle('btn-muted', !showList);
                    if (addBtn) {
                        addBtn.classList.toggle('btn-primary', showAdd);
                        addBtn.classList.toggle('btn-muted', !showAdd);
                    }
                    typesBtn.classList.toggle('btn-primary', showTypes);
                    typesBtn.classList.toggle('btn-muted', !showTypes);
                };

                if (openAddByError && addBtn) {
                    activate('add');
                } else {
                    activate('list');
                }

                listBtn.addEventListener('click', () => activate('list'));
                if (addBtn) {
                    addBtn.addEventListener('click', () => activate('add'));
                }
                typesBtn.addEventListener('click', () => activate('types'));
            }

            const escapeHtml = (value) => {
                const text = String(value ?? '');
                return text
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            };

            const normalizeDiscountPercent = (rawValue) => {
                const raw = Number(rawValue || 0);
                if (!Number.isFinite(raw) || raw <= 0) {
                    return 0;
                }
                if (raw >= 10) {
                    return raw;
                }
                if (raw >= 1) {
                    return raw * 10;
                }
                return raw * 100;
            };

            const readClientFromButton = (button) => {
                return {
                    client_no: button.dataset.clientNo ?? '',
                    client_name: button.dataset.clientName ?? '',
                    phone: button.dataset.phone ?? '',
                    city: button.dataset.city ?? '',
                    address: button.dataset.address ?? '',
                    client_type: button.dataset.clientType ?? '',
                    discount: button.dataset.discount ?? '',
                };
            };

            const openClientDetail = (client) => {
                if (!detailModal || !detailForm || !detailClientNo || !detailClientNoChip || !detailClientName || !detailClientPhone || !detailClientCity || !detailClientAddress || !detailClientType || !detailClientDiscount) {
                    return;
                }

                const clientNo = String(client.client_no ?? '').trim();
                const actionTemplate = detailForm.dataset.updateTemplate ?? '';
                if (!clientNo || actionTemplate === '') {
                    return;
                }

                detailForm.action = actionTemplate.replace('__CLIENT__', clientNo);
                detailClientNo.value = clientNo;
                detailClientNoChip.textContent = `#${clientNo}`;
                detailClientName.value = String(client.client_name ?? '');
                detailClientPhone.value = String(client.phone ?? '');
                detailClientCity.value = String(client.city ?? '');
                detailClientAddress.value = String(client.address ?? '');
                detailClientType.value = String(client.client_type ?? '');
                detailClientDiscount.value = client.discount !== '' ? Number(client.discount || 0).toFixed(2) : '';
                detailModal.style.display = 'block';
            };

            const closeClientDetail = () => {
                if (detailModal) {
                    detailModal.style.display = 'none';
                }
            };

            if (detailClientType && detailClientDiscount) {
                detailClientType.addEventListener('change', () => {
                    const selectedOption = detailClientType.options[detailClientType.selectedIndex];
                    const discount = selectedOption?.dataset?.discount;
                    if (discount === undefined) {
                        return;
                    }
                    detailClientDiscount.value = discount === '' ? '' : Number(discount).toFixed(2);
                });
            }

            if (cancelClientDetail) {
                cancelClientDetail.addEventListener('click', closeClientDetail);
            }

            if (detailModal) {
                detailModal.addEventListener('click', (event) => {
                    if (event.target === detailModal) {
                        closeClientDetail();
                    }
                });
            }

            if (tableBody) {
                tableBody.addEventListener('click', (event) => {
                    const target = event.target;
                    if (!(target instanceof HTMLElement)) {
                        return;
                    }
                    const button = target.closest('.js-client-detail');
                    if (!button) {
                        return;
                    }
                    openClientDetail(readClientFromButton(button));
                });
            }

            if (!searchInput || !tableBody || !totalEl) {
                return;
            }

            let debounceId = null;

            const renderRow = (client) => {
                const clientNo = String(client.client_no ?? '');
                const clientName = String(client.client_name ?? '');
                const phone = String(client.phone ?? '');
                const city = String(client.city ?? '');
                const address = String(client.address ?? '');
                const clientType = String(client.client_type ?? '');
                const typeName = String(client.type_name ?? '');
                const discount = Number(client.discount ?? 0);
                const discountPercent = normalizeDiscountPercent(discount);
                const typeCount = Number(typeClientCounts[clientType] ?? 0);
                const typeCell = typeName
                    ? `${escapeHtml(typeName)}<div class="subtle">${escapeHtml(typeCount.toLocaleString())} clients</div>`
                    : 'N/A';
                const actionCell = canManageClients
                    ? `<button
                            type="button"
                            class="btn btn-muted js-client-detail"
                            data-client-no="${escapeHtml(clientNo)}"
                            data-client-name="${escapeHtml(clientName)}"
                            data-phone="${escapeHtml(phone)}"
                            data-city="${escapeHtml(city)}"
                            data-address="${escapeHtml(address)}"
                            data-client-type="${escapeHtml(clientType)}"
                            data-discount="${escapeHtml(String(discount))}"
                        >Detail</button>`
                    : '<span class="subtle">Read only</span>';
                if (!canManageClients) {
                    return `
                        <tr>
                            <td><strong>#${escapeHtml(clientNo)}</strong></td>
                            <td>${escapeHtml(clientName || 'N/A')}</td>
                            <td>
                                <div>${escapeHtml(phone || 'N/A')}</div>
                                <div class="subtle">${escapeHtml(city || 'N/A')}</div>
                            </td>
                            <td>${escapeHtml(address || 'N/A')}</td>
                            <td>${typeCell}</td>
                            <td>${escapeHtml(discountPercent.toFixed(2))}%</td>
                            <td><span class="subtle">Read only</span></td>
                        </tr>
                    `;
                }

                return `
                    <tr>
                        <td><strong>#${escapeHtml(clientNo)}</strong></td>
                        <td>${escapeHtml(clientName || 'N/A')}</td>
                        <td>
                            <div>${escapeHtml(phone || 'N/A')}</div>
                            <div class="subtle">${escapeHtml(city || 'N/A')}</div>
                        </td>
                        <td>${escapeHtml(address || 'N/A')}</td>
                        <td>${typeCell}</td>
                        <td>${escapeHtml(discountPercent.toFixed(2))}%</td>
                        <td>${actionCell}</td>
                    </tr>
                `;
            };

            const renderClients = (clients) => {
                if (!clients || clients.length === 0) {
                    tableBody.innerHTML = '<tr><td colspan="7" class="subtle">No clients found.</td></tr>';
                    return;
                }
                tableBody.innerHTML = clients.map(renderRow).join('');
            };

            const fetchClients = () => {
                const endpoint = searchInput.dataset.endpoint;
                if (!endpoint) return;
                const q = searchInput.value.trim();
                const params = new URLSearchParams();
                if (q !== '') params.set('q', q);
                if (typeFilter && typeFilter.value) {
                    params.set('type', typeFilter.value);
                }
                const url = params.toString() ? `${endpoint}?${params.toString()}` : endpoint;

                fetch(url, { headers: { 'Accept': 'application/json' } })
                    .then((res) => res.json())
                    .then((data) => {
                        totalEl.textContent = data.count ?? 0;
                        renderClients(data.clients || []);
                    })
                    .catch(() => {
                        // ignore fetch errors for now
                    });
            };

            searchInput.addEventListener('input', () => {
                if (debounceId) {
                    clearTimeout(debounceId);
                }
                debounceId = setTimeout(fetchClients, 300);
            });

            if (typeFilter) {
                typeFilter.addEventListener('change', () => {
                    if (debounceId) {
                        clearTimeout(debounceId);
                    }
                    debounceId = setTimeout(fetchClients, 150);
                });
            }
        });
    </script>

    {{-- ══════════════════════════════════════════════════════════════════
         DSS JAVASCRIPT  (Chart.js loaded from CDN)
    ══════════════════════════════════════════════════════════════════ --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        'use strict';

        const DSS_ENDPOINT = @json(route('clients.dss'));

        // ── Helpers ──────────────────────────────────────────────────────
        const fmt = (n) => new Intl.NumberFormat('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(n);
        const fmtShort = (n) => {
            if (n >= 1_000_000) return '$' + (n / 1_000_000).toFixed(1) + 'M';
            if (n >= 1_000)     return '$' + (n / 1_000).toFixed(1) + 'K';
            return '$' + fmt(n);
        };

        let topClientsChart = null;
        let historyChart    = null;
        let topClientsData  = [];

        // ── Toggle collapse ───────────────────────────────────────────────
        const dssBody    = document.getElementById('dss-body');
        const chevron    = document.getElementById('dss-chevron');
        const toggleBtn  = document.getElementById('dss-toggle-btn');
        let collapsed = false;
        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                collapsed = !collapsed;
                dssBody.style.display = collapsed ? 'none' : '';
                chevron.className = collapsed ? 'bi bi-chevron-down' : 'bi bi-chevron-up';
            });
        }

        // ── Refresh button ────────────────────────────────────────────────
        const refreshBtn = document.getElementById('dss-refresh-btn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                loadSummary();
                loadTopClients();
                loadHistory();
            });
        }

        // ── Load Summary KPIs ─────────────────────────────────────────────
        function loadSummary() {
            const kpiGrid = document.getElementById('dss-kpi-grid');
            if (!kpiGrid) return;

            // Show skeletons
            kpiGrid.innerHTML = Array.from({length: 6}).map(() => `
                <article class="stat dss-skeleton" style="min-height:74px;">
                    <div class="dss-skel-line" style="width:55%;height:10px;margin-bottom:10px;"></div>
                    <div class="dss-skel-line" style="width:75%;height:22px;"></div>
                </article>`).join('');

            fetch(`${DSS_ENDPOINT}?type=summary`, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(d => renderKPIs(d, kpiGrid))
                .catch(() => {
                    kpiGrid.innerHTML = `<div style="grid-column:1/-1; text-align:center; color:var(--muted); padding:20px;">
                        <i class="bi bi-exclamation-triangle"></i> Failed to load analytics.
                    </div>`;
                });
        }

        function renderKPIs(d, kpiGrid) {
            const cards = [
                {
                    label: 'Highest Spending Client',
                    value: d.highest_spending_client ?? 'N/A',
                    sub: '$' + fmt(d.highest_spending_amount ?? 0),
                    icon: 'bi-trophy-fill', color: '#e36414',
                },
                {
                    label: 'Avg Customer Spending',
                    value: '$' + fmt(d.avg_spending ?? 0),
                    sub: 'per active client',
                    icon: 'bi-cash-coin', color: '#0055a5',
                },
                {
                    label: 'Active Clients',
                    value: (d.active_clients ?? 0).toLocaleString(),
                    sub: 'with at least 1 invoice',
                    icon: 'bi-people-fill', color: '#1d7a4f',
                },
                {
                    label: 'New Clients This Month',
                    value: (d.new_clients_month ?? 0).toLocaleString(),
                    sub: 'first invoice this month',
                    icon: 'bi-person-plus-fill', color: '#6f42c1',
                },
                {
                    label: 'Repeat Customer Rate',
                    value: (d.repeat_rate ?? 0).toFixed(1) + '%',
                    sub: 'clients with >1 invoice',
                    icon: 'bi-arrow-repeat', color: '#0d6efd',
                },
                {
                    label: 'Customer Lifetime Value',
                    value: '$' + fmt(d.clv ?? 0),
                    sub: 'avg total spend per client',
                    icon: 'bi-gem', color: '#d63384',
                },
            ];

            kpiGrid.innerHTML = cards.map(c => `
                <article class="stat dss-kpi-card" style="cursor:default;">
                    <div class="label" style="display:flex; align-items:center; gap:6px;">
                        <i class="bi ${c.icon}" style="color:${c.color};"></i> ${c.label}
                    </div>
                    <div class="value" style="font-size:1.15rem; margin-top:4px; word-break:break-word;">${c.value}</div>
                    <div style="font-size:.75rem; color:var(--muted); margin-top:3px;">${c.sub}</div>
                </article>`).join('');

            // Render insights panel
            renderInsights(d);
        }

        function renderInsights(d) {
            const panel = document.getElementById('dss-insights-panel');
            const body  = document.getElementById('dss-insights-body');
            if (!panel || !body) return;

            const insights = [];

            const top5 = d.top5 ?? [];
            if (top5.length > 0) {
                const grandTotal = d.grand_total ?? 0;
                const top5Total  = top5.reduce((s, c) => s + (c.revenue ?? 0), 0);
                const pct        = grandTotal > 0 ? ((top5Total / grandTotal) * 100).toFixed(1) : '0.0';
                insights.push({
                    icon: 'bi-bar-chart-steps', color: '#e36414', bg: 'rgba(227,100,20,.1)',
                    title: 'Revenue Concentration',
                    text: `Top 5 clients contribute <strong>${pct}%</strong> of total revenue ($${fmt(top5Total)}). ` +
                        top5.map(c => `<span style="font-weight:700;">${c.client_name}</span>`).join(', ') + '.',
                });
            }

            const repeatRate = d.repeat_rate ?? 0;
            if (repeatRate < 30) {
                insights.push({
                    icon: 'bi-exclamation-triangle-fill', color: '#a32222', bg: 'rgba(163,34,34,.1)',
                    title: 'Low Retention Alert',
                    text: `Only <strong>${repeatRate.toFixed(1)}%</strong> of clients are repeat buyers. Consider loyalty programs or follow-up campaigns to improve retention.`,
                });
            } else {
                insights.push({
                    icon: 'bi-check-circle-fill', color: '#1d7a4f', bg: 'rgba(29,122,79,.1)',
                    title: 'Good Retention',
                    text: `<strong>${repeatRate.toFixed(1)}%</strong> repeat customer rate — strong client loyalty. Focus on growing new client acquisition.`,
                });
            }

            const avgSpending = d.avg_spending ?? 0;
            const highestAmt  = d.highest_spending_amount ?? 0;
            if (highestAmt > 0 && avgSpending > 0) {
                const ratio = (highestAmt / avgSpending).toFixed(1);
                insights.push({
                    icon: 'bi-lightning-charge-fill', color: '#6f42c1', bg: 'rgba(111,66,193,.1)',
                    title: 'Spending Disparity',
                    text: `Top client spends <strong>${ratio}×</strong> the average. Consider tiered VIP pricing or exclusive offers for high-value clients.`,
                });
            }

            const newClients = d.new_clients_month ?? 0;
            insights.push({
                icon: 'bi-person-plus-fill', color: '#0055a5', bg: 'rgba(0,85,165,.1)',
                title: 'Acquisition This Month',
                text: newClients > 0
                    ? `<strong>${newClients}</strong> new client${newClients !== 1 ? 's' : ''} placed their first order this month.`
                    : 'No new clients placed their first order this month. Review acquisition channels.',
            });

            body.innerHTML = insights.map(ins => `
                <div class="dss-insight-item">
                    <div class="dss-insight-icon" style="background:${ins.bg}; color:${ins.color};">
                        <i class="bi ${ins.icon}"></i>
                    </div>
                    <div>
                        <div style="font-weight:700; font-size:.88rem; margin-bottom:3px;">${ins.title}</div>
                        <div style="font-size:.83rem; color:var(--muted); line-height:1.5;">${ins.text}</div>
                    </div>
                </div>`).join('');

            panel.style.display = '';
        }

        // ── Load Top Clients Bar Chart ────────────────────────────────────
        function loadTopClients() {
            const loading = document.getElementById('dss-top-clients-loading');
            const empty   = document.getElementById('dss-top-clients-empty');
            const canvas  = document.getElementById('dss-top-clients-chart');
            if (!canvas) return;
            if (loading) loading.style.display = '';
            if (empty)   empty.style.display   = 'none';
            canvas.style.display = 'none';

            fetch(`${DSS_ENDPOINT}?type=top_clients`, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(d => {
                    topClientsData = d.top_clients ?? [];
                    if (loading) loading.style.display = 'none';
                    if (topClientsData.length === 0) {
                        if (empty) empty.style.display = '';
                        return;
                    }
                    canvas.style.display = '';
                    renderTopClientsChart(topClientsData);

                    // Populate client filter dropdown for history chart
                    const sel = document.getElementById('dss-history-client');
                    if (sel) {
                        topClientsData.forEach(c => {
                            const opt = document.createElement('option');
                            opt.value = c.client_no;
                            opt.textContent = c.client_name;
                            sel.appendChild(opt);
                        });
                    }
                })
                .catch(() => {
                    if (loading) loading.style.display = 'none';
                    if (empty)   empty.style.display   = '';
                });
        }

        function renderTopClientsChart(data) {
            const canvas = document.getElementById('dss-top-clients-chart');
            if (!canvas) return;
            if (topClientsChart) { topClientsChart.destroy(); topClientsChart = null; }

            const labels   = data.map(c => c.client_name);
            const values   = data.map(c => c.total_spending);
            const maxVal   = Math.max(...values);
            const barColors = values.map(v => {
                const ratio = v / maxVal;
                if (ratio > 0.8) return 'rgba(0,85,165,0.85)';
                if (ratio > 0.5) return 'rgba(0,85,165,0.65)';
                return 'rgba(0,85,165,0.40)';
            });

            topClientsChart = new Chart(canvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Total Spending ($)',
                        data: values,
                        backgroundColor: barColors,
                        borderColor: 'rgba(0,85,165,0.9)',
                        borderWidth: 1,
                        borderRadius: 6,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: ctx => ` $${fmt(ctx.parsed.x)}  |  ${data[ctx.dataIndex]?.order_count ?? 0} orders`,
                            },
                        },
                    },
                    scales: {
                        x: {
                            ticks: { callback: v => fmtShort(v) },
                            grid: { color: 'rgba(0,0,0,.05)' },
                        },
                        y: {
                            ticks: { font: { size: 11 } },
                            grid: { display: false },
                        },
                    },
                },
            });
        }

        // ── Load Spending History Line Chart ──────────────────────────────
        let currentRange    = '30';
        let currentClientNo = '';

        function loadHistory(range, clientNo, from, to) {
            range    = range    ?? currentRange;
            clientNo = clientNo ?? currentClientNo;

            currentRange    = range;
            currentClientNo = clientNo;

            const loading = document.getElementById('dss-history-loading');
            const empty   = document.getElementById('dss-history-empty');
            const canvas  = document.getElementById('dss-history-chart');
            if (!canvas) return;
            if (loading) loading.style.display = '';
            if (empty)   empty.style.display   = 'none';
            canvas.style.display = 'none';

            let url = `${DSS_ENDPOINT}?type=history&range=${encodeURIComponent(range)}`;
            if (clientNo) url += `&client_no=${encodeURIComponent(clientNo)}`;
            if (from)     url += `&from=${encodeURIComponent(from)}`;
            if (to)       url += `&to=${encodeURIComponent(to)}`;

            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(d => {
                    const rows = d.history ?? [];
                    if (loading) loading.style.display = 'none';
                    if (rows.length === 0) {
                        if (empty) empty.style.display = '';
                        return;
                    }
                    canvas.style.display = '';
                    renderHistoryChart(rows);
                })
                .catch(() => {
                    if (loading) loading.style.display = 'none';
                    if (empty)   empty.style.display   = '';
                });
        }

        function renderHistoryChart(rows) {
            const canvas = document.getElementById('dss-history-chart');
            if (!canvas) return;
            if (historyChart) { historyChart.destroy(); historyChart = null; }

            const labels  = rows.map(r => r.date);
            const amounts = rows.map(r => r.total_spending);
            const orders  = rows.map(r => r.order_count);

            historyChart = new Chart(canvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        {
                            label: 'Total Spending ($)',
                            data: amounts,
                            borderColor: 'rgba(0,85,165,0.85)',
                            backgroundColor: 'rgba(0,85,165,0.08)',
                            fill: true,
                            tension: 0.35,
                            pointRadius: rows.length <= 14 ? 4 : 2,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Orders',
                            data: orders,
                            borderColor: 'rgba(227,100,20,0.8)',
                            backgroundColor: 'transparent',
                            fill: false,
                            tension: 0.35,
                            pointRadius: rows.length <= 14 ? 3 : 2,
                            borderDash: [4, 3],
                            yAxisID: 'y2',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: ctx => ctx.dataset.yAxisID === 'y'
                                    ? ` Spending: $${fmt(ctx.parsed.y)}`
                                    : ` Orders: ${ctx.parsed.y}`,
                            },
                        },
                    },
                    scales: {
                        y: {
                            position: 'left',
                            ticks: { callback: v => fmtShort(v), font: { size: 11 } },
                            grid: { color: 'rgba(0,0,0,.05)' },
                        },
                        y2: {
                            position: 'right',
                            ticks: { font: { size: 11 } },
                            grid: { display: false },
                        },
                        x: {
                            ticks: {
                                maxTicksLimit: 10,
                                font: { size: 10 },
                                maxRotation: 30,
                            },
                            grid: { display: false },
                        },
                    },
                },
            });
        }

        // ── Range tab buttons ─────────────────────────────────────────────
        document.querySelectorAll('.dss-range-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.dss-range-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                const range = btn.dataset.range;
                const customDiv = document.getElementById('dss-custom-range');
                if (range === 'custom') {
                    if (customDiv) customDiv.style.display = 'flex';
                } else {
                    if (customDiv) customDiv.style.display = 'none';
                    loadHistory(range, currentClientNo);
                }
            });
        });

        const applyRangeBtn = document.getElementById('dss-apply-range');
        if (applyRangeBtn) {
            applyRangeBtn.addEventListener('click', () => {
                const from = document.getElementById('dss-from-date')?.value ?? '';
                const to   = document.getElementById('dss-to-date')?.value ?? '';
                loadHistory('custom', currentClientNo, from, to);
            });
        }

        // ── Client filter for history chart ───────────────────────────────
        const historyClientSel = document.getElementById('dss-history-client');
        if (historyClientSel) {
            historyClientSel.addEventListener('change', () => {
                loadHistory(currentRange, historyClientSel.value);
            });
        }

        // ── Bootstrap all on page load ────────────────────────────────────
        loadSummary();
        loadTopClients();
        loadHistory();
    })();
    </script>
@endsection
