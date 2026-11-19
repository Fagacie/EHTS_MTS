<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject ?? config('etms.brand_name') }}</title>
</head>
<body style="margin:0;padding:0;background-color:#f1f5f9;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color:#f1f5f9;padding:28px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" border="0" style="max-width:600px;width:100%;background-color:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0;">
                    <!-- Ecohydrotech Brand Header -->
                    <tr>
                        <td style="background-color:#082f49;padding:24px 32px;border-bottom:4px solid #0D9488;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <p style="margin:0;font-size:18px;font-weight:700;color:#ffffff;letter-spacing:0.02em;">
                                            Ecohydrotech Solution
                                        </p>
                                        <p style="margin:4px 0 0 0;font-size:11px;font-weight:600;color:#99f6e4;text-transform:uppercase;letter-spacing:0.12em;">
                                            Training Management System
                                        </p>
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <span style="display:inline-block;padding:5px 10px;border-radius:999px;background-color:rgba(13,148,136,0.22);color:#ccfbf1;font-size:11px;font-weight:700;">
                                            Official Notice
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body Slot -->
                    <tr>
                        <td style="padding:32px;">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8fafc;padding:20px 32px;border-top:1px solid #e2e8f0;">
                            <p style="margin:0;font-size:12px;line-height:1.5;color:#64748b;">
                                Automated notification from <strong>{{ config('etms.brand_name') }}</strong>. All dates and times are displayed in Malaysia Standard Time (MYT, Asia/Kuala_Lumpur).
                            </p>
                            <p style="margin:6px 0 0 0;font-size:11px;color:#94a3b8;">
                                Support: <a href="mailto:{{ config('etms.support_email') }}" style="color:#0D9488;text-decoration:none;">{{ config('etms.support_email') }}</a>
                                &middot; <a href="{{ config('app.url') }}" style="color:#0F4C81;text-decoration:none;">{{ config('app.url') }}</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
