@extends('layouts.ecommerce')

@section('title', 'Return/Refunds')

@section('content')
@php
    $selectedInvoicePayload = $selectedInvoice ?? null;
    $oldItems               = old('items', []);
    $selectedInvoiceValue   = old('invoice_no', $selectedInvoiceNo ?? '');
    $returnDateValue        = old('return_date', now()->format('Y-m-d'));
    $statusValue            = old('status', 'Refunded');
@endphp

<style>
    .returns-form-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 12px;
        align-items: end;
    }
    .returns-form-grid .span-2 { grid-column: span 2; }

    .returns-invoice-summary {
        margin-top: 12px;
        padding: 12px 14px;
        border: 1px solid var(--border);
        border-radius: 12px;
        background: var(--surface-soft);
        display: grid;
        gap: 4px;
    }

    .returns-num { text-align: right; }

    .returns-empty-state {
        padding: 32px 16px;
        text-align: center;
        color: var(--muted);
    }
    .returns-empty-state i {
        font-size: 2rem;
        display: block;
        margin-bottom: 8px;
        opacity: .5;
    }

    .returns-item-note {
        color: var(--muted);
        font-size: .78rem;
        margin-top: 4px;
    }

    .badge-status {
        display: inline-flex;
        align-items: center;
        border-radius: 999px;
        font-size: .75rem;
        font-weight: 700;
        padding: 3px 10px;
    }
    .badge-refunded { background: #d1e7dd; color: #0f5132; }
    .badge-pending  { background: #fff3cd; color: #664d03; }
    .badge-cancelled { background: #f8d7da; color: #842029; }
    .badge-default  { background: #f3f7fb; color: var(--muted); border: 1px solid var(--border); }

    @media (max-width: 980px) {
        .returns-form-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 760px) {
        .returns-form-grid { grid-template-columns: 1fr; }
        .returns-form-grid .span-2 { grid-column: auto; }
    }
</style>

{{-- ═══════════════════════════════════════════════════════════════════════════
     HEADER + STAT CARDS (matches Products page layout)
═══════════════════════════════════════════════════════════════════════════ --}}
<section class="card">
    <h1 class="headline">Return / Refunds</h1>
    <p class="subtle" style="margin-top: 4px;">Process invoice-based product returns and track refund status.</p>

    <div class="grid grid-3" style="margin-top: 14px;">
        <article class="stat">
            <div class="label">Total Returns</div>
            <div class="value">{{ number_format($totalReturns ?? 0) }}</div>
        </article>
        <article class="stat">
            <div class="label">Pending Refunds</div>
            <div class="value">{{ number_format($pendingReturns ?? 0) }}</div>
        </article>
        <article class="stat">
            <div class="label">Total Refunded ($)</div>
            <div class="value">${{ number_format($totalRefunded ?? 0, 2) }}</div>
        </article>
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════════
     TAB BAR (matches Products page tab style)
═══════════════════════════════════════════════════════════════════════════ --}}
<section class="card" style="margin-top: 14px;">
    <div class="actions">
        <button type="button" class="btn btn-primary" id="tab-returns-list">Return History</button>
        @if($canManageReturns)
            <button type="button" class="btn btn-muted" id="tab-new-return">New Return</button>
        @endif
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════════
     PANEL: RETURN HISTORY (search + table + pagination)
═══════════════════════════════════════════════════════════════════════════ --}}
<section class="card" id="panel-returns-list">
    {{-- Search --}}
    <div class="field-grid" style="margin-bottom: 14px;">
        <form method="GET" action="{{ route('returns.index') }}" class="field-grid" style="margin:0;">
            <div>
                <label for="returns-q">Search returns</label>
                <input id="returns-q" type="text" name="q" value="{{ $q }}"
                    placeholder="Return no, invoice no, client, phone, status">
            </div>
            <div class="actions" style="align-items: flex-end;">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="{{ route('returns.index') }}" class="btn btn-muted">Reset</a>
            </div>
        </form>
    </div>

    {{-- Table --}}
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Return No</th>
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
            @forelse($returns as $ret)
                @php
                    $statusLower = mb_strtolower(trim((string) ($ret->status ?? '')));
                    $badgeClass = match($statusLower) {
                        'refunded' => 'badge-refunded',
                        'pending'  => 'badge-pending',
                        'cancelled', 'rejected' => 'badge-cancelled',
                        default    => 'badge-default',
                    };
                @endphp
                <tr>
                    <td><strong>#{{ $ret->return_no }}</strong></td>
                    <td>
                        <a href="{{ route('store.orders.show', ['invoiceNo' => (int) $ret->invoice_no]) }}"
                           style="color: var(--primary); font-weight: 700;">
                            #{{ $ret->invoice_no }}
                        </a>
                    </td>
                    <td>{{ $ret->return_date ? \Illuminate\Support\Carbon::parse($ret->return_date)->format('Y-m-d') : 'N/A' }}</td>
                    <td>{{ $ret->client_name ?: 'N/A' }}</td>
                    <td>{{ $ret->phone ?: 'N/A' }}</td>
                    <td class="returns-num">{{ number_format((float) $ret->item_qty) }}</td>
                    <td class="returns-num">${{ number_format((float) $ret->refund_total, 2) }}</td>
                    <td><span class="badge-status {{ $badgeClass }}">{{ ucfirst($ret->status ?: 'N/A') }}</span></td>
                    <td>{{ $ret->reason ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9">
                        <div class="returns-empty-state">
                            <i class="bi bi-arrow-return-left"></i>
                            No returns found. Returns will appear here once created.
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="pager" style="margin-top: 12px;">
        {{ $returns->links('pagination.orbit') }}
    </div>
</section>

{{-- ═══════════════════════════════════════════════════════════════════════════
     PANEL: NEW RETURN FORM
═══════════════════════════════════════════════════════════════════════════ --}}
@if($canManageReturns)
<section class="card" id="panel-new-return" style="display: none;">
    <div class="actions" style="justify-content: space-between; margin-bottom: 14px;">
        <h2 style="margin: 0;">New Return</h2>
    </div>

    <form method="POST" action="{{ route('returns.store') }}" id="return-form">
        @csrf

        <div class="returns-form-grid">
            <div>
                <label for="return-invoice-no">Invoice No</label>
                <input
                    id="return-invoice-no"
                    name="invoice_no"
                    type="number"
                    min="1"
                    list="recent-invoices-dl"
                    value="{{ $selectedInvoiceValue }}"
                    placeholder="e.g. 1068"
                    required
                >
                <datalist id="recent-invoices-dl">
                    @foreach($recentInvoices as $inv)
                        <option value="{{ (int) $inv->invoice_no }}">
                            #{{ $inv->invoice_no }} — {{ $inv->client_name ?? '' }}
                        </option>
                    @endforeach
                </datalist>
            </div>

            <div class="actions" style="align-items: flex-end;">
                <button type="button" id="load-return-invoice" class="btn btn-muted">
                    <i class="bi bi-search"></i> Load Invoice
                </button>
            </div>

            <div>
                <label for="return-date">Return Date</label>
                <input id="return-date" name="return_date" type="date" value="{{ $returnDateValue }}">
            </div>

            <div>
                <label for="return-status">Status</label>
                <select id="return-status" name="status">
                    @foreach(['Refunded', 'Pending', 'Cancelled'] as $s)
                        <option value="{{ $s }}" @selected($statusValue === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>

            <div class="span-2">
                <label for="return-reason">Reason</label>
                <textarea id="return-reason" name="reason" rows="2" placeholder="Reason for return…">{{ old('reason') }}</textarea>
            </div>
        </div>

        {{-- Invoice summary --}}
        <div id="return-invoice-summary" class="returns-invoice-summary" style="display:none;"></div>

        {{-- Returned products --}}
        <div style="margin-top: 16px;">
            <div class="actions" style="justify-content: space-between; margin-bottom: 10px;">
                <h3 style="margin: 0; font-size: .95rem;">Returned Products</h3>
                <button type="button" id="add-return-row" class="btn btn-muted" style="border-color: var(--accent); color: var(--accent);">
                    <i class="bi bi-plus-lg"></i> Add Product
                </button>
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
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="return-items-body">
                        <tr>
                            <td colspan="7">
                                <div class="returns-empty-state">
                                    <i class="bi bi-box-arrow-in-left"></i>
                                    Load an invoice to add returned products.
                                </div>
                            </td>
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
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     FLASH MESSAGES
═══════════════════════════════════════════════════════════════════════════ --}}
@if(session('success'))
    <div class="flash success toast" style="margin-top: 14px;">
        <span class="toast-ico"><i class="bi bi-check-lg"></i></span>
        {{ session('success') }}
    </div>
@endif
@if(session('error'))
    <div class="flash error" style="margin-top: 14px;">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="flash error" style="margin-top: 14px;">
        <ul class="error-list">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- ═══════════════════════════════════════════════════════════════════════════
     JAVASCRIPT: Tab switching + Return form logic
═══════════════════════════════════════════════════════════════════════════ --}}
<script>
document.addEventListener('DOMContentLoaded', () => {
    // ── Tab switching ────────────────────────────────────────────────────
    const listBtn  = document.getElementById('tab-returns-list');
    const newBtn   = document.getElementById('tab-new-return');
    const listPanel = document.getElementById('panel-returns-list');
    const newPanel  = document.getElementById('panel-new-return');

    const activateTab = (which) => {
        const isList = which === 'list';
        if (listPanel) listPanel.style.display = isList ? '' : 'none';
        if (newPanel)  newPanel.style.display  = isList ? 'none' : '';
        if (listBtn) { listBtn.classList.toggle('btn-primary', isList); listBtn.classList.toggle('btn-muted', !isList); }
        if (newBtn)  { newBtn.classList.toggle('btn-primary', !isList); newBtn.classList.toggle('btn-muted', isList); }
    };

    listBtn?.addEventListener('click', () => activateTab('list'));
    newBtn?.addEventListener('click', () => activateTab('new'));

    // Auto-show "new return" if there's a validation error or preloaded invoice
    @if($errors->any() || $selectedInvoicePayload)
        activateTab('new');
    @else
        activateTab('list');
    @endif
});
</script>

