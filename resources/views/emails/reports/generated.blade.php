@component('mail::layout')
{{-- Header --}}
@slot('header')
@component('mail::header', ['url' => config('app.url')])
{{ config('app.name') }}
@endcomponent
@endslot

# {{ $report->name }} Report is Ready

Your report has been successfully generated and is attached to this email.

**Report Details:**  
**Generated On:** {{ now()->format('F j, Y g:i A') }}  
**Time Range:** {{ $run->started_at->format('F j, Y') }} to {{ $run->completed_at->format('F j, Y') }}

@if(!empty($schedule))
This report was generated as part of a scheduled report. You can manage your report subscriptions in the admin panel.
@endif

@component('mail::button', ['url' => $downloadUrl ?? '#' ])
Download Report
@endcomponent

Thanks,  
{{ config('app.name') }}

{{-- Footer --}}
@slot('footer')
@component('mail::footer')
© {{ date('Y') }} {{ config('app.name') }}. All rights reserved.

If you have any questions, please contact support.
@endcomponent
@endslot
@endcomponent
