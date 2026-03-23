@extends('layouts.ecommerce')

@section('title', 'Purchase History')

@section('content')
    @php
        $canOrders = \App\Support\StaffAuth::can('orders.read');
    @endphp
    <section class="card">
        <div class="actions">
            @if($canOrders)
                <a href="{{ route('store.orders') }}" class="btn btn-muted">Order Request</a>
            @endif
            <a href="{{ route('purchases.history') }}" class="btn btn-primary">Purchase Request</a>
        </div>
    </section>

    <section class="card">
        <div class="actions" style="justify-content: space-between;">
            <div>
                <h1 class="headline">Purchase History</h1>
                <p class="subtle">All purchase records with filters for search, date range, supplier, and sort order.</p>
            </div>
        </div>

        <form method="GET" action="{{ route('purchases.history') }}" class="field-grid" style="margin-top: 12px;">
            <div>
                <label for="q">Search</label>
                <input id="q" type="text" name="q" value="{{ $q }}" placeholder="Purchase no or supplier name">
            </div>
            <div>
                <label for="supplier_id">Supplier</label>
                <select id="supplier_id" name="supplier_id">
                    <option value="">All suppliers</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->supplier_id }}" @selected((string) $supplierId === (string) $supplier->supplier_id)>
                            {{ $supplier->supplier_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from_date">From Date</label>
                <input id="from_date" type="date" name="from_date" value="{{ $fromDate }}">
            </div>
            <div>
                <label for="to_date">To Date</label>
                <input id="to_date" type="date" name="to_date" value="{{ $toDate }}">
            </div>
            <div>
                <label for="sort">Sort</label>
                <select id="sort" name="sort" onchange="this.form.submit()">
                    @foreach($sortOptions as $key => $label)
                        <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="actions" style="align-items: end;">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="{{ route('purchases.history') }}" class="btn btn-muted">Search All</a>
            </div>
        </form>
    </section>

    @if($errorMessage)
        <section class="card">
            <div class="flash error">{{ $errorMessage }}</div>
        </section>
    @endif

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Purchase No</th>
                    <th>Purchase Date</th>
                    <th>Supplier</th>
                    <th>Buyer</th>
                    <th style="text-align: right;">Item No</th>
                    <th style="text-align: right;">Total</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($purchases as $row)
                    <tr>
                        <td>#{{ $row->purchase_no }}</td>
                        <td>
                            @if($row->purchase_date)
                                {{ \Illuminate\Support\Carbon::parse($row->purchase_date)->format('Y-m-d') }}
                            @else
                                N/A
                            @endif
                        </td>
                        <td>{{ $row->supplier_name ?: 'N/A' }}</td>
                        <td>{{ $row->buyer ?: 'N/A' }}</td>
                        <td style="text-align: right;">{{ number_format((float) $row->item_qty) }}</td>
                        <td style="text-align: right;">${{ number_format((float) $row->subtotal, 2) }}</td>
                        <td><span class="chip">{{ $row->purchase_status ?: 'N/A' }}</span></td>
                        <td>
                            <a href="{{ route('purchases.index', ['purchase_no' => $row->purchase_no]) }}" class="btn btn-muted">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="subtle">No purchase history found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if(method_exists($purchases, 'links'))
        <section class="card pager">
            {{ $purchases->links('pagination.orbit') }}
        </section>
    @endif
@endsection
