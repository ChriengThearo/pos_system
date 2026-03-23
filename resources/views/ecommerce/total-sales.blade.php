@extends('layouts.ecommerce')

@section('title', 'Total Sales')

@section('content')
    <section class="card">
        <div class="actions" style="justify-content: space-between;">
            <div>
                <h1 class="headline">Total Amount By Invoice No</h1>
                <p class="subtle">Summary totals by invoice, including discount and remaining balance.</p>
            </div>
        </div>

        <form id="total-sales-search-form" method="GET" action="{{ route('total-sales.index') }}" class="field-grid" style="margin-top: 12px;">
            <div>
                <label for="from_date">From Date</label>
                <input id="from_date" type="date" name="from_date" value="{{ $fromDate }}">
            </div>
            <div>
                <label for="to_date">To Date</label>
                <input id="to_date" type="date" name="to_date" value="{{ $toDate }}">
            </div>
            <div>
                <label for="client_no">Search By Client</label>
                <select id="client_no" name="client_no">
                    <option value="">All clients</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->client_no }}" @selected((string) $clientNo === (string) $client->client_no)>
                            {{ $client->client_name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="q">Search All Fields</label>
                <input id="q" type="text" name="q" value="{{ $q ?? '' }}" placeholder="Invoice no, seller, client, amount, status...">
            </div>
            <div class="actions" style="align-items: end;">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="{{ route('total-sales.index') }}" class="btn btn-muted">Search All</a>
            </div>
        </form>
    </section>

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Invoice No</th>
                    <th>Invoice Date</th>
                    <th>Seller</th>
                    <th>Client No</th>
                    <th>Client Name</th>
                    <th style="text-align: right;">Item No</th>
                    <th style="text-align: right;">Total</th>
                    <th style="text-align: right;">Discount</th>
                    <th style="text-align: right;">Balance</th>
                    <th>Invoice Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($sales as $row)
                    <tr>
                        <td>#{{ $row->invoice_no }}</td>
                        <td>{{ \Illuminate\Support\Carbon::parse($row->invoice_date)->format('Y-m-d') }}</td>
                        <td>{{ $row->seller }}</td>
                        <td>{{ $row->client_no }}</td>
                        <td>{{ $row->client_name }}</td>
                        <td style="text-align: right;">{{ number_format((float) $row->item_qty) }}</td>
                        <td style="text-align: right;">${{ number_format((float) $row->subtotal, 2) }}</td>
                        <td style="text-align: right;">{{ number_format((float) ($row->discount_rate * 100), 2) }}%</td>
                        <td style="text-align: right;">${{ number_format((float) $row->balance, 2) }}</td>
                        <td><span class="chip">{{ $row->invoice_status ?? 'N/A' }}</span></td>
                        <td class="actions">
                            <a href="{{ route('store.orders.show', ['invoiceNo' => (int) $row->invoice_no]) }}" class="btn btn-muted">Detail</a>
                            <a href="{{ route('store.orders.show', ['invoiceNo' => (int) $row->invoice_no, 'print' => 1]) }}" class="btn btn-primary">Print</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="subtle">No invoices found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="card pager">
        {{ $sales->links('pagination.orbit') }}
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
