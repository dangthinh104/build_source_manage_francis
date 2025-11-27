@props(['siteName', 'status', 'timestamp', 'mailContent'])

@component('mail::message')
# Build Notification for {{ $siteName }}

## Build Status: {{ ucfirst($status) }}
## Time: {{ $timestamp }}

### Log Output:
<pre>{{ $mailContent }}</pre>

Best regards,<br>
{{ config('app.name') }}
@endcomponent
