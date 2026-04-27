@extends('layouts.ecommerce')

@section('title', 'Total Sales')

@section('content')
    @php
        $rows = $sales->getCollection();

        $pageInvoices = (int) $rows->count();
        $pageItems = (float) $rows->sum(fn (object $row): float => (float) ($row->item_qty ?? 0));
        $pageSubtotal = (float) $rows->sum(fn (object $row): float => (float) ($row->subtotal ?? 0));
        $pageDiscount = (float) $rows->sum(fn (object $row): float => (float) ($row->discount_amount ?? 0));
        $pageBalance = (float) $rows->sum(fn (object $row): float => (float) ($row->balance ?? 0));

        $avgInvoiceSubtotal = $pageInvoices > 0 ? ($pageSubtotal / $pageInvoices) : 0.0;
        $avgDiscountRate = $pageInvoices > 0
            ? (float) $rows->avg(fn (object $row): float => (float) (($row->discount_rate ?? 0) * 100))
            : 0.0;

        $statusCounts = [
            'paid' => 0,
            'in_debt' => 0,
            'other' => 0,
        ];

        foreach ($rows as $row) {
            $statusKey = mb_strtoupper(trim((string) ($row->invoice_status ?? '')));
            if ($statusKey === 'PAID') {
                $statusCounts['paid']++;
            } elseif ($statusKey === 'IN DEBT') {
                $statusCounts['in_debt']++;
            } else {
                $statusCounts['other']++;
            }
        }

        $paidCount = (int) $statusCounts['paid'];
        $inDebtCount = (int) $statusCounts['in_debt'];
        $otherCount = (int) $statusCounts['other'];

        $pageFrom = $sales->count() > 0 ? (int) ($sales->firstItem() ?? 0) : 0;
        $pageTo = $sales->count() > 0 ? (int) ($sales->lastItem() ?? 0) : 0;

        $activeFilterCount = (int) (
            ($fromDate ? 1 : 0)
            + ($toDate ? 1 : 0)
            + ($clientNo ? 1 : 0)
            + ((trim((string) ($q ?? '')) !== '') ? 1 : 0)
        );

        $metricCards = [
            ['label' => 'Invoices', 'value' => number_format($pageInvoices), 'note' => 'Rows on current page', 'tone' => 'blue'],
            ['label' => 'Items', 'value' => number_format($pageItems), 'note' => 'Total item quantity', 'tone' => 'green'],
            ['label' => 'Subtotal', 'value' => '$'.number_format($pageSubtotal, 2), 'note' => 'Before discounts', 'tone' => 'cyan'],
            ['label' => 'Discount', 'value' => '$'.number_format($pageDiscount, 2), 'note' => 'Discounted amount', 'tone' => 'purple'],
            ['label' => 'Balance', 'value' => '$'.number_format($pageBalance, 2), 'note' => 'Net receivable', 'tone' => 'amber'],
        ];
    @endphp

    <style>
        .salesclean {
            --sc-navy: #0e2a47;
            --sc-blue: #0f5f97;
            --sc-cyan: #0e8fa6;
            --sc-amber: #d1811f;
            --sc-ink: #10273d;
            --sc-muted: #60768f;
            --sc-surface: #ffffff;
            --sc-border: #d6e2ee;
            --sc-shadow: 0 16px 40px rgba(14, 42, 71, 0.12);
            display: grid;
            gap: 14px;
        }

        .salesclean * { min-width: 0; }

        .salesclean-hero {
            position: relative;
            overflow: hidden;
            border-radius: 18px;
            padding: 22px;
            display: grid;
            grid-template-columns: minmax(0, 1.4fr) auto;
            gap: 14px;
            color: #edf6ff;
            background:
                radial-gradient(circle at 12% 24%, rgba(255, 255, 255, 0.16), transparent 36%),
                radial-gradient(circle at 88% 20%, rgba(209, 129, 31, 0.34), transparent 40%),
                linear-gradient(140deg, #0d2742 0%, #0f5b8e 48%, #0a7aa8 100%);
            box-shadow: 0 22px 44px rgba(11, 34, 57, 0.22);
        }

        .salesclean-kicker {
            margin: 0;
            font-size: .72rem;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: rgba(236, 246, 255, 0.9);
            font-weight: 800;
        }

        .salesclean-title {
            margin: 6px 0 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: clamp(1.75rem, 2.8vw, 2.35rem);
            line-height: 1.1;
        }

        .salesclean-sub {
            margin: 10px 0 0;
            max-width: 620px;
            color: rgba(236, 246, 255, 0.9);
            font-size: .94rem;
            line-height: 1.5;
        }

        .salesclean-pill-row {
            margin-top: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .salesclean-pill {
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.24);
            background: rgba(255, 255, 255, 0.14);
            color: #f3faff;
            font-size: .74rem;
            font-weight: 700;
            padding: 5px 10px;
        }

        .salesclean-hero-actions {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: flex-start;
            gap: 8px;
        }

        .salesclean-hero-actions .btn {
            border-radius: 10px;
            font-size: .82rem;
            padding: 8px 12px;
        }

        .salesclean-hero-actions .btn-primary {
            background: linear-gradient(140deg, #f4a21c, #f5bf4d);
            color: #192733;
            box-shadow: 0 10px 20px rgba(16, 31, 46, 0.2);
        }

        .salesclean-hero-actions .btn-muted {
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.08);
            color: #f1f8ff;
        }

        .salesclean-metrics {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
        }

        .salesclean-metric {
            border: 1px solid var(--sc-border);
            border-radius: 14px;
            padding: 12px 13px;
            background: linear-gradient(165deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: var(--sc-shadow);
            position: relative;
            overflow: hidden;
        }

        .salesclean-metric::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--metric-tone, #0f5f97);
        }

        .salesclean-metric.tone-blue { --metric-tone: #0f5f97; }
        .salesclean-metric.tone-green { --metric-tone: #2c7a5a; }
        .salesclean-metric.tone-cyan { --metric-tone: #0e8fa6; }
        .salesclean-metric.tone-purple { --metric-tone: #8b56c8; }
        .salesclean-metric.tone-amber { --metric-tone: #d1811f; }

        .salesclean-metric-label {
            margin: 0;
            font-size: .69rem;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: var(--sc-muted);
            font-weight: 700;
        }

        .salesclean-metric-value {
            margin: 6px 0 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: clamp(1.04rem, 1.8vw, 1.36rem);
            line-height: 1.1;
            color: var(--sc-ink);
            font-variant-numeric: tabular-nums;
        }

        .salesclean-metric-note {
            margin: 4px 0 0;
            font-size: .78rem;
            color: var(--sc-muted);
        }

        .salesclean-panel {
            border: 1px solid var(--sc-border);
            border-radius: 16px;
            background: var(--sc-surface);
            box-shadow: var(--sc-shadow);
            padding: 16px;
            display: grid;
            gap: 12px;
        }

        .salesclean-panel-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            flex-wrap: wrap;
        }

        .salesclean-panel-head h2 {
            margin: 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: 1.03rem;
            color: var(--sc-ink);
        }

        .salesclean-panel-head p {
            margin: 4px 0 0;
            color: var(--sc-muted);
            font-size: .86rem;
        }

        .salesclean-badge {
            border-radius: 999px;
            border: 1px solid #d4e6f8;
            background: #eef6ff;
            color: #1b4f7d;
            padding: 4px 10px;
            font-size: .74rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .05em;
            white-space: nowrap;
        }

        .salesclean-filter-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            align-items: end;
        }

        .salesclean-col-date,
        .salesclean-col-client {
            grid-column: span 2;
        }

        .salesclean-col-search {
            grid-column: span 4;
        }

        .salesclean-col-actions {
            grid-column: span 2;
            display: inline-flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .salesclean-filter-grid label {
            display: block;
            margin-bottom: 6px;
            color: #1c2f46;
            font-size: .78rem;
            letter-spacing: .03em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .salesclean-filter-grid input,
        .salesclean-filter-grid select {
            width: 100%;
            border-radius: 10px;
            border: 1px solid #c8d9ea;
            background: #fbfdff;
            color: #1b2a40;
            font-size: .9rem;
            padding: 9px 10px;
        }

        .salesclean-filter-grid input:focus,
        .salesclean-filter-grid select:focus {
            outline: none;
            border-color: #0f5f97;
            box-shadow: 0 0 0 4px rgba(15, 95, 151, 0.15);
        }

        .salesclean-col-actions .btn {
            border-radius: 10px;
            padding: 9px 12px;
        }

        .salesclean-table-card {
            border: 1px solid var(--sc-border);
            border-radius: 16px;
            background: #fff;
            box-shadow: var(--sc-shadow);
            overflow: hidden;
        }

        .salesclean-table-head {
            padding: 14px 16px;
            border-bottom: 1px solid #dbe7f2;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .salesclean-table-head h2 {
            margin: 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: 1.04rem;
            color: var(--sc-ink);
        }

        .salesclean-table-head p {
            margin: 3px 0 0;
            color: var(--sc-muted);
            font-size: .84rem;
        }

        .salesclean-status-meta {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .salesclean-status-chip {
            border-radius: 999px;
            border: 1px solid #d4e0ee;
            background: #f4f8fd;
            color: #24435f;
            padding: 4px 10px;
            font-size: .73rem;
            font-weight: 700;
        }

        .salesclean-status-chip.paid {
            border-color: #b9dfca;
            background: #ebfbf2;
            color: #206844;
        }

        .salesclean-status-chip.debt {
            border-color: #f1ca98;
            background: #fff6eb;
            color: #9c5d16;
        }

        .salesclean-table-wrap {
            overflow-x: auto;
        }

        .salesclean-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1080px;
        }

        .salesclean-table th,
        .salesclean-table td {
            padding: 10px 11px;
            border-bottom: 1px solid #dbe7f2;
            vertical-align: middle;
            font-size: .88rem;
        }

        .salesclean-table th {
            position: sticky;
            top: 0;
            z-index: 1;
            background: #f2f7fd;
            color: #33485f;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .74rem;
            font-family: "Space Grotesk", sans-serif;
        }

        .salesclean-table tbody tr:hover {
            background: #f8fbff;
        }

        .salesclean-num {
            text-align: right;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .salesclean-invoice {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-weight: 800;
            color: #0f3254;
        }

        .salesclean-invoice span {
            border-radius: 999px;
            border: 1px solid #cfdef0;
            background: #edf5ff;
            color: #214c72;
            font-size: .7rem;
            font-weight: 700;
            padding: 2px 7px;
        }

        .salesclean-client {
            display: grid;
            gap: 2px;
        }

        .salesclean-client strong {
            color: #18324f;
            font-size: .88rem;
        }

        .salesclean-client span {
            font-size: .77rem;
            color: #63788f;
        }

        .salesclean-status {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 4px 10px;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .03em;
            border: 1px solid #cddbeb;
            background: #eef4fc;
            color: #274a6a;
        }

        .salesclean-status.in-debt {
            border-color: #f1ca98;
            background: #fff4e8;
            color: #995b14;
        }

        .salesclean-status.paid {
            border-color: #b7dec9;
            background: #ebfaef;
            color: #1f6943;
        }

        .salesclean-actions {
            display: inline-flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .salesclean-actions .btn {
            border-radius: 9px;
            font-size: .78rem;
            padding: 7px 10px;
        }

        .salesclean-empty {
            text-align: center;
            color: var(--sc-muted);
            padding: 24px 12px;
            font-size: .9rem;
        }

        .salesclean-pager {
            border: 1px solid var(--sc-border);
            border-radius: 14px;
            background: #ffffff;
            box-shadow: var(--sc-shadow);
            padding: 8px;
        }

        @media (max-width: 1260px) {
            .salesclean-metrics {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .salesclean-col-date,
            .salesclean-col-client {
                grid-column: span 3;
            }

            .salesclean-col-search {
                grid-column: span 6;
            }

            .salesclean-col-actions {
                grid-column: span 12;
            }
        }

        @media (max-width: 820px) {
            .salesclean-hero {
                grid-template-columns: 1fr;
                padding: 18px 16px;
            }

            .salesclean-hero-actions {
                justify-content: flex-start;
            }

            .salesclean-metrics {
                grid-template-columns: 1fr;
            }

            .salesclean-filter-grid {
                grid-template-columns: 1fr;
            }

            .salesclean-col-date,
            .salesclean-col-client,
            .salesclean-col-search,
            .salesclean-col-actions {
                grid-column: auto;
            }
        }
    </style>

    <section class="salesclean">
        <section class="salesclean-hero">
            <div>
                <p class="salesclean-kicker">Revenue Monitor</p>
                <h1 class="salesclean-title">Total Sales Dashboard</h1>
                <p class="salesclean-sub">Clean invoice totals, discounts, and balance tracking with fast filtering and easier reading.</p>
                <div class="salesclean-pill-row">
                    <span class="salesclean-pill">Page {{ $sales->currentPage() }} / {{ $sales->lastPage() }}</span>
                    <span class="salesclean-pill">Showing {{ number_format($pageFrom) }}-{{ number_format($pageTo) }}</span>
                    <span class="salesclean-pill">{{ number_format((int) $sales->total()) }} invoices</span>
                    <span class="salesclean-pill">Avg discount {{ number_format($avgDiscountRate, 2) }}%</span>
                </div>
            </div>
            <div class="salesclean-hero-actions">
                <button type="submit" form="total-sales-search-form" class="btn btn-primary">Apply Filters</button>
                <a href="{{ route('total-sales.index') }}" class="btn btn-muted">Reset</a>
            </div>
        </section>

        <section class="salesclean-metrics">
            @foreach($metricCards as $card)
                <article class="salesclean-metric tone-{{ $card['tone'] }}">
                    <p class="salesclean-metric-label">{{ $card['label'] }}</p>
                    <p class="salesclean-metric-value">{{ $card['value'] }}</p>
                    <p class="salesclean-metric-note">{{ $card['note'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="salesclean-panel">
            <div class="salesclean-panel-head">
                <div>
                    <h2>Filter Console</h2>
                    <p>Filter by date, client, or keyword. Search runs automatically when fields change.</p>
                </div>
                <span class="salesclean-badge">Active Filters {{ $activeFilterCount }}</span>
            </div>

            <form id="total-sales-search-form" method="GET" action="{{ route('total-sales.index') }}" class="salesclean-filter-grid">
                <div class="salesclean-col-date">
                    <label for="from_date">From Date</label>
                    <input id="from_date" type="date" name="from_date" value="{{ $fromDate }}">
                </div>
                <div class="salesclean-col-date">
                    <label for="to_date">To Date</label>
                    <input id="to_date" type="date" name="to_date" value="{{ $toDate }}">
                </div>
                <div class="salesclean-col-client">
                    <label for="client_no">Client</label>
                    <select id="client_no" name="client_no">
                        <option value="">All clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->client_no }}" @selected((string) $clientNo === (string) $client->client_no)>
                                {{ $client->client_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="salesclean-col-search">
                    <label for="q">Search All Fields</label>
                    <input id="q" type="text" name="q" value="{{ $q ?? '' }}" placeholder="Invoice no, seller, client, amount, status...">
                </div>
                <div class="salesclean-col-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="{{ route('total-sales.index') }}" class="btn btn-muted">Search All</a>
                </div>
            </form>
        </section>

        <section class="salesclean-table-card">
            <div class="salesclean-table-head">
                <div>
                    <h2>Invoice Revenue Table</h2>
                    <p>Average invoice value on this page: <strong>${{ number_format($avgInvoiceSubtotal, 2) }}</strong></p>
                </div>
                <div class="salesclean-status-meta">
                    <span class="salesclean-status-chip paid">Paid {{ number_format($paidCount) }}</span>
                    <span class="salesclean-status-chip debt">In Debt {{ number_format($inDebtCount) }}</span>
                    <span class="salesclean-status-chip">Other {{ number_format($otherCount) }}</span>
                    <span class="salesclean-status-chip">Rows {{ number_format($pageInvoices) }}</span>
                </div>
            </div>

            <div class="salesclean-table-wrap">
                <table class="salesclean-table">
                    <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Invoice Date</th>
                        <th>Seller</th>
                        <th>Client</th>
                        <th class="salesclean-num">Item Qty</th>
                        <th class="salesclean-num">Subtotal</th>
                        <th class="salesclean-num">Discount %</th>
                        <th class="salesclean-num">Balance</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($sales as $row)
                        @php
                            $statusText = mb_strtoupper((string) ($row->invoice_status ?? 'N/A'));
                            $statusClass = match ($statusText) {
                                'IN DEBT' => 'in-debt',
                                'PAID' => 'paid',
                                default => '',
                            };
                        @endphp
                        <tr>
                            <td>
                                <div class="salesclean-invoice">
                                    #{{ $row->invoice_no }}
                                    <span>INV</span>
                                </div>
                            </td>
                            <td>{{ \Illuminate\Support\Carbon::parse($row->invoice_date)->format('Y-m-d') }}</td>
                            <td>{{ $row->seller }}</td>
                            <td>
                                <div class="salesclean-client">
                                    <strong>{{ $row->client_name }}</strong>
                                    <span>Client #{{ $row->client_no }}</span>
                                </div>
                            </td>
                            <td class="salesclean-num">{{ number_format((float) $row->item_qty) }}</td>
                            <td class="salesclean-num">${{ number_format((float) $row->subtotal, 2) }}</td>
                            <td class="salesclean-num">{{ number_format((float) (($row->discount_rate ?? 0) * 100), 2) }}%</td>
                            <td class="salesclean-num">${{ number_format((float) $row->balance, 2) }}</td>
                            <td>
                                <span class="salesclean-status {{ $statusClass }}">{{ $row->invoice_status ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <div class="salesclean-actions">
                                    <a href="{{ route('store.orders.show', ['invoiceNo' => (int) $row->invoice_no]) }}" class="btn btn-muted">Detail</a>
                                    <a href="{{ route('store.orders.show', ['invoiceNo' => (int) $row->invoice_no, 'print' => 1]) }}" class="btn btn-primary">Print</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="salesclean-empty">No invoices found for the current filters.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="salesclean-pager pager">
            {{ $sales->links('pagination.orbit') }}
        </section>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('total-sales-search-form');
            const qInput = document.getElementById('q');
            const fromDateInput = document.getElementById('from_date');
            const toDateInput = document.getElementById('to_date');
            const clientSelect = document.getElementById('client_no');
            if (!form || !qInput) return;

            let debounceId = null;
            const submitSearch = () => form.requestSubmit();

            qInput.addEventListener('input', () => {
                if (debounceId) clearTimeout(debounceId);
                debounceId = setTimeout(submitSearch, 350);
            });

            [fromDateInput, toDateInput, clientSelect].forEach((el) => {
                if (!el) return;
                el.addEventListener('change', submitSearch);
            });
        });
    </script>
@endsection
