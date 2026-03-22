@component('mail::message')
# Hello {{ $account->email }}

Please verify your email address by clicking the button below:

@component('mail::button', ['url' => $verificationUrl])
Verify Email
@endcomponent

If you did not create an account, no further action is required.

Thanks,<br>
{{ config('app.name') }}
@endcomponent