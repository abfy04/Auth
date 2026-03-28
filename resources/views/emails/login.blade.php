@component('mail::message')
# New Login Detected

We noticed a new login to your account.

**Details:**
- Location: {{ $location }}
- Device: {{ $user_agent }}
- Time: {{ $time }}

If this was you, no further action is needed.

If this wasn't you, please secure your account immediately.
Thanks,<br>
{{ config('app.name') }}
@endcomponent