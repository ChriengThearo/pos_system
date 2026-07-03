@extends('layouts.ecommerce')

@section('title', 'Return/Refunds')

@section('content')
@php
    $returnFormColumns = collect($returnColumns ?? [])->reject(fn ($column) => (bool) ($column['is_identity'] ?? false))->values();
    $detailFormColumns = collect($returnDetailColumns ?? [])->values();
    $selectedInvoicePayload = $selectedInvoice ?? null;
    $oldItems = old('items', []);
    $selectedInvoiceValue = old('invoice_no', $selectedInvoiceNo ?? '');
    $returnDateValue = old('return_date', now()->format('Y-m-d'));
    $statusValue = old('status', 'Refunded');
@endphp

<style>
    .returns-layout {
        display: grid;
        gap: 14px;
    }

    .returns-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 10px;
    }

    .returns-form-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        align-items: end;
    }

    .returns-form-grid .wide {
        grid-column: span 2;
    }

    .returns-invoice-summary {
        display: grid;
        gap: 8px;
        margin-top: 12px;
        padding: 12px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: #f8fbfd;
    }

    .returns-invoice-summary strong {
        color: #1b2a40;
    }

    .returns-table-actions {
        display: inline-flex;
        gap: 8px;
        align-items: center;
    }

    .returns-item-note {
        color: var(--muted);
        font-size: .8rem;
        margin-top: 5px;
    }

    .returns-empty {
        padding: 16px;
        color: var(--muted);
        text-align: center;
    }

    .returns-num {
        text-align: right;
    }

    @media (max-width: 980px) {
        .returns-form-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 760px) {
        .returns-form-grid {
            grid-template-columns: 1fr;
        }

        .returns-form-grid .wide {
            grid-column: auto;
        }
    }
</style>

