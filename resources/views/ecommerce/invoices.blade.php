@extends('layouts.ecommerce')

@section('title', 'POS — Invoices')

@section('content')
@php
    $header      = $order['header'] ?? null;
    $orderItems  = $order['items'] ?? collect();
    $subtotal    = (float) ($order['subtotal'] ?? 0);
    $discountRate   = (float) ($order['discount_rate'] ?? 0);
    $discountAmount = (float) ($order['discount_amount'] ?? 0);
    $grandTotal  = (float) ($order['grand_total'] ?? 0);
    $isNew       = $isNew ?? false;
    $staffUser   = \App\Support\StaffAuth::user();
    $isCompleted = !$isNew && (($header->invoice_status ?? '') === 'Completed');
    $paymentCurrencies     = collect($paymentCurrencies ?? [])->values();
    $defaultPaymentCurrency = trim((string) ($defaultPaymentCurrency ?? 'USD'));
    if ($defaultPaymentCurrency === '') $defaultPaymentCurrency = 'USD';
    $initialPaymentCurrency = old('payment_currency', $defaultPaymentCurrency);
    $canManage   = \App\Support\StaffAuth::can('invoices.manage');
    $types       = $types ?? collect();
    $clients     = $clients ?? collect();
@endphp

<style>
    /* ── Override content area for full-height POS layout ── */
    main.content {
        padding: 14px 16px !important;
        gap: 0 !important;
        height: calc(100vh - var(--appbar-height));
        overflow: hidden;
    }

    /* ── Two-column POS wrapper ── */
    #pos-wrap {
        display: grid;
        grid-template-columns: 1fr 400px;
        gap: 14px;
        height: 100%;
        min-height: 0;
    }

    /* ════════════════════════════════════════
       LEFT PANEL — Catalog
    ════════════════════════════════════════ */
    #pos-catalog {
        background: #fff;
        border-radius: 16px;
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        min-height: 0;
        overflow: hidden;
    }

    #pos-catalog-top {
        padding: 14px 16px 0;
        flex-shrink: 0;
    }

    /* Search row */
    #pos-search-row {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    #pos-search {
        flex: 1;
        border: 1.5px solid var(--border);
        border-radius: 10px;
        padding: 10px 14px;
        font-size: .93rem;
        outline: none;
        background: #f9fbfc;
        transition: border .15s, background .15s;
        font-family: inherit;
        color: var(--text);
    }
    #pos-search:focus { border-color: var(--primary); background: #fff; }
    #pos-search-btn {
        background: var(--accent);
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 10px 22px;
        font-size: .93rem;
        font-weight: 700;
        cursor: pointer;
        white-space: nowrap;
        font-family: inherit;
        transition: opacity .15s;
    }
    #pos-search-btn:hover { opacity: .88; }

    /* Category tabs */
    #pos-type-tabs {
        display: flex;
        gap: 8px;
        overflow-x: auto;
        padding: 12px 16px;
        flex-shrink: 0;
        scrollbar-width: none;
        border-bottom: 1px solid var(--border);
    }
    #pos-type-tabs::-webkit-scrollbar { display: none; }

    .pos-type-tab {
        white-space: nowrap;
        padding: 7px 16px;
        border-radius: 999px;
        border: 1.5px solid var(--border);
        background: #fff;
        font-size: .83rem;
        font-weight: 600;
        cursor: pointer;
        font-family: inherit;
        transition: border-color .12s, color .12s, background-color .12s;
        color: var(--text);
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .pos-type-tab:hover { border-color: var(--accent); color: var(--accent); }
    .pos-type-tab.active { background: var(--accent); border-color: var(--accent); color: #fff; }

    /* Product grid scroll area */
    #pos-grid-wrap {
        flex: 1;
        overflow-y: auto;
        padding: 14px 16px 16px;
        min-height: 0;
    }
    #pos-product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(148px, 1fr));
        gap: 12px;
    }

    /* Product card */
    .pos-card {
        background: #fafbfd;
        border: 1.5px solid var(--border);
        border-radius: 14px;
        padding: 10px;
        cursor: pointer;
        transition: border-color .12s, box-shadow .12s;
        display: flex;
        flex-direction: column;
        gap: 6px;
        user-select: none;
    }
    .pos-card:hover {
        border-color: var(--accent);
        box-shadow: 0 2px 8px rgba(227,100,20,.12);
    }
    .pos-card.oos { opacity: .45; cursor: not-allowed; transform: none !important; box-shadow: none !important; border-color: var(--border) !important; }

    .pos-card-img {
        width: 100%;
        aspect-ratio: 1;
        border-radius: 10px;
        object-fit: cover;
        background: #eef3f8;
        display: block;
    }
    .pos-card-img-ph {
        width: 100%;
        aspect-ratio: 1;
        border-radius: 10px;
        background: linear-gradient(135deg, #eef3f8, #dde6f0);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #b0bcc8;
    }
    .pos-card-img-wrap {
        position: relative;
    }
    .pos-card-photo-prev,
    .pos-card-photo-next {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        z-index: 2;
        background: rgba(255,255,255,0.82);
        border: none;
        border-radius: 50%;
        padding: 3px;
        line-height: 0;
        cursor: pointer;
        color: #374151;
        box-shadow: 0 1px 4px rgba(0,0,0,0.12);
    }
    .pos-card-photo-prev { left: 4px; }
    .pos-card-photo-next { right: 4px; }
    .pos-card-name {
        font-size: .82rem;
        font-weight: 600;
        color: var(--text);
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .pos-card-price {
        font-size: .91rem;
        font-weight: 700;
        color: var(--accent);
    }

    /* Loading / empty */
    #pos-catalog-loading { display: none; text-align: center; padding: 28px; color: var(--muted); font-size: .9rem; }
    #pos-catalog-empty   { display: none; text-align: center; padding: 28px; color: var(--muted); font-size: .9rem; }

    /* ════════════════════════════════════════
       RIGHT PANEL — Order / Receipt
    ════════════════════════════════════════ */
    #pos-order {
        background: #fff;
        border-radius: 16px;
        border: 1px solid var(--border);
        box-shadow: var(--shadow);
        display: flex;
        flex-direction: column;
        min-height: 0;
        overflow: hidden;
    }

    /* Customer row */
    #pos-customer-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 14px 16px;
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
    }
    .pos-avatar {
        width: 36px; height: 36px;
        border-radius: 50%;
        background: var(--surface-soft);
        border: 1.5px solid var(--border);
        display: flex; align-items: center; justify-content: center;
        color: var(--muted);
        flex-shrink: 0;
    }
    #pos-client-select {
        flex: 1;
        border: none;
        font-size: .92rem;
        font-weight: 600;
        background: transparent;
        cursor: pointer;
        outline: none;
        min-width: 0;
        color: var(--text);
        font-family: inherit;
    }
    .pos-icon-btn {
        width: 34px; height: 34px;
        border-radius: 8px;
        background: var(--surface-soft);
        border: 1px solid var(--border);
        display: flex; align-items: center; justify-content: center;
        cursor: pointer;
        color: var(--primary);
        flex-shrink: 0;
        transition: background .15s;
    }
    .pos-icon-btn:hover { background: #dde6f0; }

    /* Order items */
    #pos-items-wrap {
        flex: 1;
        overflow-y: auto;
        padding: 10px 14px 4px;
        min-height: 0;
    }
    #pos-items-empty {
        text-align: center;
        color: var(--muted);
        padding: 32px 0;
        font-size: .9rem;
    }

    .pos-item {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 10px 0;
        border-bottom: 1px solid #f0f4f8;
    }
    .pos-item:last-child { border-bottom: none; }
    .pos-item-info { flex: 1; min-width: 0; }
    .pos-item-name {
        font-size: .89rem;
        font-weight: 600;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .pos-item-each { font-size: .82rem; color: var(--muted); margin-top: 2px; }
    .pos-item-right { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; flex-shrink: 0; }
    .pos-item-amt { font-size: .92rem; font-weight: 700; }
    .pos-qty-row { display: flex; align-items: center; gap: 4px; }
    .pos-qty-btn {
        width: 24px; height: 24px;
        border-radius: 6px;
        border: 1.5px solid var(--border);
        background: #fff;
        font-size: .92rem; font-weight: 700;
        cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: background-color .1s, border-color .1s, color .1s;
        line-height: 1;
        font-family: inherit;
    }
    .pos-qty-btn:hover { background: var(--accent); border-color: var(--accent); color: #fff; }
    .pos-qty-num { min-width: 22px; text-align: center; font-size: .88rem; font-weight: 700; }

    /* Totals */
    #pos-totals {
        padding: 12px 16px;
        border-top: 1.5px solid var(--border);
        flex-shrink: 0;
    }
    .pos-tot-row {
        display: flex;
        justify-content: space-between;
        align-items: baseline;
        font-size: .88rem;
        padding: 3px 0;
        color: var(--muted);
    }
    .pos-tot-row.grand {
        font-size: 1.05rem; font-weight: 800;
        color: var(--text);
        padding-top: 9px;
        border-top: 1.5px solid var(--border);
        margin-top: 5px;
    }

    /* Voucher */
    #pos-voucher {
        padding: 10px 16px;
        border-top: 1px solid var(--border);
        flex-shrink: 0;
    }
    #pos-voucher-input {
        width: 100%;
        border: 1.5px dashed var(--border);
        border-radius: 10px;
        padding: 9px 14px;
        font-size: .87rem;
        color: var(--muted);
        outline: none;
        background: transparent;
        text-align: center;
        font-family: inherit;
    }

    /* Pay button */
    #pos-pay-btn {
        margin: 10px 16px 16px;
        padding: 14px;
        background: var(--accent);
        color: #fff;
        border: none;
        border-radius: 12px;
        font-size: 1rem; font-weight: 800;
        cursor: pointer;
        font-family: inherit;
        width: calc(100% - 32px);
        transition: opacity .15s;
        flex-shrink: 0;
        letter-spacing: .01em;
    }
    #pos-pay-btn:hover:not(:disabled) { opacity: .88; }
    #pos-pay-btn:disabled { opacity: .42; cursor: not-allowed; }