@if($canManageReturns)
<script>
(() => {
    'use strict';

    const invoiceInput  = document.getElementById('return-invoice-no');
    const loadButton    = document.getElementById('load-return-invoice');
    const addButton     = document.getElementById('add-return-row');
    const summary       = document.getElementById('return-invoice-summary');
    const body          = document.getElementById('return-items-body');
    const initialInvoice = @json($selectedInvoicePayload);
    const oldItems       = @json($oldItems);
    const invoiceUrlTemplate = @json(route('returns.invoice', ['invoiceNo' => '__INVOICE__']));

    let invoiceData = null;
    let rowIndex = 0;

    if (!invoiceInput || !loadButton || !addButton || !summary || !body) return;

    const money = (v) => Number(v || 0).toFixed(2);
    const esc = (v) => String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');

    const productByNo = (no) => {
        const key = String(no || '').toUpperCase();
        return (invoiceData?.items || []).find(i => String(i.product_no || '').toUpperCase() === key) || null;
    };

    const productOptions = (selected) => {
        const sel = String(selected || '').toUpperCase();
        let html = '<option value="">— choose product —</option>';
        (invoiceData?.items || []).forEach(item => {
            const no = String(item.product_no || '');
            const label = `${no} — ${item.product_name || ''} (${item.returnable_qty || 0} available)`;
            html += `<option value="${esc(no)}" ${no.toUpperCase() === sel ? 'selected' : ''}>${esc(label)}</option>`;
        });
        return html;
    };

    const setEmpty = (msg, icon = 'bi-box-arrow-in-left') => {
        body.innerHTML = `<tr><td colspan="7"><div class="returns-empty-state"><i class="bi ${icon}"></i> ${esc(msg)}</div></td></tr>`;
    };

    const syncRow = (row) => {
        const sel = row.querySelector('[data-return-product]');
        const qtyInput = row.querySelector('[data-return-qty]');
        const refundInput = row.querySelector('[data-return-refund]');
        const soldCell = row.querySelector('[data-return-sold]');
        const retCell = row.querySelector('[data-return-returned]');
        const availCell = row.querySelector('[data-return-available]');
        const note = row.querySelector('[data-return-note]');
        const item = productByNo(sel?.value);

        if (!item) {
            if (soldCell) soldCell.textContent = '—';
            if (retCell) retCell.textContent = '—';
            if (availCell) availCell.textContent = '—';
            if (qtyInput) qtyInput.max = '0';
            if (note) note.textContent = 'Select an invoice product.';
            return;
        }

        const returnable = Number(item.returnable_qty || 0);
        const price = Number(item.price || 0);
        if (soldCell) soldCell.textContent = Number(item.sold_qty || 0).toLocaleString();
        if (retCell) retCell.textContent = Number(item.returned_qty || 0).toLocaleString();
        if (availCell) availCell.textContent = returnable.toLocaleString();
        if (qtyInput) {
            qtyInput.max = String(returnable);
            if (!qtyInput.value || Number(qtyInput.value) <= 0) qtyInput.value = returnable > 0 ? '1' : '0';
        }
        if (refundInput && (refundInput.dataset.auto === '1' || refundInput.value === '')) {
            refundInput.value = money(Number(qtyInput?.value || 0) * price);
            refundInput.dataset.auto = '1';
        }
        if (note) note.textContent = `Unit price $${money(price)}`;
    };

    const addRow = (prefill = {}) => {
        if (!invoiceData || !(invoiceData.items || []).length) {
            setEmpty('Load an invoice with products before adding rows.', 'bi-exclamation-circle');
            return;
        }
        if (body.querySelector('.returns-empty-state')) body.innerHTML = '';

        const i = rowIndex++;
        const productNo = String(prefill.product_no || '');
        const qty = prefill.qty != null ? String(prefill.qty) : '';
        const refund = prefill.refund_amount != null ? String(prefill.refund_amount) : '';

        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <select name="items[${i}][product_no]" data-return-product style="min-width:200px;">
                    ${productOptions(productNo)}
                </select>
                <div class="returns-item-note" data-return-note>Select an invoice product.</div>
            </td>
            <td class="returns-num" data-return-sold>—</td>
            <td class="returns-num" data-return-returned>—</td>
            <td class="returns-num" data-return-available>—</td>
            <td class="returns-num">
                <input name="items[${i}][qty]" type="number" min="0" step="1" value="${esc(qty)}" data-return-qty style="width:90px;">
            </td>
            <td class="returns-num">
                <input name="items[${i}][refund_amount]" type="number" min="0" step="0.01" value="${esc(refund)}" data-return-refund data-auto="${refund === '' ? '1' : '0'}" style="width:120px;">
            </td>
            <td>
                <button type="button" class="btn btn-muted" data-return-remove style="font-size:.8rem; padding:5px 10px;">Remove</button>
            </td>
        `;
        body.appendChild(row);
        syncRow(row);

        row.querySelector('[data-return-product]')?.addEventListener('change', () => {
            const ref = row.querySelector('[data-return-refund]');
            if (ref) { ref.dataset.auto = '1'; ref.value = ''; }
            syncRow(row);
        });
        row.querySelector('[data-return-qty]')?.addEventListener('input', () => syncRow(row));
        row.querySelector('[data-return-refund]')?.addEventListener('input', e => { e.currentTarget.dataset.auto = '0'; });
        row.querySelector('[data-return-remove]')?.addEventListener('click', () => {
            row.remove();
            if (!body.querySelector('tr')) setEmpty('Add at least one returned product.', 'bi-cart-x');
        });
    };

    const renderInvoice = (data, prefillRows = []) => {
        invoiceData = data;
        rowIndex = 0;
        const h = data?.header || {};
        summary.style.display = '';
        summary.innerHTML = `
            <div><strong>Invoice #${esc(h.invoice_no)}</strong> <span class="badge-status badge-default" style="margin-left:6px;">${esc(h.invoice_status || 'N/A')}</span></div>
            <div class="subtle" style="font-size:.84rem;">Customer: <strong>${esc(h.client_name || 'N/A')}</strong> · Phone: ${esc(h.phone || 'N/A')} · Seller: ${esc(h.employee_name || 'N/A')} · Date: ${esc(h.invoice_date || 'N/A')}</div>
        `;
        body.innerHTML = '';
        const rows = Array.isArray(prefillRows) && prefillRows.length > 0 ? prefillRows : [{}];
        rows.forEach(r => addRow(r));
    };

    const loadInvoice = async () => {
        const no = String(invoiceInput.value || '').trim();
        if (!no) { setEmpty('Enter an invoice number first.', 'bi-info-circle'); return; }
        setEmpty('Loading…', 'bi-hourglass-split');
        summary.style.display = 'none';
        try {
            const url = invoiceUrlTemplate.replace('__INVOICE__', encodeURIComponent(no));
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const json = await res.json();
            if (!res.ok) throw new Error(json.message || 'Invoice not found.');
            renderInvoice(json, []);
        } catch (err) {
            invoiceData = null;
            summary.style.display = '';
            summary.innerHTML = `<span style="color:var(--danger);">${esc(err.message)}</span>`;
            setEmpty('Failed to load invoice.', 'bi-x-circle');
        }
    };

    loadButton.addEventListener('click', loadInvoice);
    addButton.addEventListener('click', () => addRow({}));
    invoiceInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); loadInvoice(); } });

    if (initialInvoice) renderInvoice(initialInvoice, oldItems);
})();
</script>
@endif
@endsection
