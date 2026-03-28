@component('mail::message')
# Provider Account Update

Hello {{ $business_name }},

Unfortunately, your provider account has been suspended or not approved at this time.

If you believe this is an error or would like more details, please contact support.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
