@component('mail::message')
# You're Approved 🎉

Hello {{ $business_name }},

Your provider account has been approved.

You can now access all features and start offering your services.

We wish you success,<br>
{{ config('app.name') }}
@endcomponent