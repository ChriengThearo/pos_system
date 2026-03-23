@extends('layouts.ecommerce')

@section('title', 'Debt History')

@section('content')
    <section class="card">
        <div class="actions" style="justify-content: space-between;">
            <div>
                <h1 class="headline" style="margin: 0;">Debt History</h1>
                <p class="subtle" style="margin: 6px 0 0;">Payment records for client debts.</p>
            </div>
        </div>

        <div class="actions" style="margin-top: 12px;">
            <a href="{{ route('client-depts.index') }}" class="btn {{ request()->routeIs('client-depts.index') ? 'btn-primary' : 'btn-muted' }}">Client Debts</a>
            <a href="{{ route('client-depts.history') }}" class="btn {{ request()->routeIs('client-depts.history') ? 'btn-primary' : 'btn-muted' }}">Debt History</a>
        </div>

        <form method="GET" action="{{ route('client-depts.history') }}" class="actions" style="margin-top: 12px;">
            <div style="min-width: 260px; flex: 1;">
                <label for="q">Search payment / client / invoice</label>
                <input id="q" type="text" name="q" value="{{ $q ?? '' }}" placeholder="Payment ID, client name, phone, invoice no...">
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
            <a href="{{ route('client-depts.history') }}" class="btn btn-muted">Reset</a>
        </form>
    </section>

    @if(!empty($errorMessage))
        <section class="card">
            <div class="flash error">{{ $errorMessage }}</div>
        </section>
    @endif

    <section class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Payment ID</th>
                    <th>Payment Date</th>
                    <th>Invoice No</th>
                    <th>Client</th>
                    <th style="text-align: right;">Amount</th>
                    <th style="text-align: right;">Recieve</th>
                    <th style="text-align: right;">Recieved To Date</th>
                    <th style="text-align: right;">Debt</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>#{{ $row->payment_id }}</td>
                        <td>
                            {{ \Illuminate\Support\Carbon::parse($row->payment_date ?? $row->invoice_date)->format('Y-m-d H:i') }}
                        </td>
                        <td>#{{ $row->invoice_no }}</td>
                        <td>
                            <strong>{{ $row->client_name }}</strong><br>
                            <span class="subtle">#{{ $row->client_no }} | {{ $row->phone ?: 'N/A' }}</span>
                        </td>
                        <td style="text-align: right;">${{ number_format((float) ($row->amount ?? 0), 2) }}</td>
                        <td style="text-align: right;">${{ number_format((float) ($row->recieve_amount ?? 0), 2) }}</td>
                        <td style="text-align: right;">${{ number_format((float) ($row->recieved_to_date ?? 0), 2) }}</td>
                        <td style="text-align: right; color: #a32222; font-weight: 700;">
                            ${{ number_format((float) ($row->debt_amount ?? 0), 2) }}
                        </td>
                        <td><span class="chip">{{ $row->invoice_status ?? 'N/A' }}</span></td>
                        <td>
                            <a href="{{ route('store.orders.show', ['invoiceNo' => (int) $row->invoice_no]) }}" class="btn btn-muted">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="subtle">No dept history found.</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($rows, 'links'))
            <div style="margin-top: 12px;">
                {{ $rows->links('pagination.orbit') }}
            </div>
        @endif
    </section>
@endsection
