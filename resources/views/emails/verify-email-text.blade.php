Welcome to PathWise!

@if (filled($name ?? null))
Hi {{ $name }},

@endif
Thank you for creating your PathWise account! We're excited to have you join our learning community.

To get started, please verify your email address by opening the link below. Your account will be activated once your email is verified.

Verify Email Address:
{!! $url !!}

This link expires in {{ (int) config('auth.verification.expire', 60) }} minutes.

If you didn't create a PathWise account, you can safely ignore this email.

-- PathWise
