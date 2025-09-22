@component('mail::message')
# Welcome to {{ config('app.name') }}

Dear {{ $member->first_name }},

We're thrilled to welcome you to our church family! We're grateful you've chosen to be part of our community.

## Getting Started

Here are a few things you can do next:

- Complete your profile in our member portal
- Explore our upcoming events and services
- Connect with a small group or ministry
- Let us know how we can pray for you

@component('mail::button', ['url' => route('member.dashboard')])
Access Your Member Portal
@endcomponent

If you have any questions or need assistance, please don't hesitate to reach out to us at {{ config('mail.from.address') }} or by replying to this email.

Blessings,  
The {{ config('app.name') }} Team

@component('mail::subcopy')
You're receiving this email because you've recently registered as a member of {{ config('app.name') }}. If this was a mistake, please contact us.
@endcomponent
@endcomponent