</style>

<div id="pos-wrap">

    {{-- ═══════════════════════════════════════
         LEFT PANEL — Product Catalog
    ═══════════════════════════════════════ --}}
    <div id="pos-catalog">
        <div id="pos-catalog-top">
            <div id="pos-search-row">
                <input type="text" id="pos-search" placeholder="Search all product here..." autocomplete="off">
                <button type="button" id="pos-search-btn">Search</button>
            </div>
        </div>

        <div id="pos-type-tabs">
            <button class="pos-type-tab active" data-type="">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 3h7v7H3V3zm0 11h7v7H3v-7zm11-11h7v7h-7V3zm0 11h7v7h-7v-7z"/></svg>
                All
            </button>
            @foreach($types as $t)
                <button class="pos-type-tab" data-type="{{ $t->id }}">{{ $t->name }}</button>
            @endforeach
        </div>

        <div id="pos-grid-wrap">
            <div id="pos-catalog-loading">Loading products…</div>
            <div id="pos-catalog-empty">No products found.</div>
            <div id="pos-product-grid"></div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════
         RIGHT PANEL — Order / Receipt
    ═══════════════════════════════════════ --}}
    <div id="pos-order">

        {{-- Customer selector --}}
        <div id="pos-customer-row">
            <div class="pos-avatar">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8V21h19.2v-1.8c0-3.2-6.4-4.8-9.6-4.8z"/></svg>
            </div>
            <select id="pos-client-select" aria-label="Select customer">
                <option value="">Select customer</option>
                @foreach($clients as $c)
                    <option value="{{ $c->client_no }}" data-discount="{{ $c->discount }}">
                        {{ $c->client_name }}
                    </option>
                @endforeach
            </select>
            <button type="button" class="pos-icon-btn" id="pos-client-edit-btn" title="Edit / view customer">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zm17.71-10.21a1 1 0 0 0 0-1.41l-2.34-2.34a1 1 0 0 0-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
            </button>
        </div>

        {{-- Order items list --}}
        <div id="pos-items-wrap">
            <div id="pos-items-empty">No items added yet.</div>
        </div>

        {{-- Totals --}}
        <div id="pos-totals">
            <div class="pos-tot-row">
                <span>Subtotal</span>
                <span id="pos-subtotal">$ 0.00</span>
            </div>
            <div class="pos-tot-row" id="pos-disc-row" style="display:none;">
                <span id="pos-disc-label">Discount (0%)</span>
                <span id="pos-discount">-$ 0.00</span>
            </div>
            <div class="pos-tot-row grand">
                <span>Total</span>
                <span id="pos-total">$ 0.00</span>
            </div>
        </div>

        {{-- Voucher (decorative) --}}
        <div id="pos-voucher">
            <input type="text" id="pos-voucher-input" placeholder="Add Voucher Code" disabled>
        </div>

        {{-- Pay / Print Receipt --}}
        <button type="button" id="pos-pay-btn" disabled>Print Receipt</button>
    </div>
