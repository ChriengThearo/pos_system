@extends('layouts.ecommerce')

@section('title', 'Oracle Commerce Dashboard')

@section('content')
    @php
        $canShop = \App\Support\StaffAuth::can('shop.read');
        $canOrders = \App\Support\StaffAuth::can('orders.read');
        $canDeepCheck = \App\Support\StaffAuth::can('system.audit');
    @endphp
    <section class="card">
        <div class="actions" style="justify-content: space-between;">
            <div>
                <h1 class="headline">Oracle E-Commerce Dashboard</h1>
                <p class="subtle">
                    Live metrics from <strong>C##website_v1</strong> using <code>PRODUCTS</code>,
                    <code>CLIENTS</code>, <code>INVOICES</code>, and <code>INVOICE_DETAILS</code>.
                </p>
            </div>
            <div class="actions">
                @if($canShop)
                    <a href="{{ route('store.catalog') }}" class="btn btn-primary">Open Shop</a>
                @endif
                @if($canDeepCheck)
                    <a href="{{ route('store.deep-check') }}" class="btn btn-muted">Schema Check</a>
                @endif
            </div>
        </div>

        <div class="grid grid-4" style="margin-top: 14px;">
            <article class="stat">
                <div class="label">Products</div>
                <div class="value">{{ number_format($metrics['products']) }}</div>
            </article>
            <article class="stat">
                <div class="label">Clients</div>
                <div class="value">{{ number_format($metrics['clients']) }}</div>
            </article>
            <article class="stat">
                <div class="label">Invoices</div>
                <div class="value">{{ number_format($metrics['invoices']) }}</div>
            </article>
            <article class="stat">
                <div class="label">Revenue (raw)</div>
                <div class="value">${{ number_format((float) $metrics['revenue'], 2) }}</div>
            </article>
        </div>
    </section>

    <div class="grid grid-2">
        <section class="card">
            <div class="actions" style="justify-content: space-between;">
                <h2 style="margin: 0;">Recent Invoices</h2>
                @if($canOrders)
                    <a href="{{ route('store.orders') }}" class="chip">View all</a>
                @endif
            </div>
            <div class="table-wrap" style="margin-top: 12px;">
                <table>
                    <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Seller</th>
                        <th>Status</th>
                        <th style="text-align: right;">Subtotal</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($recentInvoices as $row)
                        <tr>
                            <td>
                                @if($canOrders)
                                    <a href="{{ route('store.orders.show', ['invoiceNo' => (int) $row->invoice_no]) }}"
                                       style="color: var(--primary); font-weight: 700;">
                                        #{{ $row->invoice_no }}
                                    </a>
                                @else
                                    <strong>#{{ $row->invoice_no }}</strong>
                                @endif
                            </td>
                            <td>{{ \Illuminate\Support\Carbon::parse($row->invoice_date)->format('Y-m-d') }}</td>
                            <td>{{ $row->client_name }}</td>
                            <td>{{ $row->seller }}</td>
                            <td><span class="chip">{{ $row->invoice_status ?? 'N/A' }}</span></td>
                            <td style="text-align: right;">${{ number_format((float) $row->subtotal, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="subtle">No invoices found.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card">
            <h2 style="margin-top: 0;">Stock Alerts</h2>
            <p class="subtle">Derived from <code>ALERT_STOCKS</code> trigger-managed records.</p>
            <div class="table-wrap" style="margin-top: 12px;">
                <table>
                    <thead>
                    <tr>
                        <th>Product</th>
                        <th>Current Qty</th>
                        <th>Lower Qty</th>
                        <th>Upper Qty</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($lowStocks as $row)
                        <tr>
                            <td>
                                <strong>{{ $row->product_no }}</strong><br>
                                <span class="subtle">{{ $row->product_name }}</span>
                            </td>
                            <td>{{ number_format((float) $row->qty_on_hand) }}</td>
                            <td>{{ number_format((float) $row->lower_qty) }}</td>
                            <td>{{ number_format((float) $row->higher_qty) }}</td>
                            <td><span class="chip">{{ $row->stock_status ?? 'N/A' }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="subtle">No stock alerts.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <section class="card">
        <h2 style="margin-top: 0;">Top Products by Sales</h2>
        <p class="subtle">Grouped from view <code>MONTHLY_SALES</code>.</p>

        <div class="grid grid-3" style="margin-top: 12px;">
            @forelse($topProducts as $row)
                <article class="stat">
                    <div class="label">{{ $row->product_no }}</div>
                    <div style="font-weight: 800; margin-bottom: 6px;">{{ $row->product_name }}</div>
                    <div class="subtle">Sales: <strong>${{ number_format((float) $row->sales, 2) }}</strong></div>
                    <div class="subtle">Units: <strong>{{ number_format((float) $row->units) }}</strong></div>
                </article>
            @empty
                <p class="subtle">No sales data available.</p>
            @endforelse
        </div>
    </section>
@endsection
