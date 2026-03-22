@component('mail::message')
# Welcome {{ $account->email }}

Thank you for registering as a provider on our platform.

**Business Name:** {{ $provider->business_name }}  
**City:** {{ $provider->city }}

Your account is currently **pending approval**. You will receive an email once an admin approves your account.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