</div>

{{-- ═══════════════════════════════════════
     HIDDEN NEW-INVOICE FORM
═══════════════════════════════════════ --}}
<form id="pos-invoice-form" method="POST" action="{{ route('invoices.store') }}" style="display:none;">
    @csrf
    <input type="hidden" name="invoice_status" value="In Process">
    <input id="pf-client"    type="hidden" name="client_no"        value="">
    <input id="pf-pay-amt"   type="hidden" name="payment_amount"   value="">
    <input id="pf-recv-amt"  type="hidden" name="recieve_amount"   value="">
    <input id="pf-currency"  type="hidden" name="payment_currency" value="{{ $initialPaymentCurrency }}">
    <input id="pf-pay-type"  type="hidden" name="payment_type"     value="cash">
    <input id="pf-memo"      type="hidden" name="invoice_memo"     value="">
    <div  id="pf-items"></div>
</form>

{{-- ═══════════════════════════════════════
     PAYMENT TYPE MODAL
═══════════════════════════════════════ --}}
<div id="pt-modal"
     style="position:fixed;inset:0;background:rgba(10,14,20,.55);display:none;align-items:center;justify-content:center;z-index:1050;padding:16px;"
     aria-hidden="true">
    <div class="card" role="dialog" aria-modal="true" style="width:min(380px,100%);margin:0;text-align:center;">
        <h3 style="margin-top:0;">Payment Type</h3>
        <p class="subtle" style="margin-bottom:18px;">Choose how the customer will pay</p>
        <div class="actions" style="justify-content:center;gap:12px;">
            <button type="button" id="pt-cash-btn" class="btn btn-muted" style="min-width:120px;font-size:1.05rem;">Cash</button>
            <button type="button" id="pt-qr-btn"   class="btn btn-primary" style="min-width:120px;font-size:1.05rem;">QR</button>
        </div>
        <div style="margin-top:14px;">
            <button type="button" id="pt-cancel-btn" class="btn btn-muted" style="font-size:.9rem;">Cancel</button>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════
     PAYMENT DETAILS MODAL
