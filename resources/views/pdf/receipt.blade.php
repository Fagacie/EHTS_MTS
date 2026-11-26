<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $order['orderNumber'] }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }
        * {
            box-sizing: border-box;
        }
        html, body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
            color: #162133;
            background: #ffffff;
            font-size: 11px;
            line-height: 1.45;
        }
        .brand-bar {
            width: 100%;
            border-collapse: collapse;
        }
        .brand-bar td {
            height: 6px;
            padding: 0;
        }
        .page-wrap {
            padding: 34px 40px 36px 40px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .header-table td {
            vertical-align: top;
        }
        .logo-img {
            height: 46px;
            width: auto;
            display: block;
            margin-bottom: 8px;
        }
        .company-name {
            font-size: 13px;
            font-weight: bold;
            color: #0b2a5b;
            margin: 0 0 2px 0;
        }
        .company-meta {
            font-size: 9.5px;
            color: #5c687a;
            margin: 0;
            line-height: 1.5;
        }
        .doc-title-wrap {
            text-align: right;
        }
        .doc-title {
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 1.2px;
            color: #0b2a5b;
            margin: 0 0 6px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 9.5px;
            font-weight: bold;
            letter-spacing: 0.8px;
            text-transform: uppercase;
        }
        .status-paid {
            background: #e6f6ec;
            color: #166534;
            border: 1px solid #b6e3c6;
        }
        .status-pending {
            background: #fef5e7;
            color: #9a5b13;
            border: 1px solid #f5d8a8;
        }
        .status-danger {
            background: #fdecec;
            color: #991b1b;
            border: 1px solid #f5bcbc;
        }
        .receipt-ref-line {
            margin-top: 8px;
            font-size: 10px;
            color: #475569;
        }
        .receipt-ref-line strong {
            color: #0d1829;
        }
        .meta-box {
            width: 100%;
            border-collapse: collapse;
            background: #f8f7f4;
            border: 1px solid #e6e3dc;
            border-radius: 6px;
            margin-bottom: 24px;
        }
        .meta-box td {
            width: 50%;
            vertical-align: top;
            padding: 14px 16px;
        }
        .meta-box td.right-col {
            border-left: 1px solid #e6e3dc;
        }
        .section-label {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #0f62a4;
            margin: 0 0 8px 0;
        }
        .customer-name {
            font-size: 12.5px;
            font-weight: bold;
            color: #0d1829;
            margin: 0 0 3px 0;
        }
        .meta-text {
            font-size: 10px;
            color: #475569;
            margin: 0 0 2px 0;
        }
        .kv-table {
            width: 100%;
            border-collapse: collapse;
        }
        .kv-table td {
            padding: 2.5px 0;
            width: auto;
            border: none;
            font-size: 10px;
        }
        .kv-table td.kv-key {
            color: #64748b;
            width: 44%;
        }
        .kv-table td.kv-val {
            color: #0d1829;
            font-weight: bold;
            text-align: right;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .items-table thead th {
            background: #0b2a5b;
            color: #ffffff;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.9px;
            padding: 9px 12px;
            text-align: left;
        }
        .items-table thead th.num,
        .items-table tbody td.num {
            text-align: right;
        }
        .items-table thead th.center,
        .items-table tbody td.center {
            text-align: center;
        }
        .items-table tbody td {
            padding: 11px 12px;
            border-bottom: 1px solid #e6e3dc;
            vertical-align: top;
            font-size: 10.5px;
        }
        .item-title {
            font-weight: bold;
            color: #0d1829;
            margin: 0 0 2px 0;
        }
        .item-code {
            font-size: 9px;
            color: #64748b;
            margin: 0;
        }
        .totals-wrap {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        .totals-wrap td.spacer {
            width: 54%;
            vertical-align: top;
            padding-right: 18px;
        }
        .totals-wrap td.ledger {
            width: 46%;
            vertical-align: top;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
        }
        .ledger-table td {
            padding: 6px 10px;
            font-size: 10.5px;
        }
        .ledger-table td.label {
            color: #5c687a;
        }
        .ledger-table td.amount {
            text-align: right;
            font-weight: bold;
            color: #0d1829;
        }
        .ledger-table tr.grand-total td {
            background: #0b2a5b;
            color: #ffffff;
            font-size: 12px;
            font-weight: bold;
            padding: 10px 12px;
        }
        .ledger-table tr.grand-total td.amount {
            color: #ffffff;
        }
        .payment-note-box {
            background: #f4f9f6;
            border: 1px solid #cce6d5;
            border-left: 3px solid #2d8a3e;
            padding: 10px 12px;
            font-size: 9.5px;
            color: #244032;
        }
        .payment-note-box.pending-box {
            background: #fef9ef;
            border-color: #f3dfb6;
            border-left-color: #d97706;
            color: #6b440d;
        }
        .payment-note-title {
            font-weight: bold;
            font-size: 10px;
            margin: 0 0 4px 0;
            color: #0d1829;
        }
        .sub-table-title {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0b2a5b;
            margin: 0 0 8px 0;
        }
        .activity-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .activity-table th {
            text-align: left;
            font-size: 8.5px;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: #64748b;
            border-bottom: 1px solid #d9d5cc;
            padding: 6px 8px;
        }
        .activity-table td {
            font-size: 9.5px;
            color: #1e293b;
            border-bottom: 1px solid #efede8;
            padding: 7px 8px;
        }
        .activity-table th.num,
        .activity-table td.num {
            text-align: right;
        }
        .footer-block {
            margin-top: 28px;
            padding-top: 14px;
            border-top: 1px solid #e2dfd8;
            font-size: 9px;
            color: #64748b;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-table td {
            vertical-align: top;
        }
        .footer-table td.right {
            text-align: right;
        }
    </style>
</head>
<body>
    <!-- Brand Top Ribbon -->
    <table class="brand-bar" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width: 60%; background: #0b2a5b;"></td>
            <td style="width: 25%; background: #0f62a4;"></td>
            <td style="width: 15%; background: #2d8a3e;"></td>
        </tr>
    </table>

    <div class="page-wrap">
        <!-- Header: Company Logo + Official Receipt Title -->
        <table class="header-table" cellspacing="0" cellpadding="0">
            <tr>
                <td style="width: 56%;">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="Ecohydrotech Solutions Sdn. Bhd." class="logo-img">
                    @else
                        <p class="company-name">ECOHYDROTECH SOLUTIONS SDN. BHD.</p>
                    @endif
                    <p class="company-meta">
                        Ecohydrotech Solutions Sdn. Bhd.<br>
                        Hydrology &amp; Water Resources Training Centre<br>
                        Malaysia &middot; Training &amp; Professional Development
                    </p>
                </td>
                <td style="width: 44%;" class="doc-title-wrap">
                    <p class="doc-title">{{ $isPaid ? 'OFFICIAL RECEIPT' : 'ORDER RECEIPT' }}</p>
                    @php
                        $badgeClass = $isPaid
                            ? 'status-paid'
                            : ($order['isPendingPayment'] ? 'status-pending' : 'status-danger');
                    @endphp
                    <span class="status-badge {{ $badgeClass }}">{{ $order['statusLabel'] }}</span>
                    <p class="receipt-ref-line">
                        Receipt No: <strong>RCT-{{ $order['orderNumber'] }}</strong><br>
                        Order Ref: <strong>{{ $order['orderNumber'] }}</strong>
                    </p>
                </td>
            </tr>
        </table>

        <!-- Bill To & Receipt Metadata -->
        <table class="meta-box" cellspacing="0" cellpadding="0">
            <tr>
                <td>
                    <p class="section-label">Received From / Bill To</p>
                    <p class="customer-name">{{ $order['billing']['customerName'] }}</p>
                    <p class="meta-text">{{ $order['billing']['customerEmail'] }}</p>
                    @if (!empty($order['billing']['customerPhone']))
                        <p class="meta-text">{{ $order['billing']['customerPhone'] }}</p>
                    @endif
                    @if (!empty($order['billing']['billingAddress']))
                        <p class="meta-text" style="margin-top: 4px;">{{ $order['billing']['billingAddress'] }}</p>
                    @endif
                </td>
                <td class="right-col">
                    <p class="section-label">Receipt Summary</p>
                    <table class="kv-table" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="kv-key">Order Type</td>
                            <td class="kv-val">{{ $order['orderTypeLabel'] }}</td>
                        </tr>
                        <tr>
                            <td class="kv-key">Order Date</td>
                            <td class="kv-val">{{ $order['createdAtLabel'] }}</td>
                        </tr>
                        <tr>
                            <td class="kv-key">Currency</td>
                            <td class="kv-val">{{ $order['currency'] }}</td>
                        </tr>
                        @if ($latestPayment)
                            <tr>
                                <td class="kv-key">Payment Gateway</td>
                                <td class="kv-val">{{ $latestPayment['gatewayLabel'] }}</td>
                            </tr>
                            <tr>
                                <td class="kv-key">Transaction Ref</td>
                                <td class="kv-val">{{ $latestPayment['paymentReference'] }}</td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        <!-- Itemized Line Items -->
        <table class="items-table" cellspacing="0" cellpadding="0">
            <thead>
                <tr>
                    <th style="width: 48%;">Description</th>
                    <th class="center" style="width: 10%;">Qty</th>
                    <th class="num" style="width: 21%;">Unit Price</th>
                    <th class="num" style="width: 21%;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order['items'] as $item)
                    <tr>
                        <td>
                            <p class="item-title">{{ $item['title'] }}</p>
                            @if (!empty($item['code']))
                                <p class="item-code">Code: {{ $item['code'] }}</p>
                            @endif
                        </td>
                        <td class="center">{{ $item['quantity'] }}</td>
                        <td class="num">{{ $item['unitPriceLabel'] }}</td>
                        <td class="num" style="font-weight: bold;">{{ $item['lineTotalLabel'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals & Verification Note -->
        <table class="totals-wrap" cellspacing="0" cellpadding="0">
            <tr>
                <td class="spacer">
                    <div class="payment-note-box {{ $isPaid ? '' : 'pending-box' }}">
                        <p class="payment-note-title">
                            {{ $isPaid ? 'Payment Confirmed' : 'Payment Status: '.$order['statusLabel'] }}
                        </p>
                        @if ($isPaid)
                            Full payment of <strong>{{ $order['totalLabel'] }}</strong> has been verified and recorded by Ecohydrotech Solutions Sdn. Bhd.
                            @if (!empty($order['bookings']))
                                <br>Reservation Ref: <strong>{{ collect($order['bookings'])->pluck('bookingReference')->implode(', ') }}</strong>
                            @endif
                        @elseif ($order['isPendingPayment'])
                            This order is currently awaiting verified payment confirmation.
                            @if (!empty($order['expiresAtLabel']))
                                Reservation hold ends {{ $order['expiresAtLabel'] }}.
                            @endif
                        @else
                            This order is marked as {{ $order['statusLabel'] }}. Please contact support if you require assistance.
                        @endif
                    </div>
                </td>
                <td class="ledger">
                    <table class="ledger-table" cellspacing="0" cellpadding="0">
                        <tr>
                            <td class="label">Subtotal</td>
                            <td class="amount">{{ $order['totalLabel'] }}</td>
                        </tr>
                        <tr>
                            <td class="label">Tax / Fees</td>
                            <td class="amount">{{ $order['currency'] }} 0.00</td>
                        </tr>
                        <tr class="grand-total">
                            <td>{{ $isPaid ? 'Total Paid' : 'Total Due' }}</td>
                            <td class="amount">{{ $order['totalLabel'] }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Payment Transactions -->
        @if (!empty($order['payments']))
            <p class="sub-table-title">Payment Transactions</p>
            <table class="activity-table" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th>Payment Reference</th>
                        <th>Gateway</th>
                        <th>Date &amp; Time</th>
                        <th>Status</th>
                        <th class="num">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order['payments'] as $payment)
                        <tr>
                            <td style="font-weight: bold;">{{ $payment['paymentReference'] }}</td>
                            <td>{{ $payment['gatewayLabel'] }}</td>
                            <td>
                                {{ trim(($payment['timestampPrefix'] ?? '').' '.($payment['timestampLabel'] ?? '—')) }}
                            </td>
                            <td>{{ $payment['statusLabel'] }}</td>
                            <td class="num" style="font-weight: bold;">{{ $payment['amountLabel'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- Refund Records (if any) -->
        @if (!empty($order['refunds']))
            <p class="sub-table-title">Refund Records</p>
            <table class="activity-table" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th>Refund Reference</th>
                        <th>Method</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th class="num">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order['refunds'] as $refund)
                        <tr>
                            <td style="font-weight: bold;">{{ $refund['refundReference'] }}</td>
                            <td>{{ $refund['methodLabel'] }}</td>
                            <td>{{ $refund['reason'] ?? '—' }}</td>
                            <td>{{ $refund['statusLabel'] }}</td>
                            <td class="num" style="font-weight: bold;">{{ $refund['amountLabel'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- Footer -->
        <div class="footer-block">
            <table class="footer-table" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="width: 65%;">
                        <strong>Ecohydrotech Solutions Sdn. Bhd.</strong><br>
                        This is a computer-generated official receipt from the Ecohydrotech Training Management System. No physical signature is required.
                    </td>
                    <td style="width: 35%;" class="right">
                        Generated {{ $generatedAtLabel }}<br>
                        Ref: {{ $order['orderNumber'] }}
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
