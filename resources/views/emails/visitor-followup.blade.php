@component('mail::message')
# Thanks for Visiting {{ config('app.name') }}

Dear {{ $visitor->first_name }},

Thank you for joining us at {{ config('app.name') }}! {{ $message ?? 'We hope you enjoyed your time with us.' }}

## Next Steps

We'd love to help you get connected:

- Learn more about our ministries and programs
- Join us for our next service
- Connect with a small group
- Let us know how we can pray for you

@component('mail::button', ['url' => route('visitor.connect', ['id' => $visitor->id])])
Get Connected
@endcomponent

If you have any questions or would like more information, please don't hesitate to reach out to us at {{ config('mail.from.address') }} or by replying to this email.

Blessings,  
The {{ config('app.name') }} Team

@component('mail::subcopy')
You're receiving this email because you visited {{ config('app.name') }}. If you'd prefer not to receive these emails, you can [unsubscribe here]({{ route('visitor.unsubscribe', ['id' => $visitor->id]) }}).
@endcomponent
@endcomponent
