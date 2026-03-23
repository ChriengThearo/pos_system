@extends('layouts.ecommerce')

@section('title', 'Analyst Future')

@section('content')
    @php
        $showAiReply = request()->query('ask') === '1';
        $hasModeQuery = request()->has('mode');
        $maxForecastUnits = (float) ($displayRows->max('forecast_units') ?? 0);
        $canStockStatus = \App\Support\StaffAuth::can('stock-status.read');
    @endphp

    <style>
        .future-page {
            --future-ink: #14334f;
            --future-ocean: #0b638e;
            --future-teal: #0f9aa4;
            --future-amber: #f2a23a;
            --future-edge: #c9d9ea;
            --future-glow: 0 22px 42px rgba(10, 30, 50, 0.16);
            display: grid;
            gap: 14px;
        }

        .future-hero-card {
            padding: 0;
            overflow: hidden;
            position: relative;
            border: 1px solid rgba(12, 49, 79, 0.24);
            background: linear-gradient(126deg, #083d68 0%, #0d668f 48%, #10989d 100%);
            color: #fff;
            box-shadow: 0 24px 48px rgba(7, 22, 39, 0.28);
        }

        .future-hero-card::before,
        .future-hero-card::after {
            content: "";
            position: absolute;
            border-radius: 999px;
            pointer-events: none;
        }

        .future-hero-card::before {
            width: 290px;
            height: 290px;
            top: -130px;
            right: -100px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.32), transparent 70%);
        }

        .future-hero-card::after {
            width: 320px;
            height: 320px;
            left: -140px;
            bottom: -200px;
            background: radial-gradient(circle, rgba(242, 162, 58, 0.4), transparent 72%);
        }

        .future-hero-grid {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 18px;
            grid-template-columns: minmax(0, 1.5fr) minmax(240px, 0.9fr);
            align-items: stretch;
            padding: 24px;
        }

        .future-kicker {
            margin: 0 0 8px;
            font-size: .72rem;
            letter-spacing: .22em;
            text-transform: uppercase;
            font-weight: 800;
            color: rgba(237, 247, 255, 0.88);
        }

        .future-title {
            margin: 0;
            color: #fff;
            font-size: clamp(1.6rem, 2.8vw, 2.3rem);
        }

        .future-subtitle {
            margin: 10px 0 0;
            color: rgba(236, 247, 255, 0.92);
            line-height: 1.55;
            max-width: 62ch;
            font-size: .95rem;
        }

        .future-hero-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
            align-items: center;
        }

        .future-hero-actions .btn-muted {
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.34);
            background: rgba(255, 255, 255, 0.14);
        }

        .future-hero-actions .btn-primary {
            background: linear-gradient(140deg, #f4a53f, #e87f1f);
            box-shadow: 0 12px 24px rgba(48, 20, 0, 0.25);
        }

        .future-hero-chip {
            color: #fff;
            border-color: rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.15);
        }

        .future-signal-panel {
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.33);
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.22), rgba(255, 255, 255, 0.1));
            backdrop-filter: blur(6px);
            padding: 14px;
            display: grid;
            gap: 12px;
            align-content: space-between;
            animation: future-float 5s ease-in-out infinite;
        }

        .future-signal-label {
            margin: 0;
            font-size: .73rem;
            text-transform: uppercase;
            letter-spacing: .17em;
            color: rgba(238, 248, 255, 0.88);
            font-weight: 800;
        }

        .future-signal-title {
            margin-top: 8px;
            font-size: 1.32rem;
            line-height: 1.2;
            font-family: "Space Grotesk", sans-serif;
            font-weight: 700;
        }

        .future-signal-sub {
            margin: 6px 0 0;
            font-size: .84rem;
            line-height: 1.45;
            color: rgba(236, 247, 255, 0.9);
        }

        .future-signal-live {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: .8rem;
            color: rgba(245, 252, 255, 0.96);
        }

        .future-signal-dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #90f8e2;
            box-shadow: 0 0 0 0 rgba(144, 248, 226, 0.46);
            animation: future-pulse 1.8s ease-in-out infinite;
        }

        .future-main-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: minmax(0, 1fr);
        }

        .future-chat-card,
        .future-guide-card,
        .future-metric-card,
        .future-chart-card,
        .future-outlook-card {
            padding: 16px;
        }

        .future-head {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            flex-wrap: wrap;
            margin-bottom: 12px;
        }

        .future-head h2,
        .future-head h3 {
            margin: 0;
            color: var(--future-ink);
            font-size: 1.08rem;
            font-family: "Space Grotesk", sans-serif;
        }

        .future-head p {
            margin: 5px 0 0;
            color: #5d7690;
            font-size: .87rem;
            line-height: 1.45;
        }

        .future-tag {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 5px 10px;
            border: 1px solid #d5e2f0;
            background: #f4f8ff;
            color: #4f6983;
            font-size: .74rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 700;
        }

        .future-chat-shell {
            border: 1px solid var(--future-edge);
            border-radius: 18px;
            background:
                radial-gradient(circle at 13% 8%, rgba(13, 103, 145, 0.14), transparent 32%),
                radial-gradient(circle at 84% 18%, rgba(15, 154, 164, 0.14), transparent 35%),
                linear-gradient(170deg, #fbfdff, #f1f7ff);
            padding: 15px;
            box-shadow: var(--future-glow);
        }

        .future-chat-log {
            display: grid;
            gap: 12px;
        }

        .future-chat-row {
            display: flex;
            gap: 10px;
            align-items: flex-end;
            animation: future-rise .38s ease both;
        }

        .future-chat-row.user {
            justify-content: flex-end;
        }

        .future-chat-avatar {
            width: 36px;
            height: 36px;
            border-radius: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: .72rem;
            font-weight: 800;
            letter-spacing: .04em;
            flex-shrink: 0;
        }

        .future-chat-avatar.ai {
            background: linear-gradient(145deg, #0c688f, #0e8f9f);
            color: #fff;
            box-shadow: 0 8px 18px rgba(11, 54, 79, 0.24);
        }

        .future-chat-avatar.user {
            background: linear-gradient(145deg, #f2f7ff, #dce9f9);
            border: 1px solid #cad9e9;
            color: #1e4664;
        }

        .future-chat-bubble {
            max-width: min(80ch, 79%);
            border-radius: 14px;
            padding: 10px 12px;
            line-height: 1.47;
            font-size: .93rem;
            border: 1px solid transparent;
        }

        .future-chat-bubble.ai {
            color: #16344e;
            background: #fff;
            border-color: #c9d8ea;
            border-bottom-left-radius: 6px;
            box-shadow: 0 12px 24px rgba(16, 24, 32, 0.08);
        }

        .future-chat-bubble.user {
            color: #fff;
            background: linear-gradient(140deg, #0f6aa8, #1890b2);
            border-color: rgba(255, 255, 255, 0.24);
            border-bottom-right-radius: 6px;
            box-shadow: 0 12px 26px rgba(9, 52, 80, 0.26);
        }

        .future-chat-bubble .actions {
            margin-top: 8px;
        }

        .future-chat-bubble select {
            min-width: min(340px, 100%);
        }

        .future-chat-meta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 8px;
            color: #5e7892;
            font-size: .78rem;
        }

        .future-typing {
            display: inline-flex;
            gap: 4px;
            align-items: center;
        }

        .future-typing span {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: #6f8aa4;
            animation: future-dot 1.2s infinite ease-in-out;
        }

        .future-typing span:nth-child(2) { animation-delay: 0.15s; }
        .future-typing span:nth-child(3) { animation-delay: 0.3s; }

        #future-reset-wrap {
            justify-content: flex-end;
            margin-top: 10px;
            display: none;
        }

        .future-guide-card {
            border: 1px solid #d5e2ef;
            background: linear-gradient(160deg, #fbfdff, #f1f7ff);
            display: grid;
            align-content: start;
            gap: 12px;
        }

        .future-guide-list {
            display: grid;
            gap: 10px;
        }

        .future-guide-item {
            display: grid;
            grid-template-columns: 30px minmax(0, 1fr);
            gap: 8px;
            align-items: start;
            padding: 10px;
            border-radius: 12px;
            border: 1px solid #d8e4f1;
            background: #fff;
        }

        .future-guide-step {
            width: 30px;
            height: 30px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: .8rem;
            font-weight: 800;
            background: linear-gradient(145deg, #0b678f, #119aa2);
        }

        .future-guide-item p {
            margin: 0;
            font-size: .86rem;
            color: #23445f;
            line-height: 1.42;
        }

        .future-context-grid {
            display: grid;
            gap: 8px;
        }

        .future-context-pill {
            border: 1px solid #d5e2ef;
            border-radius: 12px;
            padding: 9px 10px;
            background: #fff;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .future-context-pill .label {
            color: #5f7891;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 700;
        }

        .future-context-pill .value {
            color: #173852;
            font-size: .9rem;
            font-family: "Space Grotesk", sans-serif;
            font-weight: 800;
        }

        .future-metric-card,
        .future-chart-card,
        .future-outlook-card {
            background: linear-gradient(180deg, #fff, #f5f9ff);
        }

        .future-metric-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .future-metric {
            position: relative;
            border: 1px solid #d6e2ef;
            border-radius: 14px;
            padding: 13px 14px;
            background: linear-gradient(165deg, #fff, #f2f8ff);
            overflow: hidden;
        }

        .future-metric::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #0b678f, #13a1a6);
        }

        .future-metric .label {
            margin: 0;
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #56708a;
            font-weight: 700;
        }

        .future-metric .value {
            margin: 8px 0 0;
            font-size: 1.45rem;
            color: #12324d;
            font-weight: 800;
            font-family: "Space Grotesk", sans-serif;
        }

        .future-chart-grid {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .future-chart-wrap {
            border: 1px solid #d5e1ef;
            border-radius: 14px;
            padding: 10px;
            background: #fff;
            min-height: 248px;
        }

        .future-outlook-list {
            display: grid;
            gap: 10px;
        }

        .future-outlook-row {
            display: flex;
            gap: 10px;
            border: 1px solid #d5e2f0;
            border-radius: 12px;
            padding: 10px;
            background: #fff;
            align-items: flex-start;
        }

        .future-rank {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            flex-shrink: 0;
            color: #fff;
            font-size: .82rem;
            font-weight: 800;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #0b678f, #119aa1);
            box-shadow: 0 10px 20px rgba(10, 63, 94, 0.2);
        }

        .future-outlook-row h4 {
            margin: 0;
            color: #173a56;
            font-size: .94rem;
            line-height: 1.3;
        }

        .future-outlook-row p {
            margin: 3px 0 0;
            color: #607992;
            font-size: .82rem;
        }

        .future-track {
            margin-top: 8px;
            width: 100%;
            height: 7px;
            border-radius: 999px;
            background: #dfe8f5;
            overflow: hidden;
        }

        .future-track span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #0b678f, #12a2a6);
        }

        .future-empty {
            margin: 0;
            border: 1px dashed #cad9ea;
            border-radius: 12px;
            padding: 12px;
            background: #f8fbff;
            color: #56708a;
            font-size: .88rem;
        }

        @keyframes future-dot {
            0%, 80%, 100% { opacity: 0.28; transform: translateY(0); }
            40% { opacity: 1; transform: translateY(-2px); }
        }

        @keyframes future-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-4px); }
        }

        @keyframes future-pulse {
            0% { box-shadow: 0 0 0 0 rgba(143, 248, 225, 0.52); }
            70% { box-shadow: 0 0 0 10px rgba(143, 248, 225, 0); }
            100% { box-shadow: 0 0 0 0 rgba(143, 248, 225, 0); }
        }

        @keyframes future-rise {
            from { opacity: 0; transform: translateY(8px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 1080px) {
            .future-hero-grid,
            .future-main-grid {
                grid-template-columns: 1fr;
            }

            .future-signal-panel {
                animation: none;
            }
        }

        @media (max-width: 980px) {
            .future-metric-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .future-chart-grid {
                grid-template-columns: 1fr;
            }

            .future-chat-bubble {
                max-width: 100%;
            }
        }

        @media (max-width: 640px) {
            .future-hero-grid {
                padding: 20px 16px;
            }

            .future-chat-card,
            .future-guide-card,
            .future-metric-card,
            .future-chart-card,
            .future-outlook-card {
                padding: 14px;
            }

            .future-chat-shell {
                padding: 12px;
            }

            .future-chat-avatar {
                width: 32px;
                height: 32px;
                border-radius: 10px;
                font-size: .66rem;
            }

            .future-chat-bubble select {
                min-width: 0;
            }

            .future-metric-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="future-page">
        <section class="card future-hero-card">
            <div class="future-hero-grid">
                <div>
                    <p class="future-kicker">Predictive Workspace</p>
                    <h1 class="headline future-title">Analyst Future Products</h1>
                    <p class="future-subtitle">
                        Forecast insights powered by Oracle view <code style="color:#fff;">FUTURE_PRODUCTS</code>.
                        Explore demand direction with a guided AI conversation and instant visual analytics.
                    </p>
                    <div class="future-hero-actions">
                        @if($canStockStatus)
                            <a href="{{ route('products.status') }}" class="btn btn-muted">Product Status</a>
                        @endif
                        <a href="{{ route('products.status.future') }}" class="btn btn-primary">Analyst Future</a>
                        <span class="chip future-hero-chip">{{ ucfirst($mode) }} Mode</span>
                        <span class="chip future-hero-chip">Live Oracle Feed</span>
                    </div>
                </div>

            </div>
        </section>

        @if(!empty($futureError))
            <section class="card">
                <div class="flash error">{{ $futureError }}</div>
            </section>
        @endif

        <section class="future-main-grid">
            <article class="card future-chat-card">
                <div class="future-head">
                    <div>
                        <h2>Conversation Builder</h2>
                        <p>Answer the guided prompts and ask AI for a focused forecast sentence.</p>
                    </div>
                    <span class="future-tag">Step by Step</span>
                </div>

                <form id="future-ai-form" method="GET" action="{{ route('products.status.future') }}">
                    <input type="hidden" id="future-ask-flag" name="ask" value="{{ $showAiReply ? '1' : '0' }}">
                    <input type="hidden" id="year" name="year" value="{{ $selectedYear }}">

                    <div class="future-chat-shell">
                        <div class="future-chat-log">
                            <div class="future-chat-row js-step js-step-1">
                                <div class="future-chat-avatar ai">AI</div>
                                <div class="future-chat-bubble ai">
                                    1) Please choose <strong>Analysis Mode</strong>.
                                    <div class="actions">
                                        <select id="mode" name="mode" required>
                                            <option value="" @selected(! $hasModeQuery) disabled>Select analysis mode</option>
                                            <option value="monthly" @selected($hasModeQuery && $mode === 'monthly')>Monthly Product Forecast</option>
                                            <option value="yearly" @selected($hasModeQuery && $mode === 'yearly')>Yearly Product Forecast</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="future-chat-row user js-answer js-answer-1" style="display: none;">
                                <div class="future-chat-bubble user" id="future-answer-mode"></div>
                                <div class="future-chat-avatar user">YOU</div>
                            </div>

                            <div class="future-chat-row js-step js-step-2" style="display: none;">
                                <div class="future-chat-avatar ai">AI</div>
                                <div class="future-chat-bubble ai">
                                    2) Please choose <strong>Forecast Month</strong>.
                                    <div class="actions">
                                        <select id="month" name="month" @disabled($mode !== 'monthly')>
                                            @if($monthOptions->isEmpty())
                                                <option value="">No months available</option>
                                            @endif
                                            @foreach($monthOptions as $monthValue)
                                                @php
                                                    $monthLabel = $monthValue;
                                                    if (preg_match('/^\d{4}-\d{2}$/', (string) $monthValue)) {
                                                        try {
                                                            $monthLabel = \Illuminate\Support\Carbon::createFromFormat('Y-m', (string) $monthValue)->format('M Y');
                                                        } catch (\Throwable) {
                                                            $monthLabel = $monthValue;
                                                        }
                                                    }
                                                @endphp
                                                <option value="{{ $monthValue }}" @selected((string) $selectedMonth === (string) $monthValue)>{{ $monthLabel }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="future-chat-row user js-answer js-answer-2" style="display: none;">
                                <div class="future-chat-bubble user" id="future-answer-month"></div>
                                <div class="future-chat-avatar user">YOU</div>
                            </div>
                            <div class="future-chat-row js-step js-step-4" style="display: none;">
                                <div class="future-chat-avatar ai">AI</div>
                                <div class="future-chat-bubble ai">
                                    3) Please choose <strong>Product</strong>.
                                    <div class="actions">
                                        <select id="product_no" name="product_no">
                                            <option value="">All products</option>
                                            @foreach($productOptions as $product)
                                                <option value="{{ $product->product_no }}" @selected((string) $selectedProductNo === (string) $product->product_no)>
                                                    {{ $product->product_name }} ({{ $product->product_no }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="future-chat-row user js-answer js-answer-4" style="display: none;">
                                <div class="future-chat-bubble user" id="future-answer-product"></div>
                                <div class="future-chat-avatar user">YOU</div>
                            </div>

                            <div class="future-chat-row js-step js-step-5" style="display: none;">
                                <div class="future-chat-avatar ai">AI</div>
                                <div class="future-chat-bubble ai">
                                    Great. Click <strong>Ask AI</strong> to generate the sentence.
                                    <div class="actions">
                                        <button type="submit" class="btn btn-primary" id="future-ask-btn">Ask AI</button>
                                        <a href="{{ route('products.status.future') }}" class="btn btn-muted">Reset</a>
                                    </div>
                                </div>
                            </div>

                            <div class="future-chat-row js-ai-reply" @if(! $showAiReply) style="display: none;" @endif>
                                <div class="future-chat-avatar ai">AI</div>
                                <div class="future-chat-bubble ai">
                                    {{ $forecastMessage }}
                                    <div class="future-chat-meta">
                                        <span class="future-typing" aria-hidden="true"><span></span><span></span><span></span></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="actions" id="future-reset-wrap">
                        <a href="{{ route('products.status.future') }}" class="btn btn-muted">Reset</a>
                    </div>
                </form>
            </article>

        </section>

        <section class="card future-metric-card">
            <div class="future-head">
                <div>
                    <h2>Forecast Pulse</h2>
                    <p>High-level metrics for the current filtered scope.</p>
                </div>
                <span class="future-tag">Context: {{ $contextLabel }}</span>
            </div>

            <div class="future-metric-grid">
                <article class="future-metric">
                    <p class="label">Products In Scope</p>
                    <p class="value">{{ number_format((int) ($metrics['products'] ?? 0)) }}</p>
                </article>
                <article class="future-metric">
                    <p class="label">Forecast Units</p>
                    <p class="value">{{ number_format((float) ($metrics['forecast_units'] ?? 0)) }}</p>
                </article>
                <article class="future-metric">
                    <p class="label">Avg Units / Product</p>
                    <p class="value">{{ number_format((float) ($metrics['avg_units'] ?? 0), 2) }}</p>
                </article>
            </div>
        </section>

        <section class="future-chart-grid">
            <article class="card future-chart-card">
                <div class="future-head">
                    <div>
                        <h3>Top Forecast Products</h3>
                        <p>Bar comparison for projected units by product.</p>
                    </div>
                    <span class="future-tag">Top 10</span>
                </div>
                <div class="future-chart-wrap">
                    <canvas id="future-top-chart" height="220"></canvas>
                </div>
            </article>

            <article class="card future-chart-card">
                <div class="future-head">
                    <div>
                        <h3>Forecast Trend By Month</h3>
                        <p>Monthly movement of projected demand.</p>
                    </div>
                    <span class="future-tag">Trend Line</span>
                </div>
                <div class="future-chart-wrap">
                    <canvas id="future-trend-chart" height="220"></canvas>
                </div>
            </article>
        </section>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (() => {
            const topLabels = @json($chartTopLabels);
            const topValues = @json($chartTopValues);
            const topNames = @json($chartTopNames);
            const trendLabels = @json($trendLabels);
            const trendValues = @json($trendValues);

            const numberLabel = (value) => Number(value ?? 0).toLocaleString();

            const createChart = (id, config) => {
                const node = document.getElementById(id);
                if (!node) return;
                new Chart(node, config);
            };

            const barGradient = (context) => {
                const chart = context.chart;
                const area = chart.chartArea;
                if (!area) {
                    return 'rgba(14, 156, 163, 0.86)';
                }

                const gradient = chart.ctx.createLinearGradient(0, area.bottom, 0, area.top);
                gradient.addColorStop(0, 'rgba(11, 99, 142, 0.86)');
                gradient.addColorStop(1, 'rgba(15, 154, 164, 0.96)');
                return gradient;
            };

            const lineGradient = (context) => {
                const chart = context.chart;
                const area = chart.chartArea;
                if (!area) {
                    return 'rgba(11, 99, 142, 0.2)';
                }

                const gradient = chart.ctx.createLinearGradient(0, area.top, 0, area.bottom);
                gradient.addColorStop(0, 'rgba(15, 154, 164, 0.34)');
                gradient.addColorStop(1, 'rgba(11, 99, 142, 0.04)');
                return gradient;
            };

            createChart('future-top-chart', {
                type: 'bar',
                data: {
                    labels: topLabels,
                    datasets: [{
                        label: 'Forecast Units',
                        data: topValues,
                        borderRadius: 8,
                        borderColor: '#0b5f89',
                        borderWidth: 1.1,
                        backgroundColor: (ctx) => barGradient(ctx)
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => numberLabel(value)
                            }
                        }
                    },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                title: (items) => {
                                    const idx = items?.[0]?.dataIndex ?? 0;
                                    const code = topLabels[idx] ?? '';
                                    const name = topNames[idx] ?? '';
                                    return name ? `${code} - ${name}` : String(code);
                                },
                                label: (item) => `Units: ${numberLabel(item.parsed.y)}`
                            }
                        }
                    }
                }
            });

            createChart('future-trend-chart', {
                type: 'line',
                data: {
                    labels: trendLabels,
                    datasets: [{
                        label: 'Forecast Units',
                        data: trendValues,
                        borderColor: '#0a6a90',
                        backgroundColor: (ctx) => lineGradient(ctx),
                        fill: true,
                        tension: 0.28,
                        pointRadius: 3,
                        pointBackgroundColor: '#0f9da3'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: (value) => numberLabel(value)
                            }
                        }
                    },
                    plugins: {
                        legend: { position: 'bottom' }
                    }
                }
            });

            const mode = document.getElementById('mode');
            const month = document.getElementById('month');
            const year = document.getElementById('year');
            const product = document.getElementById('product_no');
            const askFlag = document.getElementById('future-ask-flag');
            const askBtn = document.getElementById('future-ask-btn');

            const step1 = document.querySelector('.js-step-1');
            const step2 = document.querySelector('.js-step-2');
            const step4 = document.querySelector('.js-step-4');
            const step5 = document.querySelector('.js-step-5');

            const answerRow1 = document.querySelector('.js-answer-1');
            const answerRow2 = document.querySelector('.js-answer-2');
            const answerRow4 = document.querySelector('.js-answer-4');
            const answerMode = document.getElementById('future-answer-mode');
            const answerMonth = document.getElementById('future-answer-month');
            const answerProduct = document.getElementById('future-answer-product');
            const aiReplyRow = document.querySelector('.js-ai-reply');
            const resetWrap = document.getElementById('future-reset-wrap');

            if (!mode || !month || !year || !product || !step1 || !step2 || !step4 || !step5 || !answerRow1 || !answerRow2 || !answerRow4 || !answerMode || !answerMonth || !answerProduct || !aiReplyRow || !resetWrap) {
                return;
            }

            const showAiReply = @json($showAiReply ?? false);

            const setVisible = (el, visible) => {
                el.style.display = visible ? '' : 'none';
            };

            const selectedText = (selectEl, fallback = '') => {
                const option = selectEl.options[selectEl.selectedIndex];
                if (option && option.textContent) {
                    return option.textContent.trim();
                }
                return fallback;
            };

            const updateModeDependents = () => {
                const isMonthly = mode.value === 'monthly';
                month.disabled = !isMonthly;
            };

            const fillAnswerBubbles = () => {
                updateModeDependents();
                answerMode.textContent = `Analysis Mode: ${selectedText(mode, 'Not selected')}`;
                answerMonth.textContent = `Forecast Month: ${selectedText(month, 'Not selected')}`;
                answerProduct.textContent = `Product: ${selectedText(product, 'All products')}`;
            };

            const resetConversation = () => {
                fillAnswerBubbles();
                setVisible(step1, true);
                setVisible(step2, false);
                setVisible(step4, false);
                setVisible(step5, false);

                setVisible(answerRow1, false);
                setVisible(answerRow2, false);
                setVisible(answerRow4, false);
                setVisible(aiReplyRow, false);
                setVisible(resetWrap, false);
            };

            const advanceFromMode = () => {
                fillAnswerBubbles();
                const hasMode = mode.value !== '';
                const isMonthly = mode.value === 'monthly';

                setVisible(answerRow1, hasMode);
                setVisible(step2, isMonthly);

                setVisible(answerRow2, false);
                setVisible(step4, hasMode && !isMonthly);
                setVisible(answerRow4, false);
                setVisible(step5, false);
                setVisible(resetWrap, false);
                setVisible(aiReplyRow, false);
            };

            mode.addEventListener('change', advanceFromMode);
            mode.addEventListener('click', () => {
                if (mode.value !== '') {
                    advanceFromMode();
                }
            });

            month.addEventListener('change', () => {
                fillAnswerBubbles();
                if (month.disabled) {
                    return;
                }

                setVisible(answerRow2, true);
                setVisible(step4, true);
                setVisible(answerRow4, false);
                setVisible(step5, false);
                setVisible(resetWrap, false);
                setVisible(aiReplyRow, false);
            });

            product.addEventListener('change', () => {
                fillAnswerBubbles();
                setVisible(answerRow4, true);
                setVisible(step5, true);
                setVisible(resetWrap, true);
                setVisible(aiReplyRow, false);
            });

            askBtn?.addEventListener('click', () => {
                if (askFlag) {
                    askFlag.value = '1';
                }
            });

            if (showAiReply) {
                fillAnswerBubbles();
                setVisible(answerRow1, true);
                setVisible(answerRow2, mode.value === 'monthly');
                setVisible(answerRow4, true);
                setVisible(step1, true);
                setVisible(step2, mode.value === 'monthly');
                setVisible(step4, true);
                setVisible(step5, true);
                setVisible(aiReplyRow, true);
                setVisible(resetWrap, true);
            } else {
                resetConversation();
            }
        })();
    </script>
@endsection
