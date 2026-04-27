@extends('layouts.ecommerce')

@section('title', 'Total Sales')

@section('content')
    @php
        $rows = collect($sales->items());

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
            ['label' => 'Invoices', 'value' => number_format($pageInvoices), 'note' => 'Rows on this page', 'tone' => 'blue'],
            ['label' => 'Items', 'value' => number_format($pageItems), 'note' => 'Total quantity', 'tone' => 'green'],
            ['label' => 'Subtotal', 'value' => '$'.number_format($pageSubtotal, 2), 'note' => 'Before discounts', 'tone' => 'cyan'],
            ['label' => 'Discount', 'value' => '$'.number_format($pageDiscount, 2), 'note' => 'Discount amount', 'tone' => 'purple'],
            ['label' => 'Balance', 'value' => '$'.number_format($pageBalance, 2), 'note' => 'Net receivable', 'tone' => 'amber'],
        ];

        $todayOverview = is_array($todayOverview ?? null) ? $todayOverview : [];
        $weeklySalesSeries = collect($weeklySalesSeries ?? []);
        $topProductsOverview = collect($topProductsOverview ?? []);
        $recentTransactions = collect($recentTransactions ?? []);
        $inventoryAlerts = collect($inventoryAlerts ?? []);

        $overviewCards = [
            [
                'label' => 'Total Sales',
                'value' => (float) data_get($todayOverview, 'sales.today', 0),
                'change' => (float) data_get($todayOverview, 'sales.change_percent', 0),
                'format' => 'money',
                'tone' => 'emerald',
            ],
            [
                'label' => 'Transactions',
                'value' => (float) data_get($todayOverview, 'transactions.today', 0),
                'change' => (float) data_get($todayOverview, 'transactions.change_percent', 0),
                'format' => 'int',
                'tone' => 'blue',
            ],
            [
                'label' => 'Customers',
                'value' => (float) data_get($todayOverview, 'customers.today', 0),
                'change' => (float) data_get($todayOverview, 'customers.change_percent', 0),
                'format' => 'int',
                'tone' => 'purple',
            ],
            [
                'label' => 'Avg. Sale',
                'value' => (float) data_get($todayOverview, 'avg_sale.today', 0),
                'change' => (float) data_get($todayOverview, 'avg_sale.change_percent', 0),
                'format' => 'money',
                'tone' => 'amber',
            ],
        ];

        $formatMetricValue = static function (float $value, string $format): string {
            if ($format === 'money') {
                return '$'.number_format($value, 2);
            }

            return number_format($value);
        };

        $maxWeeklySales = (float) max(
            1,
            (float) $weeklySalesSeries->max(fn (object $day): float => (float) ($day->sales ?? 0))
        );

        $maxWeeklyTransactions = (float) max(
            1,
            (float) $weeklySalesSeries->max(fn (object $day): float => (float) ($day->transactions ?? 0))
        );

        $canViewStock = \App\Support\StaffAuth::can('stock-status.read');
    @endphp

    <style>
        .salesplus {
            --sp-navy: #0e2a47;
            --sp-blue: #0f5f97;
            --sp-cyan: #0e8fa6;
            --sp-amber: #d1811f;
            --sp-ink: #10273d;
            --sp-muted: #60768f;
            --sp-border: #d6e2ee;
            --sp-shadow: 0 16px 40px rgba(14, 42, 71, 0.12);
            display: grid;
            gap: 14px;
        }

        .salesplus * { min-width: 0; }

        .salesplus-hero {
            position: relative;
            overflow: hidden;
            border-radius: 18px;
            padding: 22px;
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) auto;
            gap: 14px;
            color: #edf6ff;
            background:
                radial-gradient(circle at 12% 24%, rgba(255, 255, 255, 0.16), transparent 36%),
                radial-gradient(circle at 88% 20%, rgba(209, 129, 31, 0.34), transparent 40%),
                linear-gradient(140deg, #0d2742 0%, #0f5b8e 48%, #0a7aa8 100%);
            box-shadow: 0 22px 44px rgba(11, 34, 57, 0.22);
        }

        .salesplus-kicker {
            margin: 0;
            font-size: .72rem;
            letter-spacing: .2em;
            text-transform: uppercase;
            color: rgba(236, 246, 255, 0.9);
            font-weight: 800;
        }

        .salesplus-title {
            margin: 6px 0 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: clamp(1.75rem, 2.8vw, 2.3rem);
            line-height: 1.1;
        }

        .salesplus-sub {
            margin: 10px 0 0;
            max-width: 640px;
            color: rgba(236, 246, 255, 0.9);
            font-size: .94rem;
            line-height: 1.5;
        }

        .salesplus-pill-row {
            margin-top: 12px;
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
        }

        .salesplus-pill {
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.24);
            background: rgba(255, 255, 255, 0.14);
            color: #f3faff;
            font-size: .74rem;
            font-weight: 700;
            padding: 5px 10px;
        }

        .salesplus-hero-actions {
            display: inline-flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: flex-start;
            gap: 8px;
        }

        .salesplus-hero-actions .btn {
            border-radius: 10px;
            font-size: .82rem;
            padding: 8px 12px;
        }

        .salesplus-hero-actions .btn-primary {
            background: linear-gradient(140deg, #f4a21c, #f5bf4d);
            color: #192733;
            box-shadow: 0 10px 20px rgba(16, 31, 46, 0.2);
        }

        .salesplus-hero-actions .btn-muted {
            border: 1px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.08);
            color: #f1f8ff;
        }

        .salesplus-panel {
            border: 1px solid var(--sp-border);
            border-radius: 16px;
            background: #fff;
            box-shadow: var(--sp-shadow);
            padding: 16px;
            display: grid;
            gap: 12px;
        }

        .salesplus-panel-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
            flex-wrap: wrap;
        }

        .salesplus-panel-head h2 {
            margin: 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: 1.02rem;
            color: var(--sp-ink);
        }

        .salesplus-panel-head p {
            margin: 4px 0 0;
            color: var(--sp-muted);
            font-size: .85rem;
        }

        .salesplus-badge {
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

        .salesplus-filter-grid {
            display: grid;
            gap: 10px;
            grid-template-columns: repeat(12, minmax(0, 1fr));
            align-items: end;
        }

        .salesplus-col-date,
        .salesplus-col-client {
            grid-column: span 2;
        }

        .salesplus-col-search {
            grid-column: span 4;
        }

        .salesplus-col-actions {
            grid-column: span 2;
            display: inline-flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .salesplus-filter-grid label {
            display: block;
            margin-bottom: 6px;
            color: #1c2f46;
            font-size: .78rem;
            letter-spacing: .03em;
            text-transform: uppercase;
            font-weight: 700;
        }

        .salesplus-filter-grid input,
        .salesplus-filter-grid select {
            width: 100%;
            border-radius: 10px;
            border: 1px solid #c8d9ea;
            background: #fbfdff;
            color: #1b2a40;
            font-size: .9rem;
            padding: 9px 10px;
        }

        .salesplus-filter-grid input:focus,
        .salesplus-filter-grid select:focus {
            outline: none;
            border-color: #0f5f97;
            box-shadow: 0 0 0 4px rgba(15, 95, 151, 0.15);
        }

        .salesplus-col-actions .btn {
            border-radius: 10px;
            padding: 9px 12px;
        }

        .salesplus-metrics {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 12px;
        }

        .salesplus-metric {
            border: 1px solid var(--sp-border);
            border-radius: 14px;
            padding: 12px 13px;
            background: linear-gradient(165deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: var(--sp-shadow);
            position: relative;
            overflow: hidden;
        }

        .salesplus-metric::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: var(--metric-tone, #0f5f97);
        }

        .salesplus-metric.tone-blue { --metric-tone: #0f5f97; }
        .salesplus-metric.tone-green { --metric-tone: #2c7a5a; }
        .salesplus-metric.tone-cyan { --metric-tone: #0e8fa6; }
        .salesplus-metric.tone-purple { --metric-tone: #8b56c8; }
        .salesplus-metric.tone-amber { --metric-tone: #d1811f; }

        .salesplus-metric-label {
            margin: 0;
            font-size: .69rem;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: var(--sp-muted);
            font-weight: 700;
        }

        .salesplus-metric-value {
            margin: 6px 0 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: clamp(1.02rem, 1.7vw, 1.34rem);
            line-height: 1.1;
            color: var(--sp-ink);
            font-variant-numeric: tabular-nums;
        }

        .salesplus-metric-note {
            margin: 4px 0 0;
            font-size: .78rem;
            color: var(--sp-muted);
        }

        .salesplus-today-cards {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .salesplus-today-card {
            border: 1px solid var(--sp-border);
            border-radius: 14px;
            background: #fff;
            box-shadow: var(--sp-shadow);
            padding: 12px 13px;
            display: grid;
            gap: 7px;
        }

        .salesplus-today-card h3 {
            margin: 0;
            font-size: .93rem;
            color: #2a3f57;
            font-weight: 700;
        }

        .salesplus-today-value {
            margin: 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: 1.5rem;
            color: #10273d;
            line-height: 1.05;
        }

        .salesplus-trend {
            font-size: .79rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .salesplus-trend.up { color: #1f8a56; }
        .salesplus-trend.down { color: #bf3b3b; }
        .salesplus-trend.flat { color: #63788f; }

        .salesplus-dashboard-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .salesplus-card {
            border: 1px solid var(--sp-border);
            border-radius: 16px;
            background: #fff;
            box-shadow: var(--sp-shadow);
            padding: 14px;
            display: grid;
            gap: 10px;
        }

        .salesplus-card-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 10px;
        }

        .salesplus-card-head h3 {
            margin: 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: 1.02rem;
            color: #10273d;
        }

        .salesplus-card-head p {
            margin: 3px 0 0;
            color: var(--sp-muted);
            font-size: .82rem;
        }

        .salesplus-week-wrap {
            display: grid;
            gap: 8px;
        }

        .salesplus-week-legend {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: .75rem;
            color: #5e7289;
            font-weight: 700;
        }

        .salesplus-week-legend span {
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .salesplus-week-legend i {
            width: 10px;
            height: 10px;
            border-radius: 2px;
            display: inline-block;
        }

        .salesplus-week-legend .sales-dot { background: #23a5c7; }
        .salesplus-week-legend .txn-dot { background: #7aa4e8; }

        .salesplus-week-chart {
            height: 200px;
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 10px;
            align-items: end;
            padding: 8px 6px 2px;
            background: linear-gradient(180deg, #f8fbff 0%, #fefefe 100%);
            border: 1px solid #dce8f3;
            border-radius: 12px;
        }

        .salesplus-week-col {
            display: grid;
            gap: 6px;
            justify-items: center;
        }

        .salesplus-week-bars {
            width: 100%;
            height: 150px;
            display: flex;
            align-items: end;
            justify-content: center;
            gap: 4px;
        }

        .salesplus-week-bar {
            width: 9px;
            border-radius: 7px 7px 3px 3px;
            min-height: 0;
        }

        .salesplus-week-bar.sales { background: linear-gradient(180deg, #0e8fa6, #27bdd5); }
        .salesplus-week-bar.txn { background: linear-gradient(180deg, #5f8ed8, #86afea); }

        .salesplus-week-label {
            font-size: .74rem;
            color: #60748b;
            font-weight: 700;
        }

        .salesplus-list {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 8px;
        }

        .salesplus-item {
            border: 1px solid #dce8f3;
            border-radius: 11px;
            background: #f9fcff;
            padding: 9px 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .salesplus-item strong {
            display: block;
            color: #153452;
            font-size: .89rem;
        }

        .salesplus-item small {
            display: block;
            margin-top: 2px;
            color: #63788f;
            font-size: .76rem;
        }

        .salesplus-item-value {
            text-align: right;
            font-family: "Space Grotesk", sans-serif;
            font-size: .95rem;
            color: #163651;
            font-weight: 700;
            white-space: nowrap;
        }

        .salesplus-item-trend {
            display: block;
            margin-top: 3px;
            font-size: .75rem;
            font-weight: 700;
        }

        .salesplus-item-trend.up { color: #1f8a56; }
        .salesplus-item-trend.down { color: #bf3b3b; }
        .salesplus-item-trend.flat { color: #63788f; }

        .salesplus-item-tag {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 3px 8px;
            font-size: .71rem;
            font-weight: 700;
            letter-spacing: .02em;
            border: 1px solid #cddbeb;
            background: #eef4fc;
            color: #274a6a;
        }

        .salesplus-item-tag.critical {
            border-color: #f0b9b9;
            background: #ffecec;
            color: #c03939;
        }

        .salesplus-item-tag.low {
            border-color: #f3d18d;
            background: #fff7ea;
            color: #ad6f16;
        }

        .salesplus-table-card {
            border: 1px solid var(--sp-border);
            border-radius: 16px;
            background: #fff;
            box-shadow: var(--sp-shadow);
            overflow: hidden;
        }

        .salesplus-table-head {
            padding: 14px 16px;
            border-bottom: 1px solid #dbe7f2;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .salesplus-table-head h2 {
            margin: 0;
            font-family: "Space Grotesk", sans-serif;
            font-size: 1.04rem;
            color: var(--sp-ink);
        }

        .salesplus-table-head p {
            margin: 3px 0 0;
            color: var(--sp-muted);
            font-size: .84rem;
        }

        .salesplus-status-meta {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .salesplus-status-chip {
            border-radius: 999px;
            border: 1px solid #d4e0ee;
            background: #f4f8fd;
            color: #24435f;
            padding: 4px 10px;
            font-size: .73rem;
            font-weight: 700;
        }

        .salesplus-status-chip.paid {
            border-color: #b9dfca;
            background: #ebfbf2;
            color: #206844;
        }

        .salesplus-status-chip.debt {
            border-color: #f1ca98;
            background: #fff6eb;
            color: #9c5d16;
        }

        .salesplus-table-wrap {
            overflow-x: auto;
        }

        .salesplus-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1080px;
        }

        .salesplus-table th,
        .salesplus-table td {
            padding: 10px 11px;
            border-bottom: 1px solid #dbe7f2;
            vertical-align: middle;
            font-size: .88rem;
        }

        .salesplus-table th {
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

        .salesplus-table tbody tr:hover {
            background: #f8fbff;
        }

        .salesplus-num {
            text-align: right;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .salesplus-invoice {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-weight: 800;
            color: #0f3254;
        }

        .salesplus-invoice span {
            border-radius: 999px;
            border: 1px solid #cfdef0;
            background: #edf5ff;
            color: #214c72;
            font-size: .7rem;
            font-weight: 700;
            padding: 2px 7px;
        }

        .salesplus-client {
            display: grid;
            gap: 2px;
        }

        .salesplus-client strong {
            color: #18324f;
            font-size: .88rem;
        }

        .salesplus-client span {
            font-size: .77rem;
            color: #63788f;
        }

        .salesplus-status {
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

        .salesplus-status.in-debt {
            border-color: #f1ca98;
            background: #fff4e8;
            color: #995b14;
        }

        .salesplus-status.paid {
            border-color: #b7dec9;
            background: #ebfaef;
            color: #1f6943;
        }

        .salesplus-actions {
            display: inline-flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .salesplus-actions .btn {
            border-radius: 9px;
            font-size: .78rem;
            padding: 7px 10px;
        }

        .salesplus-empty {
            text-align: center;
            color: var(--sp-muted);
            padding: 24px 12px;
            font-size: .9rem;
        }

        .salesplus-pager {
            border: 1px solid var(--sp-border);
            border-radius: 14px;
            background: #ffffff;
            box-shadow: var(--sp-shadow);
            padding: 8px;
        }

        @media (max-width: 1320px) {
            .salesplus-metrics {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .salesplus-today-cards {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .salesplus-dashboard-grid {
                grid-template-columns: 1fr;
            }

            .salesplus-col-date,
            .salesplus-col-client {
                grid-column: span 3;
            }

            .salesplus-col-search {
                grid-column: span 6;
            }

            .salesplus-col-actions {
                grid-column: span 12;
            }
        }

        @media (max-width: 820px) {
            .salesplus-hero {
                grid-template-columns: 1fr;
                padding: 18px 16px;
            }

            .salesplus-hero-actions {
                justify-content: flex-start;
            }

            .salesplus-metrics,
            .salesplus-today-cards,
            .salesplus-filter-grid {
                grid-template-columns: 1fr;
            }

            .salesplus-col-date,
            .salesplus-col-client,
            .salesplus-col-search,
            .salesplus-col-actions {
                grid-column: auto;
            }

        }
    </style>

    <section class="salesplus">
        <section class="salesplus-hero">
            <div>
                <p class="salesplus-kicker">Revenue Monitor</p>
                <h1 class="salesplus-title">Total Sales Dashboard</h1>
                <p class="salesplus-sub">Cleaner dashboard with daily money records, weekly overview, top products, and recent activity for faster decision making.</p>
                <div class="salesplus-pill-row">
                    <span class="salesplus-pill">Page {{ $sales->currentPage() }} / {{ $sales->lastPage() }}</span>
                    <span class="salesplus-pill">Showing {{ number_format($pageFrom) }}-{{ number_format($pageTo) }}</span>
                    <span class="salesplus-pill">{{ number_format((int) $sales->total()) }} invoices</span>
                    <span class="salesplus-pill">Avg discount {{ number_format($avgDiscountRate, 2) }}%</span>
                </div>
            </div>
            <div class="salesplus-hero-actions">
                <button type="submit" form="total-sales-search-form" class="btn btn-primary">Apply Filters</button>
                <a href="{{ route('total-sales.index') }}" class="btn btn-muted">Reset</a>
            </div>
        </section>

        <section class="salesplus-panel">
            <div class="salesplus-panel-head">
                <div>
                    <h2>Filter Console</h2>
                    <p>Filter by date, client, or keyword. Search runs automatically when fields change.</p>
                </div>
                <span class="salesplus-badge">Active Filters {{ $activeFilterCount }}</span>
            </div>

            <form id="total-sales-search-form" method="GET" action="{{ route('total-sales.index') }}" class="salesplus-filter-grid">
                <div class="salesplus-col-date">
                    <label for="from_date">From Date</label>
                    <input id="from_date" type="date" name="from_date" value="{{ $fromDate }}">
                </div>
                <div class="salesplus-col-date">
                    <label for="to_date">To Date</label>
                    <input id="to_date" type="date" name="to_date" value="{{ $toDate }}">
                </div>
                <div class="salesplus-col-client">
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
                <div class="salesplus-col-search">
                    <label for="q">Search All Fields</label>
                    <input id="q" type="text" name="q" value="{{ $q ?? '' }}" placeholder="Invoice no, seller, client, amount, status...">
                </div>
                <div class="salesplus-col-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="{{ route('total-sales.index') }}" class="btn btn-muted">Search All</a>
                </div>
            </form>
        </section>

        <section class="salesplus-metrics">
            @foreach($metricCards as $card)
                <article class="salesplus-metric tone-{{ $card['tone'] }}">
                    <p class="salesplus-metric-label">{{ $card['label'] }}</p>
                    <p class="salesplus-metric-value">{{ $card['value'] }}</p>
                    <p class="salesplus-metric-note">{{ $card['note'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="salesplus-panel">
            <div class="salesplus-panel-head">
                <div>
                    <h2>Today's Overview</h2>
                    <p>Live today metrics with change versus yesterday.</p>
                </div>
                <span class="salesplus-badge">Today</span>
            </div>

            <div class="salesplus-today-cards">
                @foreach($overviewCards as $card)
                    @php
                        $change = (float) ($card['change'] ?? 0);
                        $trendClass = $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat');
                        $trendSymbol = $change > 0 ? '+' : '';
                    @endphp
                    <article class="salesplus-today-card">
                        <h3>{{ $card['label'] }}</h3>
                        <p class="salesplus-today-value">{{ $formatMetricValue((float) $card['value'], (string) $card['format']) }}</p>
                        <span class="salesplus-trend {{ $trendClass }}">{{ $trendSymbol }}{{ number_format($change, 1) }}% from yesterday</span>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="salesplus-dashboard-grid">
            <article class="salesplus-card">
                <div class="salesplus-card-head">
                    <div>
                        <h3>Sales Overview (7 Days)</h3>
                        <p>Sales and transaction trend by day.</p>
                    </div>
                </div>

                @if($weeklySalesSeries->isEmpty())
                    <p class="salesplus-empty">No weekly data available.</p>
                @else
                    <div class="salesplus-week-wrap">
                        <div class="salesplus-week-legend">
                            <span><i class="sales-dot"></i> Sales</span>
                            <span><i class="txn-dot"></i> Transactions</span>
                        </div>
                        <div class="salesplus-week-chart">
                            @foreach($weeklySalesSeries as $day)
                                @php
                                    $salesHeight = ((float) ($day->sales ?? 0)) > 0
                                        ? max(6, (int) round((((float) $day->sales) / $maxWeeklySales) * 100))
                                        : 0;
                                    $txnHeight = ((float) ($day->transactions ?? 0)) > 0
                                        ? max(6, (int) round((((float) $day->transactions) / $maxWeeklyTransactions) * 100))
                                        : 0;
                                @endphp
                                <div class="salesplus-week-col">
                                    <div class="salesplus-week-bars">
                                        <span class="salesplus-week-bar sales" style="height: {{ $salesHeight }}%;"></span>
                                        <span class="salesplus-week-bar txn" style="height: {{ $txnHeight }}%;"></span>
                                    </div>
                                    <span class="salesplus-week-label">{{ $day->label }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </article>

            <article class="salesplus-card">
                <div class="salesplus-card-head">
                    <div>
                        <h3>Top Products</h3>
                        <p>Best performers by sales in the last 7 days.</p>
                    </div>
                </div>

                <ul class="salesplus-list">
                    @forelse($topProductsOverview as $product)
                        @php
                            $change = (float) ($product->change_percent ?? 0);
                            $trendClass = $change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat');
                            $trendSymbol = $change > 0 ? '+' : '';
                        @endphp
                        <li class="salesplus-item">
                            <div>
                                <strong>{{ $product->product_name }}</strong>
                                <small>{{ number_format((float) ($product->units ?? 0)) }} units</small>
                            </div>
                            <div class="salesplus-item-value">
                                ${{ number_format((float) ($product->sales ?? 0), 2) }}
                                <span class="salesplus-item-trend {{ $trendClass }}">{{ $trendSymbol }}{{ number_format($change, 1) }}%</span>
                            </div>
                        </li>
                    @empty
                        <li class="salesplus-empty">No top product data.</li>
                    @endforelse
                </ul>
            </article>
        </section>

        <section class="salesplus-dashboard-grid">
            <article class="salesplus-card">
                <div class="salesplus-card-head">
                    <div>
                        <h3>Recent Transactions</h3>
                        <p>Latest invoice activity in current filter context.</p>
                    </div>
                </div>

                <ul class="salesplus-list">
                    @forelse($recentTransactions as $tx)
                        <li class="salesplus-item">
                            <div>
                                <strong>#{{ $tx->invoice_no }} - {{ $tx->client_name }}</strong>
                                <small>{{ $tx->seller }} - {{ \Illuminate\Support\Carbon::parse($tx->invoice_date)->format('Y-m-d') }}</small>
                            </div>
                            <div class="salesplus-item-value">
                                ${{ number_format((float) ($tx->balance ?? 0), 2) }}
                                <span class="salesplus-item-tag">{{ $tx->invoice_status ?? 'N/A' }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="salesplus-empty">No recent transactions.</li>
                    @endforelse
                </ul>
            </article>

            <article class="salesplus-card">
                <div class="salesplus-card-head">
                    <div>
                        <h3>Inventory Alerts</h3>
                        <p>Low-stock and critical-stock products.</p>
                    </div>
                    @if($canViewStock)
                        <a href="{{ route('products.status') }}" class="salesplus-badge">Manage</a>
                    @endif
                </div>

                <ul class="salesplus-list">
                    @forelse($inventoryAlerts as $alert)
                        <li class="salesplus-item">
                            <div>
                                <strong>{{ $alert->product_name }}</strong>
                                <small>{{ number_format((float) ($alert->qty_on_hand ?? 0)) }} left - min {{ number_format((float) ($alert->lower_qty ?? 0)) }}</small>
                            </div>
                            <div class="salesplus-item-value">
                                <span class="salesplus-item-tag {{ $alert->severity ?? 'low' }}">{{ mb_strtoupper((string) ($alert->severity ?? 'low')) }}</span>
                            </div>
                        </li>
                    @empty
                        <li class="salesplus-empty">No inventory alerts.</li>
                    @endforelse
                </ul>
            </article>
        </section>

        <section class="salesplus-table-card">
            <div class="salesplus-table-head">
                <div>
                    <h2>Invoice Revenue Table</h2>
                    <p>Average invoice value on this page: <strong>${{ number_format($avgInvoiceSubtotal, 2) }}</strong></p>
                </div>
                <div class="salesplus-status-meta">
                    <span class="salesplus-status-chip paid">Paid {{ number_format($paidCount) }}</span>
                    <span class="salesplus-status-chip debt">In Debt {{ number_format($inDebtCount) }}</span>
                    <span class="salesplus-status-chip">Other {{ number_format($otherCount) }}</span>
                    <span class="salesplus-status-chip">Rows {{ number_format($pageInvoices) }}</span>
                </div>
            </div>

            <div class="salesplus-table-wrap">
                <table class="salesplus-table">
                    <thead>
                    <tr>
                        <th>Invoice No</th>
                        <th>Invoice Date</th>
                        <th>Seller</th>
                        <th>Client</th>
                        <th class="salesplus-num">Item Qty</th>
                        <th class="salesplus-num">Subtotal</th>
                        <th class="salesplus-num">Discount %</th>
                        <th class="salesplus-num">Balance</th>
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
                                <div class="salesplus-invoice">
                                    #{{ $row->invoice_no }}
                                    <span>INV</span>
                                </div>
                            </td>
                            <td>{{ \Illuminate\Support\Carbon::parse($row->invoice_date)->format('Y-m-d') }}</td>
                            <td>{{ $row->seller }}</td>
                            <td>
                                <div class="salesplus-client">
                                    <strong>{{ $row->client_name }}</strong>
                                    <span>Client #{{ $row->client_no }}</span>
                                </div>
                            </td>
                            <td class="salesplus-num">{{ number_format((float) $row->item_qty) }}</td>
                            <td class="salesplus-num">${{ number_format((float) $row->subtotal, 2) }}</td>
                            <td class="salesplus-num">{{ number_format((float) (($row->discount_rate ?? 0) * 100), 2) }}%</td>
                            <td class="salesplus-num">${{ number_format((float) $row->balance, 2) }}</td>
                            <td>
                                <span class="salesplus-status {{ $statusClass }}">{{ $row->invoice_status ?? 'N/A' }}</span>
                            </td>
                            <td>
                                <div class="salesplus-actions">
                                    <a href="{{ route('store.orders.show', ['invoiceNo' => (int) $row->invoice_no]) }}" class="btn btn-muted">Detail</a>
                                    <a href="{{ route('store.orders.show', ['invoiceNo' => (int) $row->invoice_no, 'print' => 1]) }}" class="btn btn-primary">Print</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="salesplus-empty">No invoices found for the current filters.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="salesplus-pager pager">
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
