<x-mail::message>
# {{ __('leads.mail.heading') }}

**{{ __('leads.fields.name') }}:** {{ $lead->name }}
**{{ __('leads.fields.phone') }}:** {{ $lead->phone }}
**{{ __('leads.fields.email') }}:** {{ $lead->email ?: '-' }}
**{{ __('leads.fields.source') }}:** {{ $lead->source->value }}

{{ $lead->message }}

{{ config('app.name') }}
</x-mail::message>
