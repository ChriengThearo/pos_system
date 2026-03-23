@extends('layouts.ecommerce')

@section('title', 'Clients')

@section('content')
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
@endsection
