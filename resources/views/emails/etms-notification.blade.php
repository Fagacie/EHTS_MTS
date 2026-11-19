@extends('emails.layout')

@section('content')
    @if (! empty($badge))
        <p style="margin:0 0 10px 0;">
            <span style="display:inline-block;padding:4px 10px;border-radius:6px;background-color:#f0fdfa;color:#0f766e;border:1px solid #99f6e4;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;">
                {{ $badge }}
            </span>
        </p>
    @endif

    <h1 style="margin:0 0 14px 0;font-size:21px;line-height:1.3;font-weight:700;color:#0F4C81;">
        {{ $heading }}
    </h1>

    @if (! empty($greeting))
        <p style="margin:0 0 14px 0;font-size:14px;line-height:1.6;color:#334155;">
            {{ $greeting }}
        </p>
    @endif

    @foreach (($introLines ?? []) as $line)
        <p style="margin:0 0 12px 0;font-size:14px;line-height:1.6;color:#334155;">
            {{ $line }}
        </p>
    @endforeach

    @if (! empty($details) && is_array($details))
        <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin:18px 0;border:1px solid #e2e8f0;border-radius:10px;background-color:#f8fafc;">
            @foreach ($details as $label => $value)
                <tr>
                    <td style="padding:10px 14px;font-size:12px;font-weight:700;color:#475569;border-bottom:1px solid #e2e8f0;width:40%;">
                        {{ $label }}
                    </td>
                    <td style="padding:10px 14px;font-size:13px;font-weight:600;color:#0f172a;border-bottom:1px solid #e2e8f0;">
                        {{ $value }}
                    </td>
                </tr>
            @endforeach
        </table>
    @endif

    @if (! empty($actionUrl) && ! empty($actionLabel))
        <p style="margin:24px 0;">
            <a href="{{ $actionUrl }}" style="display:inline-block;padding:12px 24px;border-radius:10px;background-color:#0F4C81;color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;">
                {{ $actionLabel }}
            </a>
        </p>
    @endif

    @foreach (($outroLines ?? []) as $line)
        <p style="margin:10px 0 0 0;font-size:13px;line-height:1.6;color:#64748b;">
            {{ $line }}
        </p>
    @endforeach
@endsection
