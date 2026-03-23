@extends('layouts.ecommerce')

@section('title', 'Shopping Cart')

@section('content')
    <section class="card">
        <div class="actions" style="justify-content: space-between;">
            <div>
                <h1 class="headline">Shopping Cart</h1>
                <p class="subtle">Review quantities before checkout. Live stock is validated against Oracle.</p>
            </div>
            <div class="actions">
                <a href="{{ route('store.catalog') }}" class="btn btn-muted">Continue Shopping</a>
                @if ($items->isNotEmpty())
                    <a href="{{ route('store.checkout') }}" class="btn btn-primary">Proceed To Checkout</a>
                @endif
            </div>
        </div>
    </section>

    @if ($items->isEmpty())
        <section class="card">
            <h3 style="margin-top: 0;">Your cart is empty</h3>
            <p class="subtle">Add products from the catalog to create an order.</p>
            <a href="{{ route('store.catalog') }}" class="btn btn-primary">Go To Shop</a>
        </section>
    @else
        <div class="grid grid-2">
            <section class="card">
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Qty</th>
                            <th style="text-align: right;">Line Total</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($items as $item)
                            @php
                                $isInvalid = !$item['exists'] || $item['qty'] > $item['available_stock'];
                            @endphp
                            <tr>
                                <td>
                                    <strong>{{ $item['product_no'] }}</strong><br>
                                    {{ $item['product_name'] }}<br>
                                    <span class="subtle">{{ $item['product_type_name'] }}</span>
                                </td>
                                <td>${{ number_format((float) $item['sell_price'], 2) }}</td>
                                <td>
                                    {{ number_format((int) $item['available_stock']) }}<br>
                                    <span class="chip">{{ $item['stock_status'] ?: 'N/A' }}</span>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('store.cart.update', ['productNo' => $item['product_no']]) }}" class="actions">
                                        @csrf
                                        @method('PATCH')
                                        <input
                                            type="number"
                                            name="qty"
                                            min="0"
                                            max="{{ max(0, (int) $item['available_stock']) }}"
                                            value="{{ (int) $item['qty'] }}"
                                            style="max-width: 82px; @if($isInvalid) border-color: #a32222; @endif"
                                        >
                                        <button type="submit" class="btn btn-muted">Update</button>
                                    </form>
                                    @if($isInvalid)
                                        <div style="color: #a32222; font-size: .8rem; margin-top: 6px;">Quantity exceeds live stock.</div>
                                    @endif
                                </td>
                                <td style="text-align: right; font-weight: 800;">
                                    ${{ number_format((float) $item['line_total'], 2) }}
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('store.cart.remove', ['productNo' => $item['product_no']]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card">
                <h3 style="margin-top: 0;">Cart Summary</h3>
                <div class="stat" style="margin-top: 10px;">
                    <div class="label">Total Items</div>
                    <div class="value">{{ number_format((int) $totals['items']) }}</div>
                </div>
                <div class="stat" style="margin-top: 10px;">
                    <div class="label">Subtotal</div>
                    <div class="value">${{ number_format((float) $totals['subtotal'], 2) }}</div>
                </div>
                <p class="subtle" style="margin-top: 10px;">
                    Final prices are set by Oracle trigger <code>SALE_ADD</code> during invoice detail insert.
                </p>
                <div class="actions" style="margin-top: 12px;">
                    <a href="{{ route('store.checkout') }}" class="btn btn-primary">Checkout</a>
                    <form method="POST" action="{{ route('store.cart.clear') }}">
                        @csrf
                        <button type="submit" class="btn btn-muted">Clear Cart</button>
                    </form>
                </div>
            </section>
        </div>
    @endif
@endsection
