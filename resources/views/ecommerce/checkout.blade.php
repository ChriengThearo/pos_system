@extends('layouts.ecommerce')

@section('title', 'Checkout')

@section('content')
    <section class="card">
        <h1 class="headline">Checkout</h1>
        <p class="subtle">
            This flow inserts rows into <code>CLIENTS</code>, <code>INVOICES</code>, and <code>INVOICE_DETAILS</code>.
            Product stock and prices are enforced by Oracle triggers.
        </p>
    </section>

    <div class="grid grid-2">
        <section class="card">
            <h2 style="margin-top: 0;">Customer & Invoice Details</h2>
            <form method="POST" action="{{ route('store.checkout.place') }}">
                @csrf

                <div class="field-grid">
                    <div>
                        <label for="customer_name">Customer name</label>
                        <input
                            id="customer_name"
                            type="text"
                            name="customer_name"
                            value="{{ old('customer_name') }}"
                            required
                            maxlength="50"
                            placeholder="e.g. Sok Dara"
                        >
                    </div>

                    <div>
                        <label for="phone">Phone</label>
                        <input
                            id="phone"
                            type="text"
                            name="phone"
                            value="{{ old('phone') }}"
                            required
                            maxlength="15"
                            placeholder="e.g. 0128000123"
                        >
                    </div>

                    <div>
                        <label for="city">City</label>
                        <input id="city" type="text" name="city" value="{{ old('city') }}" maxlength="50">
                    </div>

                    <div>
                        <label for="address">Address</label>
                        <input id="address" type="text" name="address" value="{{ old('address') }}" maxlength="150">
                    </div>

                    <div>
                        <label for="client_type">Client type</label>
                        <select id="client_type" name="client_type">
                            @foreach($clientTypes as $type)
                                <option
                                    value="{{ $type->clienttype_id }}"
                                    @selected((string) old('client_type', $defaultClientType) === (string) $type->clienttype_id)
                                >
                                    {{ $type->type_name }} (rate: {{ $type->discount_rate }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label>Logged-In Seller</label>
                        <div class="chip" style="width: 100%; justify-content: flex-start; min-height: 40px;">
                            {{ $staff['employee_name'] ?? 'Unknown Staff' }} (ID: {{ $staff['employee_id'] ?? 'N/A' }})
                        </div>
                    </div>

                    <div>
                        <label for="invoice_status">Invoice status</label>
                        <select id="invoice_status" name="invoice_status" required>
                            @foreach($invoiceStatuses as $invoiceStatus)
                                <option value="{{ $invoiceStatus }}" @selected(old('invoice_status', 'UNPAID') === $invoiceStatus)>
                                    {{ $invoiceStatus }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="margin-top: 12px;">
                    <label for="invoice_memo">Memo / Notes</label>
                    <textarea id="invoice_memo" name="invoice_memo" maxlength="100">{{ old('invoice_memo') }}</textarea>
                </div>

                <div class="actions" style="margin-top: 12px;">
                    <button type="submit" class="btn btn-primary">Place Order</button>
                    <a href="{{ route('store.cart') }}" class="btn btn-muted">Back To Cart</a>
                </div>
            </form>
        </section>

        <section class="card">
            <h2 style="margin-top: 0;">Order Summary</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th style="text-align: right;">Price</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td>
                                <strong>{{ $item['product_no'] }}</strong><br>
                                {{ $item['product_name'] }}
                            </td>
                            <td>{{ number_format((int) $item['qty']) }}</td>
                            <td style="text-align: right;">${{ number_format((float) $item['sell_price'], 2) }}</td>
                            <td style="text-align: right;">${{ number_format((float) $item['line_total'], 2) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="stat" style="margin-top: 12px;">
                <div class="label">Items</div>
                <div class="value">{{ number_format((int) $totals['items']) }}</div>
            </div>

            <div class="stat" style="margin-top: 10px;">
                <div class="label">Subtotal</div>
                <div class="value">${{ number_format((float) $totals['subtotal'], 2) }}</div>
            </div>
        </section>
    </div>
@endsection
