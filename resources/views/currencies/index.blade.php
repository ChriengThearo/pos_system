@extends('layouts.ecommerce')

@section('title', 'Currencies')

@section('content')
    <section class="card">
        <div class="actions" style="justify-content: space-between; align-items: flex-start;">
            <div>
                <h1 class="headline">Currencies</h1>
                <p class="subtle">Work with records from <code>CURRENCIES</code>.</p>
            </div>
            <div class="actions">
                <span class="chip">Oracle</span>
                <span class="chip">RBAC</span>
            </div>
        </div>

        @if(!($schema['exists'] ?? false))
            <div class="flash error" style="margin-top: 12px;">
                CURRENCIES table was not found in the Oracle schema.
            </div>
        @else
            <form method="GET" action="{{ route('currencies.index') }}" class="field-grid" style="margin-top: 14px;">
                <div>
                    <label for="q">Search currencies</label>
                    <input id="q" type="text" name="q" value="{{ $q }}" placeholder="e.g. USD, KHR, 4100">
                </div>
                <div class="actions" style="align-items: end;">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="{{ route('currencies.index') }}" class="btn btn-muted">Reset</a>
                </div>
            </form>
        @endif
    </section>

    @if($schema['exists'] ?? false)
        <section class="card">
            <div class="grid grid-3">
                <article class="stat">
                    <div class="label">Total</div>
                    <div class="value">{{ number_format((int) ($metrics['total'] ?? 0)) }}</div>
                </article>
                <article class="stat">
                    <div class="label">Active</div>
                    <div class="value">{{ number_format((int) ($metrics['active'] ?? 0)) }}</div>
                </article>
                <article class="stat">
                    <div class="label">Avg Rate</div>
                    <div class="value">{{ number_format((float) ($metrics['avg_rate'] ?? 0), 2) }}</div>
                </article>
            </div>
        </section>

        <section class="card">
            <div class="actions" style="justify-content: space-between; align-items: center;">
                <div class="actions">
                    <button type="button" class="btn btn-primary" id="show-currency-list">Currency List</button>
                    @if($canManageCurrencies)
                        <button type="button" class="btn btn-muted" id="show-currency-add">Add Currency</button>
                    @endif
                </div>
                <span class="subtle">Use the buttons to switch between list and add forms.</span>
            </div>
        </section>

        <section class="card" id="currency-list-panel">
            <div class="actions" style="justify-content: space-between;">
                <h2 style="margin-top: 0;">Currency List</h2>
                <span class="chip">{{ $currencies->total() }} total</span>
            </div>

            @php
                $columnCount = 2
                    + ($schema['code'] ? 1 : 0)
                    + ($schema['name'] ? 1 : 0)
                    + ($schema['symbol'] ? 1 : 0)
                    + ($schema['rate'] ? 1 : 0)
                    + ($schema['status'] ? 1 : 0);
            @endphp
            <div class="table-wrap" style="margin-top: 12px;">
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        @if($schema['code']) <th>Code</th> @endif
                        @if($schema['name']) <th>Name</th> @endif
                        @if($schema['symbol']) <th>Symbol</th> @endif
                        @if($schema['rate']) <th>{{ $schema['rate'] }}</th> @endif
                        @if($schema['status']) <th>Status</th> @endif
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($currencies as $currency)
                        @php
                            $rowToken = (string) ($currency->row_token ?? '');
                            $rowId = $currency->currency_id ?? null;
                            $code = (string) ($currency->currency_code ?? '');
                            $name = (string) ($currency->currency_name ?? '');
                            $symbol = (string) ($currency->symbol ?? '');
                            $rateValue = $currency->exchange_rate ?? null;
                            $statusValue = (string) ($currency->status ?? '');
                        @endphp
                        <tr>
                            <td>
                                @if($rowId !== null && $rowId !== '')
                                    <strong>#{{ $rowId }}</strong>
                                @else
                                    <span class="subtle">{{ $rowToken !== '' ? substr($rowToken, 0, 8) : 'N/A' }}</span>
                                @endif
                            </td>
                            @if($schema['code']) <td>{{ $code !== '' ? $code : 'N/A' }}</td> @endif
                            @if($schema['name']) <td>{{ $name !== '' ? $name : 'N/A' }}</td> @endif
                            @if($schema['symbol']) <td>{{ $symbol !== '' ? $symbol : 'N/A' }}</td> @endif
                            @if($schema['rate'])
                                <td>{{ $rateValue !== null ? number_format((float) $rateValue, 2) : 'N/A' }}</td>
                            @endif
                            @if($schema['status']) <td>{{ $statusValue !== '' ? $statusValue : 'N/A' }}</td> @endif
                            <td>
                                @if($canManageCurrencies)
                                    <div class="actions" style="justify-content: flex-start;">
                                        <button
                                            type="button"
                                            class="btn btn-muted js-currency-detail"
                                            data-row-token="{{ $rowToken }}"
                                            data-currency-id="{{ (string) ($rowId ?? '') }}"
                                            data-currency-code="{{ $code }}"
                                            data-currency-name="{{ $name }}"
                                            data-symbol="{{ $symbol }}"
                                            data-exchange-rate="{{ $rateValue !== null ? (float) $rateValue : '' }}"
                                            data-status="{{ $statusValue }}"
                                        >
                                            Detail
                                        </button>
                                        <form method="POST" action="{{ route('currencies.destroy') }}" onsubmit="return confirm('Delete this currency?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="row_token" value="{{ $rowToken }}">
                                            <button type="submit" class="btn btn-danger" @disabled($rowToken === '')>Delete</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="subtle">Read only</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $columnCount }}" class="subtle">No currencies found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="pager" style="margin-top: 12px;">
                {{ $currencies->links('pagination.orbit') }}
            </div>
        </section>

        @if($canManageCurrencies)
            <section class="card" id="currency-add-panel" style="display: none;">
                <h2 style="margin-top: 0;">Add Currency</h2>
                <p class="subtle">Create a new currency row in <code>CURRENCIES</code>.</p>

                <form method="POST" action="{{ route('currencies.store') }}" class="field-grid" style="margin-top: 12px;">
                    @csrf
                    @if($schema['code'])
                        <div>
                            <label for="currency_code">Currency Code</label>
                            <input id="currency_code" name="currency_code" type="text" value="{{ old('currency_code') }}" maxlength="20" required>
                        </div>
                    @endif
                    @if($schema['name'])
                        <div>
                            <label for="currency_name">Currency Name</label>
                            <input id="currency_name" name="currency_name" type="text" value="{{ old('currency_name') }}" maxlength="80" required>
                        </div>
                    @endif
                    @if($schema['symbol'])
                        <div>
                            <label for="symbol">Symbol</label>
                            <input id="symbol" name="symbol" type="text" value="{{ old('symbol') }}" maxlength="12">
                        </div>
                    @endif
                    @if($schema['rate'])
                        <div>
                            <label for="exchange_rate">{{ $schema['rate'] }}</label>
                            <input id="exchange_rate" name="exchange_rate" type="number" step="0.01" min="0" value="{{ old('exchange_rate') }}">
                        </div>
                    @endif
                    @if($schema['status'])
                        <div>
                            <label for="status">Status</label>
                            <input id="status" name="status" type="text" value="{{ old('status', 'Active') }}" maxlength="20">
                        </div>
                    @endif
                    <div class="actions" style="align-items: end;">
                        <button type="submit" class="btn btn-primary">Create Currency</button>
                    </div>
                </form>
            </section>
        @endif

        @if($canManageCurrencies)
            <div id="currency-detail-modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.5); z-index: 50; padding: 24px; overflow-y: auto;">
                <div class="card" role="dialog" aria-modal="true" aria-labelledby="currency-detail-title" style="max-width: 780px; margin: 0 auto;">
                    <div class="actions" style="justify-content: space-between;">
                        <div>
                            <h2 id="currency-detail-title" style="margin-top: 0;">Currency Detail</h2>
                            <p class="subtle" style="margin-top: 6px;">Review and update currency information.</p>
                        </div>
                        <span class="chip" id="detail-currency-chip">#</span>
                    </div>

                    <form id="currency-detail-form" method="POST" action="{{ route('currencies.update') }}" style="margin-top: 12px;">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="row_token" id="detail-row-token">
                        <div class="grid grid-2">
                            <div>
                                <label for="detail-currency-id">Currency ID</label>
                                <input id="detail-currency-id" type="text" readonly>
                            </div>
                            @if($schema['code'])
                                <div>
                                    <label for="detail-currency-code">Currency Code</label>
                                    <input id="detail-currency-code" name="currency_code" type="text" maxlength="20">
                                </div>
                            @endif
                            @if($schema['name'])
                                <div>
                                    <label for="detail-currency-name">Currency Name</label>
                                    <input id="detail-currency-name" name="currency_name" type="text" maxlength="80">
                                </div>
                            @endif
                            @if($schema['symbol'])
                                <div>
                                    <label for="detail-symbol">Symbol</label>
                                    <input id="detail-symbol" name="symbol" type="text" maxlength="12">
                                </div>
                            @endif
                            @if($schema['rate'])
                                <div>
                                    <label for="detail-exchange-rate">{{ $schema['rate'] }}</label>
                                    <input id="detail-exchange-rate" name="exchange_rate" type="number" step="0.01" min="0">
                                </div>
                            @endif
                            @if($schema['status'])
                                <div>
                                    <label for="detail-status">Status</label>
                                    <input id="detail-status" name="status" type="text" maxlength="20">
                                </div>
                            @endif
                        </div>

                        <div class="actions" style="margin-top: 12px;">
                            <button type="submit" class="btn btn-primary">Save Change</button>
                            <button type="button" class="btn btn-muted" id="cancel-currency-detail">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const listBtn = document.getElementById('show-currency-list');
            const addBtn = document.getElementById('show-currency-add');
            const listPanel = document.getElementById('currency-list-panel');
            const addPanel = document.getElementById('currency-add-panel');
            const openAddByError = @json(
                $errors->has('currency_code') ||
                $errors->has('currency_name') ||
                $errors->has('symbol') ||
                $errors->has('exchange_rate') ||
                $errors->has('status')
            );

            if (listBtn && listPanel) {
                const activate = (target) => {
                    const showList = target === 'list';
                    listPanel.style.display = showList ? '' : 'none';
                    if (addPanel) addPanel.style.display = showList ? 'none' : '';
                    listBtn.classList.toggle('btn-primary', showList);
                    listBtn.classList.toggle('btn-muted', !showList);
                    if (addBtn) {
                        addBtn.classList.toggle('btn-primary', !showList);
                        addBtn.classList.toggle('btn-muted', showList);
                    }
                };

                if ((window.location.hash === '#add-currency' || openAddByError) && addBtn) {
                    activate('add');
                } else {
                    activate('list');
                }

                listBtn.addEventListener('click', () => activate('list'));
                if (addBtn) {
                    addBtn.addEventListener('click', () => activate('add'));
                }
            }

            const detailModal = document.getElementById('currency-detail-modal');
            const detailRowToken = document.getElementById('detail-row-token');
            const detailChip = document.getElementById('detail-currency-chip');
            const detailCurrencyId = document.getElementById('detail-currency-id');
            const detailCurrencyCode = document.getElementById('detail-currency-code');
            const detailCurrencyName = document.getElementById('detail-currency-name');
            const detailSymbol = document.getElementById('detail-symbol');
            const detailRate = document.getElementById('detail-exchange-rate');
            const detailStatus = document.getElementById('detail-status');
            const cancelDetail = document.getElementById('cancel-currency-detail');
            const canManage = @json($canManageCurrencies);

            const closeDetail = () => {
                if (detailModal) detailModal.style.display = 'none';
            };

            if (cancelDetail) {
                cancelDetail.addEventListener('click', closeDetail);
            }

            if (detailModal) {
                detailModal.addEventListener('click', (event) => {
                    if (event.target === detailModal) {
                        closeDetail();
                    }
                });
            }

            if (!canManage) {
                return;
            }

            document.addEventListener('click', (event) => {
                const target = event.target;
                if (!(target instanceof HTMLElement)) {
                    return;
                }

                const button = target.closest('.js-currency-detail');
                if (!button || !detailModal || !detailRowToken || !detailChip || !detailCurrencyId) {
                    return;
                }

                const rowToken = button.dataset.rowToken ?? '';
                const currencyId = button.dataset.currencyId ?? '';
                detailRowToken.value = rowToken;
                detailCurrencyId.value = currencyId || rowToken;
                detailChip.textContent = currencyId ? `#${currencyId}` : (rowToken ? rowToken.slice(0, 8) : '#');
                if (detailCurrencyCode) detailCurrencyCode.value = button.dataset.currencyCode ?? '';
                if (detailCurrencyName) detailCurrencyName.value = button.dataset.currencyName ?? '';
                if (detailSymbol) detailSymbol.value = button.dataset.symbol ?? '';
                if (detailRate) detailRate.value = button.dataset.exchangeRate ?? '';
                if (detailStatus) detailStatus.value = button.dataset.status ?? '';

                detailModal.style.display = 'block';
            });
        });
    </script>
@endsection
