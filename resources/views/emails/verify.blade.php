<!-- resources/views/emails/verify.blade.php -->

@component('mail::message')
# Verify Email Address

Please click the button below to verify your email address.

@component('mail::button', ['url' => $url])
Verify Email Address
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
