@extends('layouts.ecommerce')

@section('title', 'Product Status')

@section('content')
    @php
        $topUnderGap = $underGapRows->first();
        $topOverGap = $overGapRows->first();
        $totalQtyOnHand = (float) (($statusQtyTotals['understock'] ?? 0) + ($statusQtyTotals['enough'] ?? 0) + ($statusQtyTotals['overstock'] ?? 0));
        $canFutureStock = \App\Support\StaffAuth::can('future-stock.read');
    @endphp

    <style>
        .status-page {
            --status-ink: #14334f;
            --status-ocean: #0b638e;
            --status-teal: #0f9aa4;
            --status-amber: #f2a23a;
            --status-edge: #c9d9ea;
            --status-glow: 0 22px 42px rgba(10, 30, 50, 0.16);
            display: grid;
            gap: 14px;
        }

        .status-hero-card {
            padding: 0;
            overflow: hidden;
            position: relative;
            border: 1px solid rgba(12, 49, 79, 0.24);
            background: linear-gradient(126deg, #083d68 0%, #0d668f 48%, #10989d 100%);
            color: #fff;
            box-shadow: 0 24px 48px rgba(7, 22, 39, 0.28);
        }

        .status-hero-card::before,
        .status-hero-card::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
        }

        .status-hero-card::before {
            width: 290px;
            height: 290px;
            top: -130px;
            right: -100px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.32), transparent 70%);
        }

        .status-hero-card::after {
            width: 320px;
            height: 320px;
            left: -140px;
            bottom: -200px;
            background: radial-gradient(circle, rgba(242, 162, 58, 0.4), transparent 72%);
        }

        .status-hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(0, 1.5fr) minmax(240px, 0.9fr);
            align-items: stretch;
            padding: 24px;
        }

        .status-kicker {
            margin: 0 0 8px;
            font-size: 0.72rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            font-weight: 800;
            color: rgba(237, 247, 255, 0.88);
        }

        .status-title {
            margin: 0;
            color: #fff;
            font-size: clamp(1.6rem, 2.8vw, 2.3rem);
        }

        .status-subtitle {
            margin: 10px 0 0;
            color: rgba(236, 247, 255, 0.92);
            line-height: 1.55;
            max-width: 62ch;
            font-size: 0.95rem;
        }

        .status-hero-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
            align-items: center;
        }

        .status-hero-actions .btn-muted {
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.34);
            background: rgba(255, 255, 255, 0.14);
        }

        .status-hero-actions .btn-primary {
            background: var(--accent);
            box-shadow: 0 12px 24px rgba(48, 20, 0, 0.25);
        }

        .status-hero-chip {
            color: #fff;
            border-color: rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.15);
        }

        .status-signal-panel {
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.33);
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.22), rgba(255, 255, 255, 0.1));
            backdrop-filter: blur(6px);
            padding: 14px;
            display: grid;
            gap: 12px;
            align-content: space-between;
            animation: status-float 5s ease-in-out infinite;
        }

        .status-signal-label {
            margin: 0;
            font-size: 0.73rem;
            text-transform: uppercase;
            letter-spacing: 0.17em;
            color: rgba(238, 248, 255, 0.88);
            font-weight: 800;
        }

        .status-signal-title {
            margin-top: 8px;
            font-size: 1.32rem;
            line-height: 1.2;
            font-family: "Space Grotesk", sans-serif;
            font-weight: 700;
        }

        .status-signal-sub {
            margin: 6px 0 0;
            font-size: 0.84rem;
            line-height: 1.45;
            color: rgba(236, 247, 255, 0.9);
        }

        .status-signal-live {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            color: rgba(245, 252, 255, 0.96);
        }

        .status-signal-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #90f8e2;
            box-shadow: 0 0 0 0 rgba(144, 248, 226, 0.46);
            animation: status-pulse 1.8s ease-in-out infinite;
        }

        .status-filter-card {
            border: 1px solid #d5e2ef;
            background: linear-gradient(160deg, #fbfdff, #f1f7ff);
        }

        .status-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .status-head h2,
        .status-head h3 {
            margin: 0;
            color: var(--status-ink);
            font-size: 1.08rem;
            font-family: "Space Grotesk", sans-serif;
        }

        .status-head p {
            margin: 5px 0 0;
            color: #5d7690;
            font-size: 0.87rem;
            line-height: 1.45;
        }

        .status-tag {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 10px;
            border: 1px solid #d5e2f0;
            background: #f4f8ff;
            color: #4f6983;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            font-weight: 700;
        }

        .status-filter-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            align-items: end;
        }

        .status-filter-grid .actions {
            justify-content: flex-end;
        }

        .status-metric-card,
        .status-chart-card,
        .status-table-card {
            background: linear-gradient(180deg, #fff, #f5f9ff);
        }

        .status-metric-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .status-metric {
            position: relative;
            border: 1px solid #d6e2ef;
            border-radius: 14px;
            padding: 13px 14px;
            background: linear-gradient(165deg, #fff, #f2f8ff);
            overflow: hidden;
        }

        .status-metric::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #0b678f, #13a1a6);
        }

        .status-metric.critical::after {
            background: linear-gradient(90deg, #b91c1c, #ef4444);
        }

        .status-metric.stable::after {
            background: linear-gradient(90deg, #0f766e, #22c55e);
        }

        .status-metric.excess::after {
            background: linear-gradient(90deg, #1d4ed8, #60a5fa);
        }

        .status-metric.total::after {
            background: linear-gradient(90deg, #0b678f, #12a2a6);
        }

        .status-metric .label {
            margin: 0;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #56708a;
            font-weight: 700;
        }

        .status-metric .value {
            margin: 8px 0 0;
            font-size: 1.45rem;
            color: #12324d;
            font-weight: 800;
            font-family: "Space Grotesk", sans-serif;
        }

        .status-chart-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .status-chart-card {
            border: 1px solid #d5e1ef;
        }

        .status-chart-wide {
            grid-column: 1 / -1;
        }

        .status-chart-wrap {
            border: 1px solid #d5e1ef;
            border-radius: 14px;
            padding: 10px;
            background: #fff;
            min-height: 240px;
        }

        .status-table-wrap {
            background: #fff;
        }

        .status-empty {
            margin: 0;
            border: 1px dashed #cad9ea;
            border-radius: 12px;
            padding: 12px;
            background: #f8fbff;
            color: #56708a;
            font-size: 0.88rem;
        }

        .status-animate {
            opacity: 0;
            transform: translateY(14px);
            animation: status-rise 0.6s ease forwards;
        }

        .status-delay-1 { animation-delay: 0.08s; }
        .status-delay-2 { animation-delay: 0.16s; }
        .status-delay-3 { animation-delay: 0.24s; }
        .status-delay-4 { animation-delay: 0.32s; }
        .status-delay-5 { animation-delay: 0.4s; }
        .status-delay-6 { animation-delay: 0.48s; }
        .status-delay-7 { animation-delay: 0.56s; }

        @keyframes status-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        @keyframes status-pulse {
            0% { box-shadow: 0 0 0 0 rgba(143, 248, 225, 0.52); }
            70% { box-shadow: 0 0 0 10px rgba(143, 248, 225, 0); }
            100% { box-shadow: 0 0 0 0 rgba(143, 248, 225, 0); }
        }

        @keyframes status-rise {
            from { opacity: 0; transform: translateY(16px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (prefers-reduced-motion: reduce) {
            .status-animate {
                opacity: 1;
                transform: none;
                animation: none;
            }
        }

        @media (max-width: 1080px) {
            .status-hero-grid {
                grid-template-columns: 1fr;
            }

            .status-signal-panel {
                animation: none;
            }
        }

        @media (max-width: 980px) {
            .status-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .status-metric-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .status-chart-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .status-hero-grid {
                padding: 20px 16px;
            }

            .status-filter-grid {
                grid-template-columns: 1fr;
            }

            .status-metric-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="status-page">
        <section class="card status-hero-card status-animate status-delay-1">
            <div class="status-hero-grid">
                <div>
                    <p class="status-kicker">Inventory Command</p>
                    <h1 class="headline status-title">Product Status</h1>
                    <p class="status-subtitle">Live inventory by status groups: Under Stock, Enough, and OverStock.</p>
                    <div class="status-hero-actions">
                        <a href="{{ route('products.status') }}" class="btn btn-primary status-btn-primary">Product Status</a>
                        @if($canFutureStock)
                            <a href="{{ route('products.status.future') }}" class="btn btn-muted status-btn-muted">Analyst Future</a>
                        @endif
                        <span class="chip status-hero-chip">Oracle Live</span>
                        <span class="chip status-hero-chip">{{ number_format((int) ($statusCounts['understock'] ?? 0)) }} Understock</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="card status-filter-card status-animate status-delay-2">
            <div class="status-head">
                <div>
                    <h2>Filter &amp; Sort</h2>
                    <p>Search by product, code, or type to focus the live status list.</p>
                </div>
                <span class="status-tag">Live Query</span>
            </div>
            <form method="GET" action="{{ route('products.status') }}" class="status-filter-grid">
                <div>
                    <label for="q">Search product / code / type</label>
                    <input id="q" type="text" name="q" value="{{ $q }}" placeholder="e.g. P0001 or Laptop">
                </div>
                <div>
                    <label for="type">Product Type</label>
                    <select id="type" name="type">
                        <option value="">All types</option>
                        @foreach($types as $typeRow)
                            <option value="{{ $typeRow->id }}" @selected((string) $type === (string) $typeRow->id)>{{ $typeRow->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="sort">Sort</label>
                    <select id="sort" name="sort">
                        @foreach($sortOptions as $value => $label)
                            <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('products.status') }}" class="btn btn-muted">Reset</a>
                </div>
            </form>
        </section>

        <section class="card status-metric-card status-animate status-delay-3">
            <div class="status-head">
                <div>
                    <h2>Status Snapshot</h2>
                    <p>Counts and totals across current status groups.</p>
                </div>
                <span class="status-tag">Inventory Pulse</span>
            </div>
            <div class="status-metric-grid">
                <article class="status-metric critical">
                    <p class="label">Under Stock Items</p>
                    <p class="value">{{ number_format((int) ($statusCounts['understock'] ?? 0)) }}</p>
                </article>
                <article class="status-metric stable">
                    <p class="label">Enough Items</p>
                    <p class="value">{{ number_format((int) ($statusCounts['enough'] ?? 0)) }}</p>
                </article>
                <article class="status-metric excess">
                    <p class="label">OverStock Items</p>
                    <p class="value">{{ number_format((int) ($statusCounts['overstock'] ?? 0)) }}</p>
                </article>
                <article class="status-metric total">
                    <p class="label">Total Qty On Hand</p>
                    <p class="value">{{ number_format($totalQtyOnHand) }}</p>
                </article>
            </div>
        </section>

        <section class="status-chart-grid status-animate status-delay-4">
            <article class="card status-chart-card">
                <div class="status-head">
                    <div>
                        <h3>Count Distribution</h3>
                        <p>Doughnut comparison across status groups.</p>
                    </div>
                    <span class="status-tag">Doughnut</span>
                </div>
                <div class="status-chart-wrap">
                    <canvas id="statusCountChart" height="180"></canvas>
                </div>
            </article>
            <article class="card status-chart-card">
                <div class="status-head">
                    <div>
                        <h3>Under Stock Gap Top 8</h3>
                        <p>Largest missing quantities requiring attention.</p>
                    </div>
                    <span class="status-tag">Bar</span>
                </div>
                <div class="status-chart-wrap">
                    <canvas id="underGapChart" height="280"></canvas>
                </div>
            </article>
            <article class="card status-chart-card status-chart-wide">
                <div class="status-head">
                    <div>
                        <h3>All Products Qty On Hand</h3>
                        <p>Line view of every product quantity on hand.</p>
                    </div>
                    <span class="status-tag">Line</span>
                </div>
                <div class="status-chart-wrap" style="overflow-x: auto; padding-bottom: 6px;">
                    <div id="all-gap-wrap" style="min-width: 900px;">
                        <canvas id="allGapChart" height="280"></canvas>
                    </div>
                </div>
            </article>
        </section>

        @php
            $renderRows = static function (iterable $rows): void {
                foreach ($rows as $row) {
                    $photoPath = trim((string) ($row->photo_path ?? ''));
                    $photoSrc = '';
                    if ($photoPath !== '') {
                        $photoSrc = \Illuminate\Support\Str::startsWith($photoPath, ['http://', 'https://', '/'])
                            ? $photoPath
                            : asset($photoPath);
                    }
                    echo '<tr>';
                    echo '<td style="width: 72px;">';
                    if ($photoSrc !== '') {
                        echo '<img src="' . e($photoSrc) . '" alt="' . e((string) ($row->product_name ?? 'Product')) . '" style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border);">';
                    } else {
                        echo '<span class="chip">No Photo</span>';
                    }
                    echo '</td>';
                    echo '<td>' . e((string) ($row->product_no ?? '')) . '</td>';
                    echo '<td>' . e((string) ($row->product_name ?? 'N/A')) . '</td>';
                    echo '<td>' . e((string) ($row->product_type_name ?? 'N/A')) . '</td>';
                    echo '<td style="text-align: right;">' . number_format((float) ($row->qty_on_hand ?? 0)) . '</td>';
                    echo '</tr>';
                }
            };
        @endphp

        <section id="under-stock-list" class="card status-table-card status-animate status-delay-5">
            <div class="status-head">
                <div>
                    <h2>Under Stock List</h2>
                    <p>Products below the lower threshold in alert stock.</p>
                </div>
                <span class="status-tag">{{ number_format($underStockRows->count()) }} rows</span>
            </div>
            <div class="table-wrap soft status-table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Product No</th>
                        <th>Product Name</th>
                        <th>Type</th>
                        <th style="text-align: right;">Qty</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if($underStockRows->isNotEmpty())
                        @foreach($underStockRows as $row)
                            @php
                                $photoPath = trim((string) ($row->photo_path ?? ''));
                                $photoSrc = '';
                                if ($photoPath !== '') {
                                    $photoSrc = \Illuminate\Support\Str::startsWith($photoPath, ['http://', 'https://', '/'])
                                        ? $photoPath
                                        : asset($photoPath);
                                }
                            @endphp
                            <tr>
                                <td style="width: 72px;">
                                    @if($photoSrc !== '')
                                        <img src="{{ $photoSrc }}" alt="{{ $row->product_name ?? 'Product' }}" style="width: 48px; height: 48px; object-fit: cover; border-radius: 8px; border: 1px solid var(--border);">
                                    @else
                                        <span class="chip">No Photo</span>
                                    @endif
                                </td>
                                <td>{{ $row->product_no ?? '' }}</td>
                                <td>{{ $row->product_name ?? 'N/A' }}</td>
                                <td>{{ $row->product_type_name ?? 'N/A' }}</td>
                                <td style="text-align: right;">{{ number_format((float) ($row->qty_on_hand ?? 0)) }}</td>
                                <td>
                                    @if(\App\Support\StaffAuth::can('purchases.read'))
                                        <a href="{{ route('purchases.index', ['new' => 1, 'prefill_product_no' => (string) ($row->product_no ?? ''), 'prefill_qty' => 100]) }}" class="btn btn-primary">Purchase</a>
                                    @else
                                        <span class="chip">No Access</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="6" class="status-empty">No under stock records.</td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card status-table-card status-animate status-delay-6">
            <div class="status-head">
                <div>
                    <h2>Enough List</h2>
                    <p>Products within acceptable stock boundaries.</p>
                </div>
                <span class="status-tag">{{ number_format($enoughRows->count()) }} rows</span>
            </div>
            <div class="table-wrap soft status-table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Product No</th>
                        <th>Product Name</th>
                        <th>Type</th>
                        <th style="text-align: right;">Qty</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if($enoughRows->isNotEmpty())
                        @php
                            $renderRows($enoughRows);
                        @endphp
                    @else
                        <tr>
                            <td colspan="5" class="status-empty">No enough stock records.</td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card status-table-card status-animate status-delay-7">
            <div class="status-head">
                <div>
                    <h2>OverStock List</h2>
                    <p>Products beyond the higher stock threshold.</p>
                </div>
                <span class="status-tag">{{ number_format($overStockRows->count()) }} rows</span>
            </div>
            <div class="table-wrap soft status-table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Product No</th>
                        <th>Product Name</th>
                        <th>Type</th>
                        <th style="text-align: right;">Qty</th>
                    </tr>
                    </thead>
                    <tbody>
                    @if($overStockRows->isNotEmpty())
                        @php
                            $renderRows($overStockRows);
                        @endphp
                    @else
                        <tr>
                            <td colspan="5" class="status-empty">No over stock records.</td>
                        </tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    @php
        $chartCountData = [
            (int) ($statusCounts['understock'] ?? 0),
            (int) ($statusCounts['enough'] ?? 0),
            (int) ($statusCounts['overstock'] ?? 0),
        ];
        $chartUnderGapLabels = $underGapRows->map(static fn($row): string => (string) ($row->product_name ?? $row->product_no))->values()->all();
        $chartUnderGapValues = $underGapRows->map(static fn($row): float => (float) ($row->gap_value ?? 0))->values()->all();
        $chartAllQtyLabels = $allGapRows->map(static fn($row): string => (string) ($row->product_no ?? ''))->values()->all();
        $chartAllQtyValues = $allGapRows->map(static fn($row): float => (float) ($row->qty_on_hand ?? 0))->values()->all();
        $chartAllQtyNames = $allGapRows->map(static fn($row): string => (string) ($row->product_name ?? ''))->values()->all();
        $chartAllQtyStatus = $allGapRows->map(static fn($row): string => (string) ($row->status_group ?? $row->stock_status ?? ''))->values()->all();
        $chartAllQtyGap = $allGapRows->map(static fn($row): float => (float) ($row->gap_value ?? 0))->values()->all();
    @endphp

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (() => {
            const countData = @json($chartCountData);
            const underGapLabels = @json($chartUnderGapLabels);
            const underGapValues = @json($chartUnderGapValues);
            const allGapLabels = @json($chartAllQtyLabels);
            const allGapValues = @json($chartAllQtyValues);
            const allGapNames = @json($chartAllQtyNames);
            const allGapStatus = @json($chartAllQtyStatus);
            const allGapGap = @json($chartAllQtyGap);

            const labels = ['Under Stock', 'Enough', 'OverStock'];
            const colors = ['#c53030', '#2f855a', '#2b6cb0'];

            const makeChart = (id, config) => {
                const el = document.getElementById(id);
                if (!el) return;
                new Chart(el, config);
            };

            makeChart('statusCountChart', {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{ data: countData, backgroundColor: colors }]
                },
                options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
            });

            makeChart('underGapChart', {
                type: 'bar',
                data: {
                    labels: underGapLabels,
                    datasets: [{
                        label: 'Missing Qty',
                        data: underGapValues,
                        backgroundColor: '#d53f3f'
                    }]
                },
                options: {
                    responsive: true,
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 500 } } },
                    plugins: { legend: { display: false } }
                }
            });

            makeChart('allGapChart', {
                type: 'line',
                data: {
                    labels: allGapLabels,
                    datasets: [{
                        label: 'Qty On Hand',
                        data: allGapValues,
                        borderColor: '#2b6cb0',
                        backgroundColor: 'rgba(43, 108, 176, 0.18)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        x: {
                            ticks: {
                                autoSkip: false,
                                maxRotation: 60,
                                minRotation: 60,
                                font: { size: 10 },
                            },
                        },
                        y: { beginAtZero: true, ticks: { stepSize: 500 } }
                    },
                    plugins: {
                        legend: { position: 'bottom' },
                        tooltip: {
                            callbacks: {
                                title: (items) => {
                                    const idx = items?.[0]?.dataIndex ?? 0;
                                    const code = allGapLabels[idx] ?? '';
                                    const name = allGapNames[idx] ?? '';
                                    return name ? `${code} - ${name}` : String(code);
                                },
                                label: (item) => {
                                    const idx = item?.dataIndex ?? 0;
                                    const status = allGapStatus[idx] ?? '';
                                    const gap = allGapGap[idx] ?? 0;
                                    const val = item?.parsed?.y ?? 0;
                                    if (status) {
                                        return `Qty: ${val} (${status}) | Gap: ${gap}`;
                                    }
                                    return `Qty: ${val} | Gap: ${gap}`;
                                }
                            }
                        }
                    }
                }
            });

            const wrap = document.getElementById('all-gap-wrap');
            if (wrap) {
                const perLabelPx = 38;
                const minWidthPx = Math.max(900, (allGapLabels?.length || 0) * perLabelPx);
                wrap.style.minWidth = `${minWidthPx}px`;
            }
        })();
    </script>
@endsection
