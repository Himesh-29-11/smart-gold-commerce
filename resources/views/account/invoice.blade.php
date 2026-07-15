<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Invoice {{ $order->reference }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #19231f;
            margin: 0;
            background: #eee
        }

        .invoice {
            width: 800px;
            max-width: calc(100% - 40px);
            margin: 30px auto;
            background: #fff;
            padding: 45px;
            box-sizing: border-box
        }

        .top {
            display: flex;
            justify-content: space-between;
            border-bottom: 3px solid #b8862f;
            padding-bottom: 25px
        }

        .brand {
            font-size: 26px;
            letter-spacing: 4px;
            color: #173c34
        }

        .brand small {
            display: block;
            font-size: 9px;
            letter-spacing: 6px;
            color: #b8862f
        }

        .right {
            text-align: right
        }

        .meta,
        .addresses {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin: 30px 0
        }

        .meta p,
        .addresses p {
            line-height: 1.6;
            color: #52605a
        }

        h1 {
            font-size: 30px;
            margin: 0 0 6px
        }

        h3 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #777
        }

        table {
            width: 100%;
            border-collapse: collapse
        }

        th {
            text-align: left;
            background: #f3f1eb;
            padding: 12px;
            font-size: 11px;
            text-transform: uppercase
        }

        td {
            padding: 14px 12px;
            border-bottom: 1px solid #ddd
        }

        .num {
            text-align: right
        }

        .totals {
            width: 350px;
            margin: 25px 0 25px auto
        }

        .totals div {
            display: flex;
            justify-content: space-between;
            padding: 7px
        }

        .totals .grand {
            font-size: 20px;
            border-top: 2px solid #173c34;
            margin-top: 8px;
            padding-top: 14px
        }

        .foot {
            border-top: 1px solid #ddd;
            padding-top: 20px;
            font-size: 11px;
            color: #666;
            line-height: 1.5
        }

        .print {
            display: block;
            margin: 20px auto;
            padding: 12px 25px;
            background: #173c34;
            color: #fff;
            border: 0;
            border-radius: 4px
        }

        @media print {
            body {
                background: #fff
            }

            .invoice {
                margin: 0;
                width: 100%;
                max-width: none
            }

            .print {
                display: none
            }
        }
    </style>
</head>

<body><button class="print" onclick="window.print()">Print / save as PDF</button>
    <div class="invoice">
        <div class="top">
            <div class="brand">AURUM<small>TRUST</small></div>
            <div class="right">
                <h1>TAX INVOICE</h1><b>{{ $order->reference }}</b>
            </div>
        </div>
        <div class="meta">
            <p><b>Invoice date</b><br>{{ $order->updated_at->format('d M Y') }}<br><b>Order
                    date</b><br>{{ $order->created_at->format('d M Y, h:i A') }}</p>
            <p class="right"><b>Payment status</b><br>{{ strtoupper($order->payment_status) }}<br><b>Currency</b><br>INR
            </p>
        </div>
        <div class="addresses">
            <div>
                <h3>Bill to</h3>
                <p>{{ $order->user->name }}<br>{{ $order->user->email }}<br>{{ $order->user->phone }}</p>
            </div>
            <div>
                <h3>Ship to</h3>
                <p>{{ data_get($order->shipping_address, 'full_name') }}<br>{{ data_get($order->shipping_address, 'address_line_1') }},
                    {{ data_get($order->shipping_address, 'address_line_2') }}<br>{{ data_get($order->shipping_address, 'city') }},
                    {{ data_get($order->shipping_address, 'state') }} –
                    {{ data_get($order->shipping_address, 'postal_code') }}</p>
            </div>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Purity / Weight</th>
                    <th>Qty</th>
                    <th class="num">Unit value</th>
                    <th class="num">Tax</th>
                    <th class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td><b>{{ data_get($item->product_snapshot, 'name') }}</b><br><small>SKU
                                {{ data_get($item->product_snapshot, 'sku') }} ·
                                {{ data_get($item->product_snapshot, 'certification') }}</small></td>
                        <td>{{ data_get($item->product_snapshot, 'purity') }} /
                            {{ data_get($item->product_snapshot, 'weight_grams') }}g</td>
                        <td>{{ $item->quantity }}</td>
                        <td class="num">₹{{ number_format($item->unit_price, 2) }}</td>
                        <td class="num">₹{{ number_format($item->tax_amount, 2) }}</td>
                        <td class="num">₹{{ number_format($item->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="totals">
            <div><span>Subtotal</span><b>₹{{ number_format($order->subtotal, 2) }}</b></div>
            <div><span>GST</span><b>₹{{ number_format($order->tax, 2) }}</b></div>
            <div><span>Delivery</span><b>₹{{ number_format($order->delivery_charge, 2) }}</b></div>
            <div><span>Discount</span><b>− ₹{{ number_format($order->discount, 2) }}</b></div>
            <div class="grand"><span>Total</span><b>₹{{ number_format($order->total, 2) }}</b></div>
        </div>
        <div class="foot"><b>Certification and partner records</b>
            <p>Individual product certificates and hallmark references accompany eligible fulfilled products. This
                system-generated invoice is valid only for a payment marked PAID after gateway verification. Production
                deployments must add the contracted seller’s legal name, GSTIN, registered address, HSN/SAC and tax
                place-of-supply fields.</p>
        </div>
    </div>
</body>

</html>
