@component('mail::message')
# {{ $type === 'register' ? 'Welcome!' : 'Login Request' }}

{{ $type === 'register'
    ? 'Thank you for registering! Click the button below to complete your registration and sign in to your account.'
    : 'You requested a login link. Click the button below to sign in to your account.' }}

@component('mail::button', ['url' => $magicLinkUrl])
{{ $type === 'register' ? 'Complete Registration' : 'Sign In' }}
@endcomponent

If the button doesn't work, copy and paste this link into your browser:

{{ $magicLinkUrl }}

This link will expire in 72 hours. If you didn't request this {{ $type === 'register' ? 'registration' : 'login' }}, please ignore this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
