@component('mail::message')
# OTP Verification

Your OTP code is:

**{{ $otp }}**

It is valid for 10 minutes.

Thanks,<br>
{{ config('app.name') }}
@endcomponent