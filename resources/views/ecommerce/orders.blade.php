@extends('layouts.ecommerce')

@section('title', 'Orders')

@section('content')
    @php
        $canViewOrders = \App\Support\StaffAuth::can('orders.read');
    @endphp
    <section class="card">
        <div class="actions" style="justify-content: space-between;">
            <div>
                <h1 class="headline">Order History</h1>
                <p class="subtle">Invoice list from <code>INVOICES</code> joined with clients, employees, and detail totals.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('store.orders') }}" class="field-grid" style="margin-top: 12px;">
            <div>
                <label for="q">Search invoice / phone / client</label>
                <input id="q" type="text" name="q" value="{{ $q }}" placeholder="e.g. 1620 or Sok Dara">
            </div>
            <div class="actions" style="align-items: end;">
                <button type="submit" class="btn btn-primary">Apply</button>
                <a href="{{ route('store.orders') }}" class="btn btn-muted">Reset</a>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Phone</th>
                    <th>Seller</th>
                    <th style="text-align: right;">Items</th>
                    <th style="text-align: right;">Subtotal</th>
                    @if($canViewOrders)
                        <th>Action</th>
                    @endif
                </tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td>
                            <a href="{{ route('store.orders.show', ['invoiceNo' => (int) $order->invoice_no]) }}"
                               style="color: var(--primary); font-weight: 700;">
                                #{{ $order->invoice_no }}
                            </a>
                        </td>
                        <td>{{ \Illuminate\Support\Carbon::parse($order->invoice_date)->format('Y-m-d') }}</td>
                        <td>{{ $order->client_name }}</td>
                        <td>{{ $order->phone }}</td>
                        <td>{{ $order->seller }}</td>
                        <td style="text-align: right;">{{ number_format((float) $order->item_qty) }}</td>
                        <td style="text-align: right; font-weight: 800;">${{ number_format((float) $order->subtotal, 2) }}</td>
                        @if($canViewOrders)
                            <td>
                                <a href="{{ route('store.orders.show', ['invoiceNo' => (int) $order->invoice_no]) }}" class="btn btn-muted">Detail</a>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $canViewOrders ? 8 : 7 }}" class="subtle">No matching invoices.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card pager">
        {{ $orders->links('pagination.orbit') }}
    </section>
@endsection