═══════════════════════════════════════ --}}
<div id="pm-modal"
     style="position:fixed;inset:0;background:rgba(10,14,20,.55);display:none;align-items:center;justify-content:center;z-index:1060;padding:16px;"
     aria-hidden="true">
    <div class="card" role="dialog" aria-modal="true" style="width:min(460px,100%);margin:0;">
        <h3 style="margin-top:0;">Payment Summary</h3>
        <div style="display:grid;gap:12px;margin-top:12px;">
            <div class="actions" style="justify-content:space-between;align-items:baseline;">
                <strong>Amount:</strong>
                <span id="pm-amount" style="font-weight:700;">$0.00</span>
            </div>
            <div>
                <label for="pm-currency">Currency Type</label>
                <select id="pm-currency">
                    @forelse($paymentCurrencies as $currency)
                        @php
                            $cv = (string) ($currency['value'] ?? '');
                            $cl = (string) ($currency['label'] ?? $cv);
                            $cr = is_numeric($currency['rate_to_usd'] ?? null) ? (float) $currency['rate_to_usd'] : 1;
                            $cc = trim((string) ($currency['code'] ?? 'USD'));
                        @endphp
                        @if($cv !== '')
                            <option value="{{ $cv }}"
                                    data-rate="{{ number_format($cr, 6, '.', '') }}"
                                    data-code="{{ $cc ?: 'USD' }}"
                                    @selected($initialPaymentCurrency === $cv)>
                                {{ $cl }}
                            </option>
                        @endif
                    @empty
                        <option value="{{ $defaultPaymentCurrency }}" data-rate="1.000000" data-code="USD" selected>
                            {{ $defaultPaymentCurrency }}
                        </option>
                    @endforelse
                </select>
                <div class="subtle" id="pm-rate-note" style="margin-top:6px;"></div>
            </div>
            <div>
                <label for="pm-receive">Receive Amount</label>
                <div style="display:flex;gap:6px;align-items:stretch;">
                    <input id="pm-receive" type="number" min="0" step="0.01" placeholder="0.00" autocomplete="off" style="flex:1;min-width:0;">
                    <button type="button" id="pm-max-btn" style="padding:6px 14px;border:1px solid #ccc;background:#f0f0f0;border-radius:6px;font-weight:600;cursor:pointer;font-family:inherit;">Max</button>
                </div>
                <div id="pm-qr-warn" style="display:none;color:#d32f2f;font-size:.88rem;margin-top:4px;font-weight:600;">
                    Received amount cannot exceed the invoice amount for QR payment.
                </div>
            </div>
            <div class="actions" id="pm-debt-row" style="justify-content:space-between;align-items:baseline;">
                <strong>Debt:</strong>
                <span id="pm-debt" style="font-weight:700;">$0.00</span>
            </div>
            <div class="actions" id="pm-change-row" style="justify-content:space-between;align-items:baseline;display:none;">
                <strong>Change:</strong>
                <span id="pm-change" style="font-weight:700;">$0.00</span>
            </div>
        </div>
        <div class="actions" style="justify-content:flex-end;gap:10px;margin-top:18px;">
            <button type="button" class="btn btn-muted"    id="pm-cancel-btn">Cancel</button>
            <button type="button" class="btn btn-primary"  id="pm-submit-btn">Submit</button>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════
     BAKONG QR MODAL