<div class="returns-layout">
    <section class="card">
        <div class="actions" style="justify-content: space-between;">
            <div>
                <h1 class="headline">Return/Refunds</h1>
                <p class="subtle">Create invoice-based returns from <code>RETURNS</code> and <code>RETURN_DETAILS</code>. Returned quantities are added back to stock.</p>
                <div class="returns-meta">
                    @foreach($returnFormColumns as $column)
                        <span class="chip">{{ $column['label'] }}: {{ $column['data_type'] }}</span>
                    @endforeach
                    @foreach($detailFormColumns as $column)
                        <span class="chip">Detail {{ $column['label'] }}: {{ $column['data_type'] }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    @if($canManageReturns)
        <section class="card">
            <form method="POST" action="{{ route('returns.store') }}" id="return-form">
                @csrf

                <div class="returns-form-grid">
                    @foreach($returnFormColumns as $column)
                        @php
                            $name = (string) ($column['name'] ?? '');
                            $inputName = (string) ($column['input_name'] ?? strtolower($name));
                            $label = (string) ($column['label'] ?? $name);
                            $inputType = (string) ($column['input_type'] ?? 'text');
                        @endphp

                        @if($name === 'INVOICE_NO')
                            <div>
                                <label for="return-invoice-no">{{ $label }}</label>
                                <input
                                    id="return-invoice-no"
                                    name="invoice_no"
                                    type="number"
                                    min="1"
                                    list="recent-return-invoices"
                                    value="{{ $selectedInvoiceValue }}"
                                    placeholder="Invoice number"
                                    required
                                >
                                <datalist id="recent-return-invoices">
                                    @foreach($recentInvoices as $invoice)
                                        <option value="{{ (int) $invoice->invoice_no }}">
                                            #{{ $invoice->invoice_no }} {{ $invoice->client_name ?? '' }} {{ $invoice->phone ?? '' }}
                                        </option>
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="actions">
                                <button type="button" class="btn btn-muted" id="load-return-invoice">Load Invoice</button>
                            </div>
                        @elseif($name === 'RETURN_DATE')
                            <div>
                                <label for="return-date">{{ $label }}</label>
                                <input id="return-date" name="return_date" type="date" value="{{ $returnDateValue }}">
                            </div>
                        @elseif($name === 'STATUS')
                            <div>
                                <label for="return-status">{{ $label }}</label>
                                <select id="return-status" name="status">
                                    @foreach(['Refunded', 'Pending', 'Cancelled'] as $status)
                                        <option value="{{ $status }}" @selected($statusValue === $status)>{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @elseif($name === 'REASON')
                            <div class="wide">
                                <label for="return-reason">{{ $label }}</label>
                                <textarea id="return-reason" name="reason" rows="2" placeholder="Reason for return">{{ old('reason') }}</textarea>
                            </div>
                        @else
                            <div>
                                <label for="return-extra-{{ $inputName }}">{{ $label }}</label>
                                <input
                                    id="return-extra-{{ $inputName }}"
                                    name="{{ $inputName }}"
                                    type="{{ $inputType === 'number' || $inputType === 'money' ? 'number' : 'text' }}"
                                    value="{{ old($inputName) }}"
                                    @if($inputType === 'money') step="0.01" min="0" @endif
                                >
                            </div>
                        @endif
                    @endforeach
                </div>

                <div id="return-invoice-summary" class="returns-invoice-summary" style="display: none;"></div>

                <div style="margin-top: 14px;">
                    <div class="actions" style="justify-content: space-between; margin-bottom: 10px;">
                        <div>
                            <h2 style="margin: 0;">Returned Products</h2>
                            <p class="subtle" style="margin: 4px 0 0;">Choose products from the selected invoice. Quantity is limited by the remaining returnable amount.</p>
                        </div>
                        <button type="button" class="btn btn-muted" id="add-return-row">Add Product</button>
                    </div>

                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>Product</th>
                                <th class="returns-num">Sold</th>
                                <th class="returns-num">Returned</th>
                                <th class="returns-num">Available</th>
                                <th class="returns-num">Qty</th>
                                <th class="returns-num">Refund Amount</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody id="return-items-body">
                            <tr>
                                <td colspan="7" class="returns-empty">Load an invoice to add returned products.</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="actions" style="justify-content: flex-end; margin-top: 14px;">
                    <button type="submit" class="btn btn-primary">Save Return</button>
                </div>
            </form>
        </section>
    @else
        <section class="card">
            <p class="subtle">You can view returns, but you do not have permission to create returns.</p>
        </section>
    @endif

    <section class="card">
        <form method="GET" action="{{ route('returns.index') }}" class="field-grid">
            <div>
                <label for="returns-q">Search returns</label>
                <input id="returns-q" type="text" name="q" value="{{ $q }}" placeholder="Return no, invoice no, client, phone, status">
            </div>
            <div class="actions" style="align-items: end;">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="{{ route('returns.index') }}" class="btn btn-muted">Reset</a>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Return</th>
                    <th>Invoice</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Phone</th>
                    <th class="returns-num">Qty</th>
                    <th class="returns-num">Refund</th>
                    <th>Status</th>
                    <th>Reason</th>
                </tr>
                </thead>
                <tbody>
                @forelse($returns as $return)
                    <tr>
                        <td>#{{ $return->return_no }}</td>
                        <td>
                            <a href="{{ route('store.orders.show', ['invoiceNo' => (int) $return->invoice_no]) }}" style="color: var(--primary); font-weight: 700;">
                                #{{ $return->invoice_no }}
                            </a>
                        </td>
                        <td>{{ $return->return_date ? \Illuminate\Support\Carbon::parse($return->return_date)->format('Y-m-d') : 'N/A' }}</td>
                        <td>{{ $return->client_name ?: 'N/A' }}</td>
                        <td>{{ $return->phone ?: 'N/A' }}</td>
                        <td class="returns-num">{{ number_format((float) $return->item_qty) }}</td>
                        <td class="returns-num">${{ number_format((float) $return->refund_total, 2) }}</td>
                        <td><span class="chip">{{ $return->status ?: 'N/A' }}</span></td>
                        <td>{{ $return->reason ?: 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="returns-empty">No returns found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card pager">
        {{ $returns->links('pagination.orbit') }}
    </section>
</div>

@if($canManageReturns)
<script>
    (() => {
        const invoiceInput = document.getElementById('return-invoice-no');
        const loadButton = document.getElementById('load-return-invoice');
        const addButton = document.getElementById('add-return-row');
        const summary = document.getElementById('return-invoice-summary');
        const body = document.getElementById('return-items-body');
        const initialInvoice = @json($selectedInvoicePayload);
        const oldItems = @json($oldItems);
        const invoiceUrlTemplate = @json(route('returns.invoice', ['invoiceNo' => '__INVOICE__']));

        let invoiceData = null;
        let rowIndex = 0;

        if (!invoiceInput || !loadButton || !addButton || !summary || !body) {
            return;
        }

        const money = (value) => Number(value || 0).toFixed(2);
        const esc = (value) => String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');

        const productByNo = (productNo) => {
            const key = String(productNo || '').toUpperCase();
            return (invoiceData?.items || []).find((item) => String(item.product_no || '').toUpperCase() === key) || null;
        };

        const productOptions = (selected) => {
            const selectedKey = String(selected || '').toUpperCase();
            const options = ['<option value="">Choose product</option>'];
            (invoiceData?.items || []).forEach((item) => {
                const productNo = String(item.product_no || '');
                const isSelected = productNo.toUpperCase() === selectedKey;
                const label = `${productNo} - ${item.product_name || ''} (${item.returnable_qty || 0} available)`;
                options.push(`<option value="${esc(productNo)}" ${isSelected ? 'selected' : ''}>${esc(label)}</option>`);
            });
            return options.join('');
        };

        const setEmptyRows = (message) => {
            body.innerHTML = `<tr><td colspan="7" class="returns-empty">${esc(message)}</td></tr>`;
        };

        const updateRow = (row) => {
            const select = row.querySelector('[data-return-product]');
            const qtyInput = row.querySelector('[data-return-qty]');
            const refundInput = row.querySelector('[data-return-refund]');
            const soldCell = row.querySelector('[data-return-sold]');
            const returnedCell = row.querySelector('[data-return-returned]');
            const availableCell = row.querySelector('[data-return-available]');
            const note = row.querySelector('[data-return-note]');
            const item = productByNo(select?.value);

            if (!item) {
                if (soldCell) soldCell.textContent = '0';
                if (returnedCell) returnedCell.textContent = '0';
                if (availableCell) availableCell.textContent = '0';
                if (qtyInput) qtyInput.max = '0';
                if (note) note.textContent = 'Select an invoice product.';
                return;
            }

            const returnable = Number(item.returnable_qty || 0);
            const price = Number(item.price || 0);
            if (soldCell) soldCell.textContent = Number(item.sold_qty || 0).toLocaleString();
            if (returnedCell) returnedCell.textContent = Number(item.returned_qty || 0).toLocaleString();
            if (availableCell) availableCell.textContent = returnable.toLocaleString();
            if (qtyInput) {
                qtyInput.max = String(returnable);
                if (!qtyInput.value || Number(qtyInput.value) <= 0) {
                    qtyInput.value = returnable > 0 ? '1' : '0';
                }
            }
            if (refundInput && (refundInput.dataset.auto === '1' || refundInput.value === '')) {
                refundInput.value = money(Number(qtyInput?.value || 0) * price);
                refundInput.dataset.auto = '1';
            }
            if (note) {
                note.textContent = `Unit price $${money(price)}`;
            }
        };

        const addRow = (prefill = {}) => {
            if (!invoiceData || !Array.isArray(invoiceData.items) || invoiceData.items.length === 0) {
                setEmptyRows('Load an invoice with products before adding rows.');
                return;
            }

            if (body.querySelector('.returns-empty')) {
                body.innerHTML = '';
            }

            const index = rowIndex++;
            const productNo = String(prefill.product_no || '');
            const qty = prefill.qty !== undefined && prefill.qty !== null ? String(prefill.qty) : '';
            const refund = prefill.refund_amount !== undefined && prefill.refund_amount !== null ? String(prefill.refund_amount) : '';

            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <select name="items[${index}][product_no]" data-return-product>
                        ${productOptions(productNo)}
                    </select>
                    <div class="returns-item-note" data-return-note>Select an invoice product.</div>
                </td>
                <td class="returns-num" data-return-sold>0</td>
                <td class="returns-num" data-return-returned>0</td>
                <td class="returns-num" data-return-available>0</td>
                <td class="returns-num">
                    <input name="items[${index}][qty]" type="number" min="0" step="1" value="${esc(qty)}" data-return-qty style="width: 96px;">
                </td>
                <td class="returns-num">
                    <input name="items[${index}][refund_amount]" type="number" min="0" step="0.01" value="${esc(refund)}" data-return-refund data-auto="${refund === '' ? '1' : '0'}" style="width: 132px;">
                </td>
                <td>
                    <button type="button" class="btn btn-muted" data-return-remove>Remove</button>
                </td>
            `;

            body.appendChild(row);
            updateRow(row);

            row.querySelector('[data-return-product]')?.addEventListener('change', () => {
                const refundInput = row.querySelector('[data-return-refund]');
                if (refundInput) {
                    refundInput.dataset.auto = '1';
                    refundInput.value = '';
                }
                updateRow(row);
            });
            row.querySelector('[data-return-qty]')?.addEventListener('input', () => updateRow(row));
            row.querySelector('[data-return-refund]')?.addEventListener('input', (event) => {
                event.currentTarget.dataset.auto = '0';
            });
            row.querySelector('[data-return-remove]')?.addEventListener('click', () => {
                row.remove();
                if (!body.querySelector('tr')) {
                    setEmptyRows('Add at least one returned product.');
                }
            });
        };

        const renderInvoice = (data, prefillRows = []) => {
            invoiceData = data;
            rowIndex = 0;
            const header = data?.header || {};
            summary.style.display = '';
            summary.innerHTML = `
                <div><strong>Invoice #${esc(header.invoice_no)}</strong> ${esc(header.invoice_status || '')}</div>
                <div class="subtle">Customer: ${esc(header.client_name || 'N/A')} | Phone: ${esc(header.phone || 'N/A')} | Seller: ${esc(header.employee_name || 'N/A')} | Date: ${esc(header.invoice_date || 'N/A')}</div>
            `;

            body.innerHTML = '';
            const usableRows = Array.isArray(prefillRows) && prefillRows.length > 0
                ? prefillRows
                : [{}];
            usableRows.forEach((item) => addRow(item));
        };

        const loadInvoice = async () => {
            const invoiceNo = String(invoiceInput.value || '').trim();
            if (invoiceNo === '') {
                setEmptyRows('Enter an invoice number first.');
                return;
            }

            setEmptyRows('Loading invoice products...');
            summary.style.display = 'none';
            try {
                const url = invoiceUrlTemplate.replace('__INVOICE__', encodeURIComponent(invoiceNo));
                const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
                const json = await response.json();
                if (!response.ok) {
                    throw new Error(json.message || 'Invoice was not found.');
                }
                renderInvoice(json, []);
            } catch (error) {
                invoiceData = null;
                summary.style.display = '';
                summary.innerHTML = `<span class="subtle">${esc(error.message || 'Unable to load invoice.')}</span>`;
                setEmptyRows('No invoice products loaded.');
            }
        };

        loadButton.addEventListener('click', loadInvoice);
        addButton.addEventListener('click', () => addRow({}));
        invoiceInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                loadInvoice();
            }
        });

        if (initialInvoice) {
            renderInvoice(initialInvoice, oldItems);
        }
    })();
</script>
@endif
@endsection
