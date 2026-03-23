@php
    $header = $order['header'];
    $storeName = 'Electronic Store';
    $storeLogoPath = 'images/store-logo.png';
    $storeLogoExists = file_exists(public_path($storeLogoPath));
    $invoiceDate = \Illuminate\Support\Carbon::parse($header->invoice_date)->format('Y-m-d H:i');
    $printedAt = isset($printedAt) ? \Illuminate\Support\Carbon::parse($printedAt)->format('Y-m-d H:i') : null;
    $discountRate = (float) ($order['discount_rate'] ?? 0);
    $grandTotal = (float) ($order['grand_total'] ?? 0);
    $hasDebt = (float) ($debtAmount ?? 0) > 0;
    $currentDebt = round(max(0, (float) ($debtAmount ?? 0)), 2);
    $recievedAmount = round(max(0, $grandTotal - $currentDebt), 2);
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt #{{ $header->invoice_no }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --ink: #101820;
            --muted: #5a6b7f;
            --line: #d8dee7;
            --soft: #f2f5f9;
            --primary: #0055a5;
            --accent: #e36414;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "Manrope", sans-serif;
            color: var(--ink);
            background: radial-gradient(circle at 20% 0%, rgba(0, 85, 165, 0.12), transparent 40%),
                        radial-gradient(circle at 85% 10%, rgba(227, 100, 20, 0.12), transparent 38%),
                        #eef2f7;
        }

        .receipt {
            max-width: 720px;
            margin: 32px auto;
            padding: 28px;
            background: #fff;
            border-radius: 18px;
            border: 1px solid var(--line);
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.12);
        }

        .flash {
            border-radius: 12px;
            padding: 10px 12px;
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
            font-size: .92rem;
            margin-bottom: 16px;
        }

        .receipt__header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            padding-bottom: 16px;
            border-bottom: 1px dashed var(--line);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .brand-logo {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            object-fit: cover;
            border: 1px solid rgba(0, 0, 0, 0.08);
            background: #fff;
        }

        .brand-dot {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            background: linear-gradient(140deg, var(--primary), var(--accent));
            box-shadow: 0 0 0 10px rgba(0, 85, 165, 0.1);
        }

        .brand-text {
            display: grid;
            gap: 4px;
        }

        .brand-text strong {
            font-family: "Space Grotesk", sans-serif;
            font-size: 1.25rem;
            letter-spacing: .02em;
        }

        .brand-text span {
            color: var(--muted);
            font-size: .88rem;
            text-transform: uppercase;
            letter-spacing: .14em;
        }

        .receipt__meta {
            text-align: right;
            font-size: .92rem;
            color: var(--muted);
            line-height: 1.6;
        }

        .receipt__meta strong { color: var(--ink); }

        .section {
            margin-top: 18px;
            padding: 14px 0;
            border-bottom: 1px dashed var(--line);
        }

        .section:last-of-type { border-bottom: 0; }

        .section h2 {
            margin: 0 0 10px;
            font-size: .9rem;
            text-transform: uppercase;
            letter-spacing: .16em;
            color: var(--muted);
        }

        .two-col {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            font-size: .95rem;
        }

        .two-col strong {
            display: block;
            margin-bottom: 6px;
            font-size: 1rem;
        }

        .items {
            width: 100%;
            border-collapse: collapse;
            font-size: .92rem;
        }

        .items th,
        .items td {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid #edf1f6;
        }

        .items th {
            font-size: .78rem;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: var(--muted);
            background: var(--soft);
        }

        .items tr:last-child td { border-bottom: 0; }

        .right { text-align: right; }

        .totals {
            margin-top: 16px;
            display: grid;
            gap: 8px;
            font-size: .95rem;
        }

        .totals .row {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .totals .row strong { font-size: 1.05rem; }

        .totals .grand {
            padding-top: 10px;
            border-top: 1px solid var(--line);
            font-size: 1.1rem;
        }

        .note {
            margin-top: 14px;
            padding: 12px;
            border-radius: 12px;
            background: var(--soft);
            color: var(--muted);
            font-size: .9rem;
        }

        .actions {
            margin-top: 18px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn {
            border: 1px solid var(--line);
            background: #fff;
            color: var(--ink);
            padding: 8px 12px;
            border-radius: 10px;
            font-weight: 700;
            font-size: .9rem;
            text-decoration: none;
            cursor: pointer;
        }

        .btn.primary {
            border-color: transparent;
            color: #fff;
            background: linear-gradient(140deg, var(--primary), #0070d2);
        }

        .no-print { display: block; }

        @media (max-width: 640px) {
            .receipt { margin: 16px; padding: 20px; }
            .two-col { grid-template-columns: 1fr; }
            .receipt__meta { text-align: left; }
        }

        @media print {
            @page { margin: 12mm; }
            body { background: #fff; }
            .receipt {
                margin: 0;
                max-width: 100%;
                border: 0;
                border-radius: 0;
                box-shadow: none;
                padding: 0;
            }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <main class="receipt">
        @if (session('success'))
            <div class="flash no-print">{{ session('success') }}</div>
        @endif

        <header class="receipt__header">
            <div class="brand">
                @if($storeLogoExists)
                    <img src="{{ asset($storeLogoPath) }}" alt="{{ $storeName }} logo" class="brand-logo">
                @else
                    <span class="brand-dot" aria-hidden="true"></span>
                @endif
                <div class="brand-text">
                    <strong>{{ $storeName }}</strong>
                    @if(! $hasDebt)
                        <span>Official Receipt</span>
                    @endif
                </div>
            </div>
            <div class="receipt__meta">
                <div><strong>Invoice</strong> #{{ $header->invoice_no }}</div>
                <div><strong>Date</strong> {{ $invoiceDate }}</div>
                <div><strong>Status</strong> {{ $header->invoice_status ?? 'N/A' }}</div>
                @if($printedAt)
                    <div><strong>Printed</strong> {{ $printedAt }}</div>
                @endif
            </div>
        </header>

        <section class="section">
            <h2>Customer and Seller</h2>
            <div class="two-col">
                <div>
                    <strong>{{ $header->client_name }}</strong>
                    Client No: {{ $header->client_no }}<br>
                    Phone: {{ $header->phone }}<br>
                    City: {{ $header->city ?: 'N/A' }}<br>
                    Address: {{ $header->address ?: 'N/A' }}
                </div>
                <div>
                    <strong>{{ $header->employee_name }}</strong>
                    Employee ID: {{ $header->employee_id }}<br>
                    Memo: {{ $header->invoice_memo ?: 'N/A' }}
                </div>
            </div>
        </section>

        <section class="section">
            <h2>Items</h2>
            <table class="items">
                <thead>
                <tr>
                    <th>Item</th>
                    <th class="right">Qty</th>
                    <th class="right">Price</th>
                    <th class="right">Total</th>
                </tr>
                </thead>
                <tbody>
                @foreach($order['items'] as $item)
                    <tr>
                        <td>{{ $item->product_name }}<br><span style="color: var(--muted); font-size: .8rem;">#{{ $item->product_no }}</span></td>
                        <td class="right">{{ number_format((float) $item->qty) }}</td>
                        <td class="right">${{ number_format((float) $item->price, 2) }}</td>
                        <td class="right">${{ number_format((float) $item->line_total, 2) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>

            <div class="totals">
                <div class="row">
                    <span>Item Count</span>
                    <span>{{ number_format((int) $order['item_count']) }}</span>
                </div>
                <div class="row">
                    <span>Subtotal</span>
                    <span>${{ number_format((float) $order['subtotal'], 2) }}</span>
                </div>
                <div class="row">
                    <span>Discount ({{ number_format($discountRate * 100, 2) }}%)</span>
                    <span>-${{ number_format((float) $order['discount_amount'], 2) }}</span>
                </div>
                <div class="row grand">
                    <strong>{{ $hasDebt ? 'Amount' : 'Grand Total' }}</strong>
                    <strong>${{ number_format($grandTotal, 2) }}</strong>
                </div>
                @if($hasDebt)
                    <div class="row">
                        <span>Recieved</span>
                        <span>${{ number_format($recievedAmount, 2) }}</span>
                    </div>
                    <div class="row">
                        <span>Debt</span>
                        <span>${{ number_format($currentDebt, 2) }}</span>
                    </div>
                @endif
            </div>
        </section>

        <div class="note">
            Thank you for shopping with us. Please keep this receipt for your records.
        </div>

        <div class="actions no-print">
            <a class="btn" href="{{ route('store.orders.show', ['invoiceNo' => (int) $header->invoice_no]) }}">Back to Invoice</a>
            <a class="btn" href="{{ route('store.orders') }}">Back to Orders</a>
            <button class="btn primary" type="button" onclick="window.print()">Print Again</button>
        </div>
    </main>

    <script>
        window.addEventListener('load', () => {
            const shouldAutoPrint = @json(isset($autoPrint) ? (bool) $autoPrint : ! $hasDebt);
            if (shouldAutoPrint) {
                setTimeout(() => window.print(), 250);
            }
        });
    </script>
</body>
</html>
