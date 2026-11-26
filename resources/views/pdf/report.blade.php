<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Ecohydrotech — {{ $reportTitle }}</title>
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
            font-size: 9.5px;
            line-height: 1.42;
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
            padding: 26px 34px 30px 34px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            border-bottom: 1.5px solid #e2dfd8;
        }
        .header-table td {
            vertical-align: top;
            padding-bottom: 12px;
        }
        .logo-img {
            height: 42px;
            width: auto;
            display: block;
            margin-bottom: 5px;
        }
        .company-name {
            font-size: 11.5px;
            font-weight: bold;
            color: #0b2a5b;
            margin: 0 0 2px 0;
        }
        .company-meta {
            font-size: 8.6px;
            color: #5c687a;
            margin: 0;
            line-height: 1.45;
        }
        .doc-title-wrap {
            text-align: right;
        }
        .doc-title {
            font-size: 15px;
            font-weight: bold;
            letter-spacing: 0.6px;
            color: #0b2a5b;
            margin: 0 0 5px 0;
        }
        .period-pill {
            display: inline-block;
            padding: 2.5px 9px;
            border-radius: 10px;
            background: #eef4fb;
            border: 1px solid #c7daf3;
            color: #0b2a5b;
            font-size: 8.5px;
            font-weight: bold;
        }
        .meta-line {
            margin-top: 6px;
            font-size: 8.5px;
            color: #5c687a;
            line-height: 1.45;
        }
        .meta-line strong {
            color: #0d1829;
        }

        /* KPI Strip */
        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 7px 0;
            margin: 0 -7px 16px -7px;
        }
        .kpi-cell {
            width: 25%;
            vertical-align: top;
            background: #f8f7f4;
            border: 1px solid #e4e1d9;
            border-radius: 5px;
            padding: 9px 10px;
        }
        .kpi-cell.dark {
            background: #0b2a5b;
            border-color: #0b2a5b;
            color: #ffffff;
        }
        .kpi-label {
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: #5c687a;
            margin: 0 0 4px 0;
        }
        .kpi-cell.dark .kpi-label { color: #b7c9e2; }
        .kpi-value {
            font-size: 13.5px;
            font-weight: bold;
            color: #0d1829;
            margin: 0 0 3px 0;
        }
        .kpi-cell.dark .kpi-value { color: #ffffff; }
        .kpi-sub {
            font-size: 7.8px;
            color: #64748b;
            margin: 0;
        }
        .kpi-cell.dark .kpi-sub { color: #cbd5e1; }

        /* Two-column summary */
        .two-col {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        .two-col > tbody > tr > td {
            width: 49%;
            vertical-align: top;
        }
        .two-col > tbody > tr > td.gap { width: 2%; }
        .panel-box {
            border: 1px solid #e2dfd8;
            border-radius: 5px;
            overflow: hidden;
        }
        .panel-header {
            background: #f4f3ef;
            border-bottom: 1px solid #e2dfd8;
            padding: 7px 10px;
            font-size: 8.8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #0b2a5b;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
        }
        .ledger-table td {
            padding: 5.5px 10px;
            border-bottom: 1px solid #efede8;
            font-size: 8.8px;
        }
        .ledger-table tr:last-child td { border-bottom: none; }
        .ledger-table td.key { color: #475569; }
        .ledger-table td.val {
            text-align: right;
            font-weight: bold;
            color: #0d1829;
            white-space: nowrap;
        }
        .ledger-table td.val.neg { color: #b91c1c; }
        .ledger-table tr.total-row td {
            background: #0b2a5b;
            color: #ffffff;
            font-weight: bold;
            font-size: 9.2px;
            padding: 7px 10px;
        }
        .ledger-table tr.total-row td.val { color: #ffffff; }
        .panel-foot {
            background: #faf9f6;
            border-top: 1px solid #e6e3dc;
            padding: 5.5px 10px;
            font-size: 7.8px;
            color: #64748b;
        }

        /* Section Headings & Data Tables */
        .section-heading {
            font-size: 10.5px;
            font-weight: bold;
            color: #0b2a5b;
            margin: 15px 0 2px 0;
        }
        .section-sub {
            font-size: 8.2px;
            color: #64748b;
            margin: 0 0 7px 0;
        }
        .sub-session-header {
            background: #eef4fb;
            border: 1px solid #c7daf3;
            border-bottom: none;
            padding: 6px 9px;
            font-size: 8.8px;
            font-weight: bold;
            color: #0b2a5b;
            margin-top: 10px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            border: 1px solid #dfdcd4;
        }
        .data-table thead th {
            background: #0b2a5b;
            color: #ffffff;
            font-size: 7.6px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 7px 8px;
            text-align: left;
            border-right: 1px solid #1d3d70;
        }
        .data-table thead th:last-child { border-right: none; }
        .data-table thead th.num,
        .data-table tbody td.num,
        .data-table tfoot td.num { text-align: right; }
        .data-table tbody td {
            padding: 6.5px 8px;
            border-bottom: 1px solid #ebe8e0;
            vertical-align: top;
            font-size: 8.5px;
            color: #1e293b;
        }
        .data-table tbody tr.alt td { background: #faf9f6; }
        .data-table tfoot td {
            background: #f1f0eb;
            border-top: 1.5px solid #cbd5e1;
            padding: 7px 8px;
            font-size: 8.6px;
            font-weight: bold;
            color: #0b2a5b;
        }
        .row-title {
            font-weight: bold;
            color: #0d1829;
            margin: 0 0 1px 0;
        }
        .row-sub {
            font-size: 7.7px;
            color: #64748b;
            margin: 0;
        }
        .status-chip {
            display: inline-block;
            padding: 1.5px 6px;
            border-radius: 7px;
            font-size: 7.4px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .chip-green { background: #e6f6ec; color: #166534; }
        .chip-amber { background: #fef5e7; color: #9a5b13; }
        .chip-red { background: #fdecec; color: #991b1b; }
        .chip-neutral { background: #f1f5f9; color: #475569; }
        .empty-row {
            text-align: center;
            color: #64748b;
            padding: 12px 10px;
            font-style: italic;
        }
        .footer-block {
            margin-top: 18px;
            padding-top: 9px;
            border-top: 1px solid #e2dfd8;
            font-size: 8px;
            color: #64748b;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-table td.right { text-align: right; }
    </style>
</head>
<body>
    @php
        $show = static fn (string $key): bool => $section === 'all' || $section === $key;
        $chipFor = static function (string $status): string {
            $s = strtolower($status);
            if (in_array($s, ['published', 'confirmed', 'successful', 'paid', 'completed', 'issued', 'present', 'verified'], true)) {
                return 'chip-green';
            }
            if (in_array($s, ['pending', 'pending payment', 'draft', 'requested', 'processing', 'submitted', 'correction requested', 'unrecorded'], true)) {
                return 'chip-amber';
            }
            if (in_array($s, ['failed', 'cancelled', 'revoked', 'rejected', 'absent', 'expired', 'refunded'], true)) {
                return 'chip-red';
            }
            return 'chip-neutral';
        };
    @endphp

    <!-- Brand Ribbon -->
    <table class="brand-bar" cellspacing="0" cellpadding="0">
        <tr>
            <td style="width: 60%; background: #0b2a5b;"></td>
            <td style="width: 25%; background: #0f62a4;"></td>
            <td style="width: 15%; background: #159b9a;"></td>
        </tr>
    </table>

    <div class="page-wrap">
        <!-- Company Header -->
        <table class="header-table" cellspacing="0" cellpadding="0">
            <tr>
                <td style="width: 52%;">
                    @if ($logoDataUri)
                        <img src="{{ $logoDataUri }}" alt="Ecohydrotech Solutions Sdn. Bhd." class="logo-img">
                    @else
                        <p class="company-name">ECOHYDROTECH SOLUTIONS SDN. BHD.</p>
                    @endif
                    <p class="company-meta">
                        Ecohydrotech Solutions Sdn. Bhd.<br>
                        Hydrology &amp; Water Resources Training Centre &middot; Malaysia
                    </p>
                </td>
                <td style="width: 48%;" class="doc-title-wrap">
                    <p class="doc-title">{{ $reportTitle }}</p>
                    <span class="period-pill">Period: {{ $periodLabel }}</span>
                    <p class="meta-line">
                        Generated: <strong>{{ $generatedAtLabel }}</strong><br>
                        Prepared by: <strong>{{ $preparedBy }}</strong>
                    </p>
                </td>
            </tr>
        </table>

        <!-- Executive KPI Strip (Always shown on Full Report) -->
        @if ($section === 'all')
            <table class="kpi-table" cellspacing="0" cellpadding="0">
                <tr>
                    <td class="kpi-cell dark">
                        <p class="kpi-label">Net Revenue</p>
                        <p class="kpi-value">MYR {{ number_format($report['revenue']['net_myr'], 2) }}</p>
                        <p class="kpi-sub">Gross MYR {{ number_format($report['revenue']['gross_myr'], 2) }} &middot; {{ $report['revenue']['paid_orders_count'] }} paid</p>
                    </td>
                    <td class="kpi-cell">
                        <p class="kpi-label">Confirmed Bookings</p>
                        <p class="kpi-value">{{ number_format($report['bookings']['confirmed']) }}</p>
                        <p class="kpi-sub">{{ $report['bookings']['unique_participants'] }} unique trainees &middot; {{ $report['bookings']['total'] }} total</p>
                    </td>
                    <td class="kpi-cell">
                        <p class="kpi-label">Attendance Rate</p>
                        <p class="kpi-value">{{ number_format($report['attendance_summary']['rate_percent'], 1) }}%</p>
                        <p class="kpi-sub">{{ $report['attendance_summary']['present'] }} present &middot; {{ $report['attendance_summary']['verified'] }} verified</p>
                    </td>
                    <td class="kpi-cell">
                        <p class="kpi-label">Certificates Issued</p>
                        <p class="kpi-value">{{ number_format($report['certificates']['issued']) }}</p>
                        <p class="kpi-sub">{{ $report['certificates']['session_type'] }} session &middot; {{ $report['certificates']['module_type'] }} module</p>
                    </td>
                </tr>
            </table>
        @endif

        <!-- Financial & Operations Ledgers -->
        @if ($section === 'all' || $section === 'finance')
            <table class="two-col" cellspacing="0" cellpadding="0">
                <tbody>
                    <tr>
                        <td>
                            <div class="panel-box">
                                <div class="panel-header">Revenue &amp; Refunds Ledger</div>
                                <table class="ledger-table" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td class="key">Live Session Bookings</td>
                                        <td class="val">MYR {{ number_format($report['revenue']['session_myr'], 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="key">Standalone Module Purchases</td>
                                        <td class="val">MYR {{ number_format($report['revenue']['module_myr'], 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="key"><strong>Gross Revenue</strong></td>
                                        <td class="val">MYR {{ number_format($report['revenue']['gross_myr'], 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="key">Completed Refunds ({{ $report['refunds']['completed_count'] }})</td>
                                        <td class="val neg">- MYR {{ number_format($report['refunds']['completed_myr'], 2) }}</td>
                                    </tr>
                                    <tr class="total-row">
                                        <td>Net Revenue</td>
                                        <td class="val">MYR {{ number_format($report['revenue']['net_myr'], 2) }}</td>
                                    </tr>
                                </table>
                                <div class="panel-foot">
                                    Payments: {{ $report['revenue']['successful_payments'] }} paid &middot; {{ $report['revenue']['pending_payments'] }} pending &middot; {{ $report['revenue']['failed_payments'] }} failed
                                </div>
                            </div>
                        </td>
                        <td class="gap"></td>
                        <td>
                            <div class="panel-box">
                                <div class="panel-header">Operations &amp; Attendance Summary</div>
                                <table class="ledger-table" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td class="key">Registered Trainees</td>
                                        <td class="val">{{ number_format($report['customers']['total']) }} ({{ $report['customers']['active'] }} active, {{ $report['customers']['new_in_period'] }} new)</td>
                                    </tr>
                                    <tr>
                                        <td class="key">Training Sessions</td>
                                        <td class="val">{{ $report['catalogue']['total_sessions'] }} ({{ $report['catalogue']['completed_sessions'] }} completed, {{ $report['catalogue']['cancelled_sessions'] }} cancelled)</td>
                                    </tr>
                                    <tr>
                                        <td class="key">Training Modules</td>
                                        <td class="val">{{ $report['catalogue']['published_modules'] }} / {{ $report['catalogue']['total_modules'] }} published</td>
                                    </tr>
                                    <tr>
                                        <td class="key">Attendance Records</td>
                                        <td class="val">{{ $report['attendance_summary']['present'] }} present / {{ $report['attendance_summary']['total'] }} total ({{ $report['attendance_summary']['verified'] }} verified)</td>
                                    </tr>
                                    <tr>
                                        <td class="key">Pending Refunds</td>
                                        <td class="val">{{ $report['refunds']['pending_count'] }} (MYR {{ number_format($report['refunds']['pending_myr'], 2) }})</td>
                                    </tr>
                                </table>
                                <div class="panel-foot">
                                    Revenue excludes pending and failed payment attempts. All timestamps in MYT.
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        @endif

        <!-- 1. Sessions & Participant Lists -->
        @if ($show('sessions'))
            <p class="section-heading">Sessions &amp; Capacity Summary</p>
            <p class="section-sub">Scheduled sessions with venue, assigned instructors, confirmed seats, attendance, and certificates.</p>

            <table class="data-table" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th style="width: 30%;">Session</th>
                        <th style="width: 17%;">Schedule</th>
                        <th style="width: 17%;">Instructor(s)</th>
                        <th class="num" style="width: 9%;">Seats</th>
                        <th class="num" style="width: 9%;">Present</th>
                        <th class="num" style="width: 8%;">Certs</th>
                        <th style="width: 10%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['session_reports'] as $i => $sess)
                        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
                            <td>
                                <p class="row-title">{{ $sess['title'] }}</p>
                                <p class="row-sub">{{ $sess['session_code'] }} &middot; {{ $sess['venue'] }}</p>
                            </td>
                            <td>{{ $sess['starts_at_label'] }}</td>
                            <td>{{ $sess['instructors'] }}</td>
                            <td class="num">{{ $sess['confirmed_seats'] }} / {{ $sess['capacity'] }}</td>
                            <td class="num">{{ $sess['present_count'] }}</td>
                            <td class="num">{{ $sess['certificates_count'] }}</td>
                            <td><span class="status-chip {{ $chipFor($sess['status']) }}">{{ $sess['status'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-row">No training sessions found for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>

            @foreach ($report['session_reports'] as $sess)
                @if (!empty($sess['participants']))
                    <div class="sub-session-header">
                        Participant List: {{ $sess['session_code'] }} — {{ $sess['title'] }} ({{ count($sess['participants']) }} confirmed)
                    </div>
                    <table class="data-table" cellspacing="0" cellpadding="0">
                        <thead>
                            <tr>
                                <th style="width: 18%;">Booking Ref</th>
                                <th style="width: 34%;">Trainee</th>
                                <th style="width: 14%;">Confirmed</th>
                                <th style="width: 12%;">Attendance</th>
                                <th style="width: 12%;">Review</th>
                                <th style="width: 10%;">Certificate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sess['participants'] as $pi => $p)
                                <tr class="{{ $pi % 2 === 1 ? 'alt' : '' }}">
                                    <td style="font-weight: bold;">{{ $p['booking_reference'] }}</td>
                                    <td>
                                        <p class="row-title">{{ $p['trainee_name'] }}</p>
                                        <p class="row-sub">{{ $p['trainee_email'] }}</p>
                                    </td>
                                    <td>{{ $p['confirmed_at'] }}</td>
                                    <td><span class="status-chip {{ $chipFor($p['attendance_status']) }}">{{ $p['attendance_status'] }}</span></td>
                                    <td><span class="status-chip {{ $chipFor($p['review_status']) }}">{{ $p['review_status'] }}</span></td>
                                    <td>{{ $p['certificate_issued'] ? 'Issued' : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            @endforeach
        @endif

        <!-- 2. Bookings & Registrations -->
        @if ($show('bookings'))
            <p class="section-heading">Bookings &amp; Registrations Report</p>
            <p class="section-sub">Confirmed: {{ $report['bookings']['confirmed'] }} &middot; Pending: {{ $report['bookings']['pending'] }} &middot; Cancelled/Expired: {{ $report['bookings']['cancelled'] + $report['bookings']['expired'] }} &middot; Refunded: {{ $report['bookings']['refunded'] }}</p>

            <table class="data-table" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th style="width: 17%;">Booking Ref</th>
                        <th style="width: 25%;">Trainee</th>
                        <th style="width: 26%;">Session</th>
                        <th style="width: 12%;">Status</th>
                        <th style="width: 20%;">Confirmed / Reserved</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['booking_rows'] as $i => $b)
                        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
                            <td style="font-weight: bold;">{{ $b['reference'] }}</td>
                            <td>
                                <p class="row-title">{{ $b['trainee_name'] }}</p>
                                <p class="row-sub">{{ $b['trainee_email'] }}</p>
                            </td>
                            <td>
                                <p class="row-title">{{ $b['session_title'] }}</p>
                                <p class="row-sub">{{ $b['session_code'] }}</p>
                            </td>
                            <td><span class="status-chip {{ $chipFor($b['status']) }}">{{ $b['status'] }}</span></td>
                            <td>{{ $b['confirmed_at'] !== '—' ? $b['confirmed_at'] : $b['reserved_at'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-row">No bookings recorded for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        <!-- 3. Attendance Report -->
        @if ($show('attendance'))
            <p class="section-heading">Attendance Verification Report</p>
            <p class="section-sub">Total: {{ $report['attendance_summary']['total'] }} &middot; Present: {{ $report['attendance_summary']['present'] }} &middot; Absent: {{ $report['attendance_summary']['absent'] }} &middot; Verified: {{ $report['attendance_summary']['verified'] }} &middot; Attendance Rate: {{ number_format($report['attendance_summary']['rate_percent'], 1) }}%</p>

            <table class="data-table" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th style="width: 26%;">Session</th>
                        <th style="width: 26%;">Trainee</th>
                        <th style="width: 12%;">Attendance</th>
                        <th style="width: 14%;">Review Status</th>
                        <th style="width: 22%;">Recorded / Verified</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['attendance_rows'] as $i => $a)
                        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
                            <td>
                                <p class="row-title">{{ $a['session_title'] }}</p>
                                <p class="row-sub">{{ $a['session_code'] }}</p>
                            </td>
                            <td>
                                <p class="row-title">{{ $a['trainee_name'] }}</p>
                                <p class="row-sub">{{ $a['trainee_email'] }}</p>
                            </td>
                            <td><span class="status-chip {{ $chipFor($a['attendance_status']) }}">{{ $a['attendance_status'] }}</span></td>
                            <td><span class="status-chip {{ $chipFor($a['review_status']) }}">{{ $a['review_status'] }}</span></td>
                            <td>{{ $a['verified_at'] !== '—' ? $a['verified_at'] : $a['recorded_at'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-row">No attendance records found for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        <!-- 4. Payments & Refunds -->
        @if ($show('finance'))
            <p class="section-heading">Payments Ledger</p>
            <p class="section-sub">Payment transactions across Billplz, Paystack, and manual verification.</p>

            <table class="data-table" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th style="width: 18%;">Payment Ref</th>
                        <th style="width: 15%;">Order #</th>
                        <th style="width: 24%;">Customer</th>
                        <th style="width: 11%;">Gateway</th>
                        <th style="width: 10%;">Status</th>
                        <th style="width: 10%;">Date</th>
                        <th class="num" style="width: 12%;">Amount (MYR)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['payment_rows'] as $i => $p)
                        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
                            <td style="font-weight: bold;">{{ $p['payment_reference'] }}</td>
                            <td>{{ $p['order_number'] }}</td>
                            <td>
                                <p class="row-title">{{ $p['customer_name'] }}</p>
                                <p class="row-sub">{{ $p['order_type'] }}</p>
                            </td>
                            <td>{{ $p['gateway'] }}</td>
                            <td><span class="status-chip {{ $chipFor($p['status']) }}">{{ $p['status'] }}</span></td>
                            <td>{{ $p['timestamp'] }}</td>
                            <td class="num" style="font-weight: bold;">{{ number_format((float) $p['amount_myr'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-row">No payment transactions recorded for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <p class="section-heading">Refunds Ledger</p>
            <p class="section-sub">Completed: {{ $report['refunds']['completed_count'] }} (MYR {{ number_format($report['refunds']['completed_myr'], 2) }}) &middot; Pending: {{ $report['refunds']['pending_count'] }} &middot; Rejected: {{ $report['refunds']['rejected_count'] }}</p>

            <table class="data-table" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th style="width: 17%;">Refund Ref</th>
                        <th style="width: 14%;">Order #</th>
                        <th style="width: 22%;">Customer</th>
                        <th style="width: 13%;">Method</th>
                        <th style="width: 11%;">Status</th>
                        <th style="width: 11%;">Requested</th>
                        <th class="num" style="width: 12%;">Amount (MYR)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['refund_rows'] as $i => $r)
                        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
                            <td style="font-weight: bold;">{{ $r['refund_reference'] }}</td>
                            <td>{{ $r['order_number'] }}</td>
                            <td>
                                <p class="row-title">{{ $r['customer_name'] }}</p>
                                <p class="row-sub">{{ $r['customer_email'] }}</p>
                            </td>
                            <td>{{ $r['method'] }}</td>
                            <td><span class="status-chip {{ $chipFor($r['status']) }}">{{ $r['status'] }}</span></td>
                            <td>{{ $r['requested_at'] }}</td>
                            <td class="num" style="font-weight: bold;">{{ number_format((float) $r['amount_myr'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-row">No refunds recorded for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        @endif

        <!-- 5. Module Sales & Performance -->
        @if ($show('modules'))
            <p class="section-heading">Module Sales &amp; Performance</p>
            <p class="section-sub">Ranked by confirmed live session bookings and standalone module purchases.</p>

            <table class="data-table" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th style="width: 34%;">Module</th>
                        <th style="width: 15%;">Category</th>
                        <th style="width: 11%;">Status</th>
                        <th class="num" style="width: 9%;">Bookings</th>
                        <th class="num" style="width: 10%;">Standalone</th>
                        <th class="num" style="width: 8%;">Access</th>
                        <th class="num" style="width: 13%;">Revenue (MYR)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['popular_modules'] as $i => $mod)
                        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
                            <td>
                                <p class="row-title">{{ $mod['title'] }}</p>
                                <p class="row-sub">{{ $mod['code'] }}</p>
                            </td>
                            <td>{{ $mod['category'] }}</td>
                            <td><span class="status-chip {{ $chipFor($mod['status']) }}">{{ $mod['status'] }}</span></td>
                            <td class="num">{{ number_format($mod['confirmed_bookings']) }}</td>
                            <td class="num">{{ number_format($mod['independent_purchases']) }}</td>
                            <td class="num">{{ number_format($mod['active_entitlements']) }}</td>
                            <td class="num" style="font-weight: bold;">{{ number_format((float) $mod['revenue_myr'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-row">No module activity recorded for this period.</td></tr>
                    @endforelse
                </tbody>
                @if (!empty($report['popular_modules']))
                    <tfoot>
                        <tr>
                            <td colspan="3">Total ({{ count($report['popular_modules']) }} modules)</td>
                            <td class="num">{{ number_format(collect($report['popular_modules'])->sum('confirmed_bookings')) }}</td>
                            <td class="num">{{ number_format(collect($report['popular_modules'])->sum('independent_purchases')) }}</td>
                            <td class="num">{{ number_format(collect($report['popular_modules'])->sum('active_entitlements')) }}</td>
                            <td class="num">MYR {{ number_format((float) collect($report['popular_modules'])->sum('revenue_myr'), 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        @endif

        <!-- 6. Certificates & Instructor Activity -->
        @if ($show('certificates'))
            <p class="section-heading">Certificates Register</p>
            <p class="section-sub">Issued: {{ $report['certificates']['issued'] }} ({{ $report['certificates']['session_type'] }} session, {{ $report['certificates']['module_type'] }} module) &middot; Revoked: {{ $report['certificates']['revoked'] }}</p>

            <table class="data-table" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th style="width: 18%;">Certificate #</th>
                        <th style="width: 22%;">Trainee</th>
                        <th style="width: 10%;">Type</th>
                        <th style="width: 28%;">Training Programme</th>
                        <th style="width: 11%;">Issued</th>
                        <th style="width: 11%;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['certificate_rows'] as $i => $c)
                        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
                            <td style="font-weight: bold;">{{ $c['certificate_number'] }}</td>
                            <td>{{ $c['trainee_name'] }}</td>
                            <td>{{ $c['record_type'] }}</td>
                            <td>
                                <p class="row-title">{{ $c['training_title'] }}</p>
                                <p class="row-sub">{{ $c['training_code'] }}</p>
                            </td>
                            <td>{{ $c['issued_at'] }}</td>
                            <td><span class="status-chip {{ $chipFor($c['status']) }}">{{ $c['status'] }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-row">No certificates recorded for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>

            <p class="section-heading">Instructor Activity</p>
            <p class="section-sub">Assigned sessions, confirmed participants, and certificates issued per instructor.</p>

            <table class="data-table" cellspacing="0" cellpadding="0">
                <thead>
                    <tr>
                        <th style="width: 40%;">Instructor</th>
                        <th class="num" style="width: 12%;">Assigned</th>
                        <th class="num" style="width: 12%;">Completed</th>
                        <th class="num" style="width: 12%;">Upcoming</th>
                        <th class="num" style="width: 12%;">Participants</th>
                        <th class="num" style="width: 12%;">Certificates</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($report['instructor_stats'] as $i => $inst)
                        <tr class="{{ $i % 2 === 1 ? 'alt' : '' }}">
                            <td>
                                <p class="row-title">{{ $inst['name'] }}</p>
                                <p class="row-sub">{{ $inst['email'] }}</p>
                            </td>
                            <td class="num" style="font-weight: bold;">{{ number_format($inst['assigned_sessions']) }}</td>
                            <td class="num">{{ number_format($inst['completed_sessions']) }}</td>
                            <td class="num">{{ number_format($inst['upcoming_sessions']) }}</td>
                            <td class="num">{{ number_format($inst['confirmed_participants']) }}</td>
                            <td class="num" style="font-weight: bold;">{{ number_format($inst['certificates_issued']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-row">No instructors found.</td></tr>
                    @endforelse
                </tbody>
                @if (!empty($report['instructor_stats']))
                    <tfoot>
                        <tr>
                            <td>Total ({{ count($report['instructor_stats']) }} instructors)</td>
                            <td class="num">{{ number_format(collect($report['instructor_stats'])->sum('assigned_sessions')) }}</td>
                            <td class="num">{{ number_format(collect($report['instructor_stats'])->sum('completed_sessions')) }}</td>
                            <td class="num">{{ number_format(collect($report['instructor_stats'])->sum('upcoming_sessions')) }}</td>
                            <td class="num">{{ number_format(collect($report['instructor_stats'])->sum('confirmed_participants')) }}</td>
                            <td class="num">{{ number_format(collect($report['instructor_stats'])->sum('certificates_issued')) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        @endif

        <!-- Footer -->
        <div class="footer-block">
            <table class="footer-table" cellspacing="0" cellpadding="0">
                <tr>
                    <td style="width: 65%;">
                        <strong>Ecohydrotech Solutions Sdn. Bhd.</strong> &mdash; Confidential Internal Report
                    </td>
                    <td style="width: 35%;" class="right">
                        Generated {{ $generatedAtLabel }} &middot; Period: {{ $periodLabel }}
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
