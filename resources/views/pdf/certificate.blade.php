<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Certificate {{ $certificate->certificate_number }}</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0;
        }
        * {
            box-sizing: border-box;
        }
        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            font-family: Georgia, "Times New Roman", DejaVu Serif, serif;
            color: #0b2a5b;
            background-color: #fbf8ef;
        }
        .page-wrap {
            width: 100%;
            height: 100%;
            padding: 18px;
            background-color: #0b2a5b;
        }
        .gold-outer {
            width: 100%;
            height: 100%;
            border: 2px solid #c89d46;
            padding: 6px;
            background-color: #fbf8ef;
        }
        .inner-canvas {
            position: relative;
            width: 100%;
            height: 100%;
            border: 1px solid #d7c7a3;
            background-color: #fdfbf4;
            overflow: hidden;
        }
        .corner-tl {
            position: absolute;
            top: 0;
            left: 0;
            width: 190px;
            height: 190px;
        }
        .corner-br {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 190px;
            height: 190px;
        }
        .watermark-org {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            margin-top: -18px;
            text-align: center;
            z-index: 1;
        }
        .watermark-org-text {
            font-family: Georgia, "Times New Roman", DejaVu Serif, serif;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: 0.24em;
            text-transform: uppercase;
            color: #0b2a5b;
            opacity: 0.04;
            margin: 0;
            line-height: 1.1;
        }
        .content-table {
            position: relative;
            z-index: 2;
            width: 100%;
            height: 100%;
            border-collapse: collapse;
        }
        .content-cell {
            padding: 28px 60px 26px 60px;
            vertical-align: middle;
            text-align: center;
        }
        .logo-wrap {
            margin-bottom: 10px;
        }
        .logo-wrap img {
            height: 92px;
            width: auto;
        }
        .cert-main {
            font-size: 40px;
            font-weight: 700;
            letter-spacing: 0.18em;
            color: #0b2a5b;
            text-transform: uppercase;
            margin: 0;
            line-height: 1.05;
        }
        .cert-sub-table {
            margin: 8px auto 0 auto;
            border-collapse: collapse;
        }
        .cert-sub-text {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.34em;
            color: #0f62a4;
            text-transform: uppercase;
            padding: 0 14px;
            white-space: nowrap;
        }
        .hairline-gold {
            width: 72px;
            height: 1.5px;
            background-color: #c89d46;
            font-size: 0;
            line-height: 0;
        }
        .presented-copy {
            font-size: 13.5px;
            font-style: italic;
            color: #4b5d73;
            margin: 20px 0 8px 0;
        }
        .recipient-name {
            font-size: 34px;
            font-weight: 700;
            color: #0b2a5b;
            margin: 0 auto 12px auto;
            padding-bottom: 10px;
            border-bottom: 1.5px solid #c89d46;
            width: 74%;
            letter-spacing: 0.01em;
        }
        .completion-copy {
            font-size: 13.5px;
            font-style: italic;
            color: #4b5d73;
            margin: 0 0 8px 0;
        }
        .programme-title {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 20px;
            font-weight: 700;
            color: #0f62a4;
            margin: 0 auto 6px auto;
            width: 82%;
            line-height: 1.35;
        }
        .programme-meta {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 10.5px;
            font-weight: 700;
            letter-spacing: 0.16em;
            color: #2d8a3e;
            text-transform: uppercase;
            margin: 0;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        .footer-left {
            width: 36%;
            vertical-align: bottom;
            text-align: left;
        }
        .footer-center {
            width: 28%;
            vertical-align: bottom;
            text-align: center;
        }
        .footer-right {
            width: 36%;
            vertical-align: bottom;
            text-align: right;
        }
        .sig-rule-left {
            width: 220px;
            border-top: 1.5px solid #0b2a5b;
            padding-top: 6px;
        }
        .sig-rule-right {
            display: inline-block;
            width: 245px;
            text-align: center;
        }
        .sig-img-box {
            height: 44px;
            text-align: center;
            margin-bottom: 4px;
        }
        .sig-img-box img {
            max-height: 42px;
            max-width: 175px;
            display: inline-block;
            vertical-align: bottom;
        }
        .sig-line {
            border-top: 1.5px solid #0b2a5b;
            padding-top: 6px;
        }
        .meta-value {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12.5px;
            font-weight: 700;
            color: #0b2a5b;
            margin: 0 0 3px 0;
        }
        .meta-label {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 0.13em;
            text-transform: uppercase;
            color: #64748b;
            margin: 0;
        }
        .signatory-title {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9.5px;
            color: #55657a;
            margin: 2px 0 0 0;
        }
        .cert-ref {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 9.5px;
            color: #64748b;
            margin: 7px 0 0 0;
            letter-spacing: 0.04em;
        }
        .cert-ref strong {
            color: #0b2a5b;
        }
    </style>
</head>
<body>
    <div class="page-wrap">
        <div class="gold-outer">
            <div class="inner-canvas">
                <!-- Architectural Top-Left Corner Ribbon -->
                <svg class="corner-tl" viewBox="0 0 190 190" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="0,0 160,0 0,160" fill="#0b2a5b" />
                    <polygon points="0,0 124,0 0,124" fill="#0f62a4" opacity="0.55" />
                    <polygon points="154,0 170,0 0,170 0,154" fill="#2d8a3e" />
                    <polygon points="172,0 177,0 0,177 0,172" fill="#c89d46" />
                </svg>

                <!-- Architectural Bottom-Right Corner Ribbon -->
                <svg class="corner-br" viewBox="0 0 190 190" xmlns="http://www.w3.org/2000/svg">
                    <polygon points="190,190 30,190 190,30" fill="#0b2a5b" />
                    <polygon points="190,190 66,190 190,66" fill="#0f62a4" opacity="0.55" />
                    <polygon points="36,190 20,190 190,20 190,36" fill="#2d8a3e" />
                    <polygon points="18,190 13,190 190,13 190,18" fill="#c89d46" />
                </svg>

                <!-- Subtle Organisation Name Watermark -->
                <div class="watermark-org">
                    <p class="watermark-org-text">{{ $certificate->issuing_organisation_snapshot }}</p>
                </div>

                <table class="content-table">
                    <tr>
                        <td class="content-cell">
                            <div class="logo-wrap">
                                @if (! empty($logoDataUri))
                                    <img src="{{ $logoDataUri }}" alt="{{ $certificate->issuing_organisation_snapshot }}">
                                @else
                                    <strong style="font-size: 18px; color: #0b2a5b;">{{ $certificate->issuing_organisation_snapshot }}</strong>
                                @endif
                            </div>

                            <h1 class="cert-main">Certificate</h1>

                            <table class="cert-sub-table">
                                <tr>
                                    <td style="vertical-align: middle;"><div class="hairline-gold"></div></td>
                                    <td class="cert-sub-text">of Completion</td>
                                    <td style="vertical-align: middle;"><div class="hairline-gold"></div></td>
                                </tr>
                            </table>

                            <p class="presented-copy">This is proudly presented to</p>
                            <div class="recipient-name">{{ $certificate->trainee_name_snapshot }}</div>

                            <p class="completion-copy">for successfully completing the professional training programme</p>
                            <div class="programme-title">{{ $certificate->training_title_snapshot }}</div>
                            <p class="programme-meta">
                                {{ $certificate->recordTypeLabel() }}
                                @if ($certificate->training_code_snapshot)
                                    &nbsp;&middot;&nbsp;{{ $certificate->training_code_snapshot }}
                                @endif
                            </p>

                            <table class="footer-table">
                                <tr>
                                    <td class="footer-left">
                                        <p class="meta-value">{{ $certificate->completion_date?->format('d F Y') }}</p>
                                        <div class="sig-rule-left">
                                            <p class="meta-label">Date of Completion</p>
                                        </div>
                                        <p class="cert-ref">
                                            Certificate No: <strong>{{ $certificate->certificate_number }}</strong>
                                        </p>
                                    </td>

                                    <td class="footer-center">
                                        <svg width="92" height="104" viewBox="0 0 92 104" xmlns="http://www.w3.org/2000/svg">
                                            <polygon points="31,64 20,101 34,92 43,101 45,68" fill="#0b2a5b" />
                                            <polygon points="61,64 72,101 58,92 49,101 47,68" fill="#2d8a3e" />
                                            <polygon points="46,4 52,11 61,8 64,17 73,17 73,26 81,30 78,39 84,46 78,53 81,62 73,66 73,75 64,75 61,84 52,81 46,88 40,81 31,84 28,75 19,75 19,66 11,62 14,53 8,46 14,39 11,30 19,26 19,17 28,17 31,8 40,11" fill="#c89d46" />
                                            <circle cx="46" cy="46" r="29" fill="#fdfbf4" stroke="#0b2a5b" stroke-width="1.8" />
                                            <circle cx="46" cy="46" r="24" fill="none" stroke="#c89d46" stroke-width="1" stroke-dasharray="2 2" />
                                            <text x="46" y="40" text-anchor="middle" font-family="DejaVu Sans, Arial, sans-serif" font-size="7.5" font-weight="700" fill="#2d8a3e" letter-spacing="1.4">VERIFIED</text>
                                            <text x="46" y="51" text-anchor="middle" font-family="Georgia, serif" font-size="9.5" font-weight="700" fill="#0b2a5b">OFFICIAL</text>
                                            <text x="46" y="60" text-anchor="middle" font-family="DejaVu Sans, Arial, sans-serif" font-size="6.5" font-weight="700" fill="#b4832c" letter-spacing="1.2">SEAL</text>
                                        </svg>
                                    </td>

                                    <td class="footer-right">
                                        <div class="sig-rule-right">
                                            <div class="sig-img-box">
                                                @if (! empty($signatureDataUri))
                                                    <img src="{{ $signatureDataUri }}" alt="Authorised signature">
                                                @endif
                                            </div>
                                            <div class="sig-line">
                                                <p class="meta-value">{{ $certificate->signatory_name_snapshot }}</p>
                                                <p class="signatory-title">{{ $certificate->signatory_title_snapshot }}</p>
                                            </div>
                                            <p class="cert-ref">
                                                Issued {{ $certificate->issued_at?->timezone(config('etms.display_timezone', 'Asia/Kuala_Lumpur'))->format('d M Y') }}
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
