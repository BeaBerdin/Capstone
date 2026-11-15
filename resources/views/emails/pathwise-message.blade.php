{{--
    PATHWISE email (HTML part), same design as the verification email.

    $heading      Title in the card.
    $name         Recipient name, or null.
    $paragraphs   Array of plain-text paragraphs (escaped).
    $highlight    Optional ['label' => ..., 'text' => ...] box (e.g. a rejection reason).
    $buttonText   Optional button label; $buttonUrl its link.
    $note         Optional small line under the button (e.g. link expiry).
    $preheader    Inbox preview text.
--}}
@php
    $logoPath = public_path('images/pathwise-logo-full.png');
    $logoSrc = isset($message) && is_file($logoPath)
        ? $message->embed($logoPath)
        : asset('images/pathwise-logo-full.png');
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>{{ $heading }}</title>
    <style>
        body { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table { border-collapse: collapse; }
        img { border: 0; outline: none; text-decoration: none; -ms-interpolation-mode: bicubic; }
        a { color: #6d28d9; }
        @media only screen and (max-width: 600px) {
            .pw-outer { padding: 24px 12px !important; }
            .pw-card { padding: 32px 22px !important; }
            .pw-heading { font-size: 22px !important; line-height: 30px !important; }
            .pw-button-table { width: 100% !important; }
            .pw-button { display: block !important; text-align: center !important; }
        }
    </style>
</head>
<body style="margin:0; padding:0; background-color:#f6f5fb;">
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#f6f5fb; opacity:0;">
        {{ $preheader ?? $heading }}
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f6f5fb;">
        <tr>
            <td align="center" class="pw-outer" style="padding:40px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;">
                    <tr>
                        <td align="center" style="padding:0 0 24px 0;">
                            <img src="{{ $logoSrc }}" width="140" alt="PathWise" style="display:block; width:140px; max-width:140px; height:auto;">
                        </td>
                    </tr>

                    <tr>
                        <td class="pw-card" style="background-color:#ffffff; border:1px solid #ebe7f7; border-top:4px solid #6d28d9; border-radius:12px; padding:40px 40px 36px 40px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#1f2937;">
                            <h1 class="pw-heading" style="margin:0 0 20px 0; font-size:24px; line-height:32px; font-weight:700; color:#1f1147;">
                                {{ $heading }}
                            </h1>

                            @if (filled($name ?? null))
                                <p style="margin:0 0 16px 0; font-size:15px; line-height:24px; color:#374151;">
                                    Hi {{ $name }},
                                </p>
                            @endif

                            @foreach ($paragraphs as $paragraph)
                                <p style="margin:0 0 16px 0; font-size:15px; line-height:24px; color:#374151;">
                                    {{ $paragraph }}
                                </p>
                            @endforeach

                            @if (! empty($highlight))
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin:8px 0 20px 0;">
                                    <tr>
                                        <td style="background-color:#faf5ff; border:1px solid #e9d5ff; border-radius:10px; padding:16px 18px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
                                            <p style="margin:0 0 6px 0; font-size:12px; line-height:18px; font-weight:700; letter-spacing:0.06em; text-transform:uppercase; color:#6d28d9;">
                                                {{ $highlight['label'] }}
                                            </p>
                                            <p style="margin:0; font-size:15px; line-height:24px; color:#1f2937; white-space:pre-line;">{{ $highlight['text'] }}</p>
                                        </td>
                                    </tr>
                                </table>
                            @endif

                            @if (filled($buttonText ?? null) && filled($buttonUrl ?? null))
                                <table role="presentation" class="pw-button-table" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:12px auto 0 auto;">
                                    <tr>
                                        <td align="center" bgcolor="#6d28d9" style="border-radius:8px; background-color:#6d28d9;">
                                            <a href="{{ $buttonUrl }}" target="_blank" rel="noopener" class="pw-button"
                                               style="display:inline-block; padding:14px 32px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:15px; line-height:20px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:8px; border:1px solid #6d28d9;">
                                                {{ $buttonText }}
                                            </a>
                                        </td>
                                    </tr>
                                </table>

                                @if (filled($note ?? null))
                                    <p style="margin:20px 0 0 0; font-size:13px; line-height:20px; color:#6b7280; text-align:center;">
                                        {{ $note }}
                                    </p>
                                @endif

                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:28px;">
                                    <tr>
                                        <td style="border-top:1px solid #ede9fe; padding-top:20px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:13px; line-height:20px; color:#6b7280;">
                                            If the button doesn't work, copy and paste this link into your browser:
                                            <br>
                                            <a href="{{ $buttonUrl }}" target="_blank" rel="noopener" style="color:#6d28d9; word-break:break-all; text-decoration:underline;">{{ $buttonUrl }}</a>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:24px 16px 0 16px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:12px; line-height:18px; color:#8b85a3;">
                            <p style="margin:0; color:#6d28d9; font-weight:700; letter-spacing:0.04em;">
                                PathWise
                            </p>
                            <p style="margin:2px 0 0 0;">
                                &copy; {{ date('Y') }} PathWise. All rights reserved.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