═══════════════════════════════════════ --}}
@if(session('bakong_qr'))
    @php
        $bakongQrCurrency = mb_strtoupper((string) session('bakong_qr_currency', 'USD'));
        $bakongQrDecimals = $bakongQrCurrency === 'KHR' ? 0 : 2;
        $bakongQrPrefix   = $bakongQrCurrency === 'USD' ? '$' : '';
    @endphp
    <div id="bakong-qr-modal"
         style="position:fixed;inset:0;background:rgba(10,14,20,.55);display:flex;align-items:center;justify-content:center;z-index:1100;padding:16px;">
        <div class="card" role="dialog" aria-modal="true" style="width:min(420px,100%);margin:0;text-align:center;">
            <div style="display:flex;align-items:baseline;justify-content:center;gap:6px;margin-bottom:16px;">
                <strong style="font-size:1.6rem;color:#1a6b3c;">{{ $bakongQrPrefix }}{{ number_format((float) session('bakong_qr_amount', 0), $bakongQrDecimals) }}</strong>
                <span style="font-size:.95rem;color:#888;">{{ $bakongQrCurrency }}</span>
            </div>
            <div id="bakong-qr-container" style="display:inline-block;padding:12px;background:#fff;border-radius:12px;border:2px solid #e5e7eb;"></div>
            <div style="margin-top:16px;">
                <button type="button" id="bakong-qr-close-btn" class="btn btn-primary"
                        onclick="document.getElementById('bakong-qr-modal').style.display='none';"
                        style="min-width:120px;">Close</button>
            </div>
        </div>
        <script src="https://cdn.jsdelivr.net/npm/qrcode-generator@1.4.4/qrcode.min.js"></script>
        <script>
            (() => {
                const qrString = @json(session('bakong_qr'));
                if (!qrString) return;
                const qr = qrcode(0, 'M');
                qr.addData(qrString);
                qr.make();
                const container = document.getElementById('bakong-qr-container');
                if (container) {
                    container.innerHTML = qr.createSvgTag({ cellSize: 4, margin: 0, scalable: true });
                    const svg = container.querySelector('svg');
                    if (svg) { svg.style.width = '260px'; svg.style.height = '260px'; }
                }

                const qrMd5 = @json(session('bakong_qr_md5', md5(session('bakong_qr', ''))));
                const checkBaseUrl = @json(route('bakong.check_transaction'));
                const bakongQrGrandTotal = @json((float) session('bakong_qr_grand_total', 0));
                const checkUrl = checkBaseUrl + '?md5=' + encodeURIComponent(qrMd5) + '&grand_total=' + encodeURIComponent(bakongQrGrandTotal);
                const fallbackAmount   = @json((float) session('bakong_qr_amount', 0));
                const fallbackCurrency = @json($bakongQrCurrency);
                let pollTimer = null;

                const stopPolling = () => { if (pollTimer) { clearInterval(pollTimer); pollTimer = null; } };

                const showReceiveToast = (text) => {
                    const main = document.querySelector('main.content');
                    if (!main) return;
                    const toast = document.createElement('div');
                    toast.className = 'flash success toast';
                    toast.innerHTML = `<span class="toast-ico" aria-hidden="true"><svg width="14" height="14" viewBox="0 0 24 24" focusable="false"><path fill="currentColor" d="M9.55 17.55L4.5 12.5l1.4-1.4 3.65 3.65 8.05-8.05 1.4 1.4z"/></svg></span><span>${text}</span>`;
                    main.insertBefore(toast, main.firstChild);
                };

                const poll = async () => {
                    try {
                        const res = await fetch(checkUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        if (!res.ok) return;
                        const data = await res.json();
                        if (data.paid) {
                            stopPolling();
                            const modal = document.getElementById('bakong-qr-modal');
                            if (modal) modal.style.display = 'none';
                            const currency = (data.currency || fallbackCurrency).toUpperCase();
                            const decimals = currency === 'KHR' ? 0 : 2;
                            const prefix   = currency === 'USD' ? '$' : '';
                            const amount   = data.amount != null
                                ? parseFloat(data.amount).toFixed(decimals)
                                : parseFloat(fallbackAmount).toFixed(decimals);
                            showReceiveToast('Receive: ' + prefix + amount + ' ' + currency);
                        }
                    } catch (_) {}
                };

                pollTimer = setInterval(poll, 3000);
                document.getElementById('bakong-qr-close-btn')?.addEventListener('click', stopPolling);
            })();
        </script>
    </div>
@endif

{{-- ═══════════════════════════════════════
     POS JAVASCRIPT
═══════════════════════════════════════ --}}
<script>
(() => {
    'use strict';

    /* ── Endpoints ── */
    const CATALOG_URL  = @json(parse_url(route('invoices.products'), PHP_URL_PATH));
    const INIT_CURRENCY = @json($initialPaymentCurrency);

    /* ── DOM — Catalog ── */
    const searchInput   = document.getElementById('pos-search');
    const searchBtn     = document.getElementById('pos-search-btn');
    const typeTabs      = document.querySelectorAll('.pos-type-tab');
    const productGrid   = document.getElementById('pos-product-grid');
    const loadingEl     = document.getElementById('pos-catalog-loading');
    const emptyEl       = document.getElementById('pos-catalog-empty');

    /* ── DOM — Order ── */
    const clientSelect  = document.getElementById('pos-client-select');
    const itemsWrap     = document.getElementById('pos-items-wrap');
    const itemsEmpty    = document.getElementById('pos-items-empty');
    const subtotalEl    = document.getElementById('pos-subtotal');
    const discRow       = document.getElementById('pos-disc-row');
    const discLabel     = document.getElementById('pos-disc-label');
    const discountEl    = document.getElementById('pos-discount');
    const totalEl       = document.getElementById('pos-total');
    const payBtn        = document.getElementById('pos-pay-btn');

    /* ── DOM — Form ── */
    const invoiceForm   = document.getElementById('pos-invoice-form');
    const pfClient      = document.getElementById('pf-client');
    const pfPayAmt      = document.getElementById('pf-pay-amt');
    const pfRecvAmt     = document.getElementById('pf-recv-amt');
    const pfCurrency    = document.getElementById('pf-currency');
    const pfPayType     = document.getElementById('pf-pay-type');
    const pfItems       = document.getElementById('pf-items');

    /* ── DOM — Payment Type Modal ── */
    const ptModal       = document.getElementById('pt-modal');
    const ptCashBtn     = document.getElementById('pt-cash-btn');
    const ptQrBtn       = document.getElementById('pt-qr-btn');
    const ptCancelBtn   = document.getElementById('pt-cancel-btn');

    /* ── DOM — Payment Details Modal ── */
    const pmModal       = document.getElementById('pm-modal');
    const pmAmount      = document.getElementById('pm-amount');
    const pmCurrency    = document.getElementById('pm-currency');
    const pmRateNote    = document.getElementById('pm-rate-note');
    const pmReceive     = document.getElementById('pm-receive');
    const pmMaxBtn      = document.getElementById('pm-max-btn');
    const pmDebtRow     = document.getElementById('pm-debt-row');
    const pmDebt        = document.getElementById('pm-debt');
    const pmChangeRow   = document.getElementById('pm-change-row');
    const pmChange      = document.getElementById('pm-change');
    const pmQrWarn      = document.getElementById('pm-qr-warn');
    const pmCancelBtn   = document.getElementById('pm-cancel-btn');
    const pmSubmitBtn   = document.getElementById('pm-submit-btn');

    /* ── State ── */
    let cart            = [];   // [{product_no, product_name, price, qty, qty_on_hand}]
    let selectedType    = '';
    let selectedPayType = 'cash';
    let grandTotal      = 0;

    /* ── Helpers ── */
    const esc   = (s) => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    const r2    = (v) => Math.round((v + Number.EPSILON) * 100) / 100;
    const money = (v) => `$ ${Number.isFinite(v) ? v.toFixed(2) : '0.00'}`;

    const discountRate = () => {
        const sel = clientSelect?.options[clientSelect.selectedIndex];
        const raw = Number(sel?.dataset?.discount || 0);
        if (!raw || raw <= 0) return 0;
        if (raw < 1)   return raw;
        if (raw <= 10) return raw / 10;
        return raw / 100;
    };

    const currencyRate = () => {
        if (!pmCurrency) return 1;
        const sel = pmCurrency.options[pmCurrency.selectedIndex];
        const r = Number(sel?.dataset?.rate || 1);
        return (Number.isFinite(r) && r > 0) ? r : 1;
    };

    const currencyCode = () => {
        if (!pmCurrency) return 'USD';
        const sel = pmCurrency.options[pmCurrency.selectedIndex];
        return String(sel?.dataset?.code || '').trim() || 'USD';
    };

    const fmtCurrency = (v) => {
        const code = currencyCode();
        if (code === 'KHR') return `${Math.round(v).toLocaleString()} ${code}`;
        return `${v.toFixed(2)} ${code}`;
    };

    /* ── Update totals ── */
    const updateTotals = () => {
        const sub  = r2(cart.reduce((s, i) => s + i.price * i.qty, 0));
        const dr   = discountRate();
        const disc = r2(sub * dr);
        grandTotal = r2(Math.max(0, sub - disc));

        subtotalEl.textContent = money(sub);
        totalEl.textContent    = money(grandTotal);

        if (disc > 0) {
            discRow.style.display   = '';
            discLabel.textContent   = `Discount (${(dr * 100).toFixed(0)}%)`;
            discountEl.textContent  = `-${money(disc)}`;
        } else {
            discRow.style.display = 'none';
        }

        payBtn.disabled = cart.length === 0 || !clientSelect?.value;
    };

    /* ── Render cart items ── */
    const renderCart = () => {
        Array.from(itemsWrap.querySelectorAll('.pos-item')).forEach(el => el.remove());
        itemsEmpty.style.display = cart.length === 0 ? '' : 'none';

        cart.forEach((item, idx) => {
            const div = document.createElement('div');
            div.className = 'pos-item';
            div.innerHTML = `
                <div class="pos-item-info">
                    <div class="pos-item-name" title="${esc(item.product_name)}">${esc(item.product_name)}</div>
                    <div class="pos-item-each">$ ${item.price.toFixed(2)} each</div>
                </div>
                <div class="pos-item-right">
                    <span class="pos-item-amt">$ ${(item.price * item.qty).toFixed(2)}</span>
                    <div class="pos-qty-row">
                        <button class="pos-qty-btn" data-action="dec" data-idx="${idx}" title="Decrease">−</button>
                        <span class="pos-qty-num">${item.qty}</span>
                        <button class="pos-qty-btn" data-action="inc" data-idx="${idx}" title="Increase">+</button>
                        <button class="pos-qty-btn" data-action="del" data-idx="${idx}"
                                title="Remove" style="margin-left:2px;color:var(--danger);border-color:var(--danger);">×</button>
                    </div>
                </div>`;
            itemsWrap.appendChild(div);
        });

        updateTotals();
    };

    /* ── Add product to cart ── */
    const addToCart = (product) => {
        if (product.qty_on_hand <= 0) return;
        const existing = cart.find(i => i.product_no === product.product_no);
        if (existing) {
            if (existing.qty < product.qty_on_hand) existing.qty++;
        } else {
            cart.push({ product_no: product.product_no, product_name: product.product_name,
                        price: product.sell_price, qty: 1, qty_on_hand: product.qty_on_hand });
        }
        renderCart();
    };

    /* ── Cart quantity interactions ── */
    itemsWrap.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-action]');
        if (!btn) return;
        const idx    = parseInt(btn.dataset.idx, 10);
        const action = btn.dataset.action;
        if (!Number.isFinite(idx) || idx < 0 || idx >= cart.length) return;

        if (action === 'inc') {
            if (cart[idx].qty < cart[idx].qty_on_hand) cart[idx].qty++;
        } else if (action === 'dec') {
            if (cart[idx].qty <= 1) cart.splice(idx, 1);
            else cart[idx].qty--;
        } else if (action === 'del') {
            cart.splice(idx, 1);
        }
        renderCart();
    });

    clientSelect?.addEventListener('change', updateTotals);

    /* ── Render product cards ── */
    const renderProducts = (products) => {
        productGrid.innerHTML = '';
        emptyEl.style.display = products.length === 0 ? '' : 'none';

        const imgPlaceholder = `<div class="pos-card-img-ph">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                <path d="M21 3H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H3V5h18v14zM8.5 13.5l2.5 3 3.5-4.5 4.5 6H5l3.5-4.5z"/>
            </svg></div>`;

        const svgLeft  = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;display:block;"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/></svg>`;
        const svgRight = `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;display:block;"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>`;

        products.forEach(p => {
            const card = document.createElement('div');
            card.className = 'pos-card' + (p.qty_on_hand <= 0 ? ' oos' : '');
            card.title     = p.product_name + (p.qty_on_hand <= 0 ? ' (Out of stock)' : '');

            const imgHtml = p.photo_url
                ? `<img class="pos-card-img" src="${esc(p.photo_url)}" alt="${esc(p.product_name)}" loading="lazy">`
                : imgPlaceholder;

            const photoBlock = p.photo_url
                ? `<div class="pos-card-img-wrap">
                    <button type="button" class="pos-card-photo-prev" tabindex="-1">${svgLeft}</button>
                    ${imgHtml}
                    <button type="button" class="pos-card-photo-next" tabindex="-1">${svgRight}</button>
                   </div>`
                : imgHtml;

            card.innerHTML = `${photoBlock}
                <div class="pos-card-name">${esc(p.product_name)}</div>
                <div class="pos-card-price">$ ${p.sell_price.toFixed(2)}</div>`;

            if (p.photo_url) {
                const wrap  = card.querySelector('.pos-card-img-wrap');
                const img   = wrap.querySelector('.pos-card-img');
                img.addEventListener('error', function() { this.outerHTML = imgPlaceholder; }, { once: true });
                const prev  = wrap.querySelector('.pos-card-photo-prev');
                const next  = wrap.querySelector('.pos-card-photo-next');
                let photos  = [{ photo_id: 0, url: p.photo_url }];
                let idx = 0, loaded = false;

                const loadPhotos = async () => {
                    if (loaded) return;
                    loaded = true;
                    try {
                        const resp = await fetch(`/products/${encodeURIComponent(p.product_no)}/photos`, {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        });
                        if (resp.ok) {
                            const json = await resp.json();
                            if (json && Array.isArray(json.photos) && json.photos.length > 0) {
                                photos = json.photos;
                                const m = photos.findIndex(ph => ph.url === img.src);
                                idx = m >= 0 ? m : 0;
                            }
                        }
                    } catch (_) {}
                    if (photos.length <= 1) { prev.style.display = 'none'; next.style.display = 'none'; }
                };

                const showPhoto = (i) => {
                    if (!photos.length) return;
                    idx = ((i % photos.length) + photos.length) % photos.length;
                    img.src = photos[idx].url;
                };

                prev.addEventListener('click', async (e) => { e.stopPropagation(); await loadPhotos(); if (photos.length > 1) showPhoto(idx - 1); });
                next.addEventListener('click', async (e) => { e.stopPropagation(); await loadPhotos(); if (photos.length > 1) showPhoto(idx + 1); });
            }

            if (p.qty_on_hand > 0) {
                card.addEventListener('click', () => addToCart(p));
            }
            productGrid.appendChild(card);
        });
    };

    /* ── Fetch products (AJAX) ── */
    let abortCtrl = null;

    const fetchProducts = async (q = '', type = '') => {
        if (abortCtrl) abortCtrl.abort();
        abortCtrl = new AbortController();

        loadingEl.style.display  = '';
        productGrid.style.display = 'none';
        emptyEl.style.display    = 'none';

        const url = new URL(CATALOG_URL, window.location.origin);
        if (q.trim())  url.searchParams.set('q',    q.trim());
        if (type)      url.searchParams.set('type', type);

        try {
            const res  = await fetch(url.toString(), {
                signal: abortCtrl.signal,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();
            renderProducts(data.products || []);
        } catch (err) {
            if (err.name !== 'AbortError') {
                productGrid.innerHTML = `<div style="color:var(--danger);padding:12px;grid-column:1/-1;">Failed to load products. Please try again.</div>`;
                emptyEl.style.display = 'none';
            }
        } finally {
            loadingEl.style.display   = 'none';
            productGrid.style.display = '';
        }
    };

    /* ── Category tabs ── */
    typeTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            typeTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            selectedType = tab.dataset.type || '';
            fetchProducts(searchInput.value, selectedType);
        });
    });

    /* ── Search ── */
    let searchTimer = null;
    const doSearch  = () => fetchProducts(searchInput.value, selectedType);

    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(doSearch, 350);
    });
    searchBtn.addEventListener('click',  () => { clearTimeout(searchTimer); doSearch(); });
    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') { clearTimeout(searchTimer); doSearch(); }
    });

    /* ══════════════════════════════════════
       PAYMENT MODALS
    ══════════════════════════════════════ */
    /* Payment type modal */
    const openPtModal  = () => { ptModal.style.display = 'flex'; ptModal.setAttribute('aria-hidden', 'false'); };
    const closePtModal = () => { ptModal.style.display = 'none'; ptModal.setAttribute('aria-hidden', 'true'); };

    /* Payment details modal */
    const updateRateNote = () => {
        if (!pmRateNote) return;
        const r = currencyRate(), c = currencyCode();
        pmRateNote.textContent = `1 USD = ${r.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})} ${c}`;
    };

    const updatePmAmount = () => {
        const amt = r2(grandTotal * currencyRate());
        pmAmount.textContent = fmtCurrency(amt);
    };

    const updatePmDebt = () => {
        const amtInC  = r2(grandTotal * currencyRate());
        const recv    = Math.max(0, Number(pmReceive?.value || 0) || 0);
        const debt    = r2(Math.max(0, amtInC - recv));
        const change  = r2(Math.max(0, recv - amtInC));

        pmDebt.textContent   = fmtCurrency(debt);
        pmChange.textContent = fmtCurrency(change);
        pmDebtRow.style.display   = debt   > 0 ? '' : 'none';
        pmChangeRow.style.display = change > 0 ? '' : 'none';

        if (selectedPayType === 'qr') {
            const over = recv > amtInC;
            pmQrWarn.style.display  = over ? '' : 'none';
            pmSubmitBtn.disabled    = over;
        } else {
            pmQrWarn.style.display  = 'none';
            pmSubmitBtn.disabled    = false;
        }
    };

    const openPmModal = () => {
        updateRateNote();
        updatePmAmount();
        pmReceive.value = r2(grandTotal * currencyRate()).toFixed(2);
        updatePmDebt();
        pmModal.style.display = 'flex';
        pmModal.setAttribute('aria-hidden', 'false');
        pmReceive?.focus();
    };
    const closePmModal = () => {
        pmModal.style.display = 'none';
        pmModal.setAttribute('aria-hidden', 'true');
    };

    pmCurrency?.addEventListener('change',  () => { updateRateNote(); updatePmAmount(); updatePmDebt(); });
    pmReceive?.addEventListener('input',    updatePmDebt);
    pmMaxBtn?.addEventListener('click', () => {
        pmReceive.value = r2(grandTotal * currencyRate()).toFixed(2);
        updatePmDebt();
    });
    pmCancelBtn?.addEventListener('click',  closePmModal);
    ptCancelBtn?.addEventListener('click',  closePtModal);

    /* Pay button triggers payment type selection */
    payBtn.addEventListener('click', openPtModal);

    const onSelectPayType = (type) => {
        selectedPayType = type;
        pfPayType.value = type;
        closePtModal();
        openPmModal();
    };
    ptCashBtn?.addEventListener('click', () => onSelectPayType('cash'));
    ptQrBtn?.addEventListener('click',   () => onSelectPayType('qr'));

    /* Submit payment → populate form → submit */
    pmSubmitBtn?.addEventListener('click', () => {
        const rate   = currencyRate();
        const amtInC = r2(grandTotal * rate);
        const recv   = Math.max(0, Number(pmReceive?.value || 0) || 0);

        pfClient.value   = clientSelect.value;
        pfPayAmt.value   = amtInC.toFixed(2);
        pfRecvAmt.value  = recv.toFixed(2);
        pfCurrency.value = pmCurrency ? String(pmCurrency.value).trim() : INIT_CURRENCY;
        pfPayType.value  = selectedPayType;

        pfItems.innerHTML = '';
        cart.forEach((item, i) => {
            const pn = document.createElement('input');
            pn.type  = 'hidden'; pn.name = `items[${i}][product_no]`; pn.value = item.product_no;
            const qn = document.createElement('input');
            qn.type  = 'hidden'; qn.name = `items[${i}][qty]`;        qn.value = String(item.qty);
            pfItems.appendChild(pn);
            pfItems.appendChild(qn);
        });

        closePmModal();
        invoiceForm.submit();
    });

    /* Backdrop / Escape close */
    ptModal?.addEventListener('click', (e) => { if (e.target === ptModal) closePtModal(); });
    pmModal?.addEventListener('click', (e) => { if (e.target === pmModal) closePmModal(); });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (ptModal?.style.display === 'flex')  closePtModal();
            else if (pmModal?.style.display === 'flex') closePmModal();
        }
    });

    /* ── Initial load ── */
    fetchProducts();
    updateTotals();

})();
</script>
@endsection
