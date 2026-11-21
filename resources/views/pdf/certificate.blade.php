<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Certificate {{ $certificate->certificate_number }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 14mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #0f172a;
            background-color: #ffffff;
        }
        .outer-frame {
            width: 100%;
            border: 5px solid #0F4C81;
            padding: 6px;
            background-color: #ffffff;
        }
        .inner-frame {
            border: 2px solid #0D9488;
            padding: 24px 34px;
            background-color: #fafcfb;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .org-name {
            font-size: 16px;
            font-weight: 700;
            color: #0F4C81;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin: 0;
        }
        .org-sub {
            font-size: 10px;
            color: #0D9488;
            letter-spacing: 0.14em;
            text-transform: uppercase;
            margin: 3px 0 0 0;
        }
        .ref-badge {
            text-align: right;
            font-size: 10px;
            color: #475569;
        }
        .ref-number {
            display: block;
            margin-top: 3px;
            font-size: 13px;
            font-weight: 700;
            color: #0F4C81;
        }
        .center-body {
            text-align: center;
            padding: 10px 18px 18px 18px;
        }
        .cert-label {
            font-size: 11px;
            font-weight: 700;
            color: #0D9488;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            margin: 0;
        }
        .cert-title {
            font-size: 30px;
            font-weight: 700;
            color: #082f49;
            margin: 6px 0 14px 0;
            letter-spacing: 0.03em;
        }
        .lead-copy {
            font-size: 12px;
            color: #475569;
            margin: 0 0 8px 0;
        }
        .trainee-name {
            font-size: 26px;
            font-weight: 700;
            color: #0F4C81;
            margin: 4px auto 12px auto;
            padding-bottom: 8px;
            border-bottom: 2px solid #cbd5e1;
            width: 78%;
        }
        .program-title {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            margin: 8px auto 6px auto;
            width: 86%;
            line-height: 1.35;
        }
        .program-meta {
            font-size: 11px;
            color: #0D9488;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin: 0 0 18px 0;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 22px;
            border-top: 1px solid #cbd5e1;
            padding-top: 14px;
        }
        .footer-cell {
            width: 33.33%;
            vertical-align: bottom;
            padding-top: 14px;
        }
        .meta-label {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #64748b;
            margin: 0 0 3px 0;
        }
        .meta-value {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        .signatory-box {
            text-align: right;
        }
        .signature-rule {
            display: inline-block;
            width: 190px;
            border-top: 1.5px solid #0F4C81;
            padding-top: 6px;
        }
        .signatory-name {
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        .signatory-title {
            font-size: 10px;
            color: #475569;
            margin: 2px 0 0 0;
        }
    </style>
</head>
<body>
    <div class="outer-frame">
        <div class="inner-frame">
            <table class="header-table">
                <tr>
                    <td style="width: 60px; vertical-align: middle;">
                        @if (! empty($logoDataUri))
                            <img src="{{ $logoDataUri }}" alt="Organisation Logo" style="width: 48px; height: 48px;">
                        @endif
                    </td>
                    <td style="vertical-align: middle;">
                        <p class="org-name">{{ $certificate->issuing_organisation_snapshot }}</p>
                        <p class="org-sub">Hydrological &amp; Environmental Technical Training</p>
                    </td>
                    <td class="ref-badge" style="vertical-align: middle;">
                        <span>Certificate Reference Number</span>
                        <span class="ref-number">{{ $certificate->certificate_number }}</span>
                    </td>
                </tr>
            </table>

            <div class="center-body">
                <p class="cert-label">Official Recognition</p>
                <h1 class="cert-title">Certificate of Completion</h1>

                <p class="lead-copy">This is to certify that</p>
                <div class="trainee-name">{{ $certificate->trainee_name_snapshot }}</div>

                <p class="lead-copy">has successfully completed the training programme</p>
                <div class="program-title">{{ $certificate->training_title_snapshot }}</div>
                <p class="program-meta">
                    {{ $certificate->recordTypeLabel() }}
                    @if ($certificate->training_code_snapshot)
                        &middot; {{ $certificate->training_code_snapshot }}
                    @endif
                </p>
            </div>

            <table class="footer-table">
                <tr>
                    <td class="footer-cell">
                        <p class="meta-label">Completion Date</p>
                        <p class="meta-value">{{ $certificate->completion_date?->format('d F Y') }}</p>
                    </td>
                    <td class="footer-cell" style="text-align: center;">
                        <p class="meta-label">Date of Issue</p>
                        <p class="meta-value">
                            {{ $certificate->issued_at?->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))->format('d F Y') }}
                        </p>
                    </td>
                    <td class="footer-cell signatory-box">
                        <div class="signature-rule">
                            @if(! empty($signatureDataUri))
                                <img src="{{ $signatureDataUri }}" alt="Authorised signature" style="display:block; max-height:36px; max-width:160px; margin:0 auto 4px;">
                            @endif
                            <p class="signatory-name">{{ $certificate->signatory_name_snapshot }}</p>
                            <p class="signatory-title">{{ $certificate->signatory_title_snapshot }}</p>
                        </div>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</body>
</html>
