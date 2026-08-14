<x-mail::message>
# {{ __('leads.confirmation.heading', ['name' => $lead->name]) }}

{{ __('leads.confirmation.body') }}

@if($lead->message)
<x-mail::panel>
{{ $lead->message }}
</x-mail::panel>
@endif

{{ __('leads.confirmation.closing') }},<br>
{{ setting('site_name', config('app.name')) }}
</x-mail::message>
