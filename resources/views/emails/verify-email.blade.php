{{--
    PATHWISE email verification message (HTML part).

    Built with tables and inline styles so it renders the same in Gmail on
    desktop and mobile. $url is Laravel's signed verification link; it is
    never rebuilt here. When the message is sent, the logo is attached to
    the email itself (inline image) so it shows even though the app may run
    on a local address; in a preview it falls back to the public file.
--}}
@php
    $logoPath = public_path('images/pathwise-logo-full.png');
    $logoSrc = isset($message) && is_file($logoPath)
        ? $message->embed($logoPath)
        : asset('images/pathwise-logo-full.png');
    $expires = (int) config('auth.verification.expire', 60);
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <title>Welcome to PathWise! Please Verify Your Email</title>
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
    {{-- Inbox preview text --}}
    <div style="display:none; max-height:0; overflow:hidden; mso-hide:all; font-size:1px; line-height:1px; color:#f6f5fb; opacity:0;">
        Verify your email address to activate your PathWise account.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f6f5fb;">
        <tr>
            <td align="center" class="pw-outer" style="padding:40px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:560px;">
                    {{-- Logo --}}
                    <tr>
                        <td align="center" style="padding:0 0 24px 0;">
                            <img src="{{ $logoSrc }}" width="140" alt="PathWise" style="display:block; width:140px; max-width:140px; height:auto;">
                        </td>
                    </tr>

                    {{-- Card --}}
                    <tr>
                        <td class="pw-card" style="background-color:#ffffff; border:1px solid #ebe7f7; border-top:4px solid #6d28d9; border-radius:12px; padding:40px 40px 36px 40px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#1f2937;">
                            <h1 class="pw-heading" style="margin:0 0 20px 0; font-size:24px; line-height:32px; font-weight:700; color:#1f1147;">
                                Welcome to PathWise!
                            </h1>

                            @if (filled($name ?? null))
                                <p style="margin:0 0 16px 0; font-size:15px; line-height:24px; color:#374151;">
                                    Hi {{ $name }},
                                </p>
                            @endif

                            <p style="margin:0 0 16px 0; font-size:15px; line-height:24px; color:#374151;">
                                Thank you for creating your PathWise account! We're excited to have you join our learning community.
                            </p>

                            <p style="margin:0 0 28px 0; font-size:15px; line-height:24px; color:#374151;">
                                To get started, please verify your email address by clicking the button below. Your account will be activated once your email is verified.
                            </p>

                            {{-- Button (bulletproof: still a coloured, clickable button without CSS support) --}}
                            <table role="presentation" class="pw-button-table" cellpadding="0" cellspacing="0" border="0" align="center" style="margin:0 auto;">
                                <tr>
                                    <td align="center" bgcolor="#6d28d9" style="border-radius:8px; background-color:#6d28d9;">
                                        <a href="{{ $url }}" target="_blank" rel="noopener" class="pw-button"
                                           style="display:inline-block; padding:14px 32px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:15px; line-height:20px; font-weight:600; color:#ffffff; text-decoration:none; border-radius:8px; border:1px solid #6d28d9;">
                                            Verify Email Address
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:24px 0 0 0; font-size:13px; line-height:20px; color:#6b7280; text-align:center;">
                                This link expires in {{ $expires }} minutes.
                            </p>

                            {{-- Fallback link --}}
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin-top:28px;">
                                <tr>
                                    <td style="border-top:1px solid #ede9fe; padding-top:20px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:13px; line-height:20px; color:#6b7280;">
                                        If the button doesn't work, copy and paste this link into your browser:
                                        <br>
                                        <a href="{{ $url }}" target="_blank" rel="noopener" style="color:#6d28d9; word-break:break-all; text-decoration:underline;">{{ $url }}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Footer --}}
                    <tr>
                        <td align="center" style="padding:24px 16px 0 16px; font-family:'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size:12px; line-height:18px; color:#8b85a3;">
                            <p style="margin:0 0 10px 0;">
                                If you didn't create a PathWise account, you can safely ignore this email.
                            </p>
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
