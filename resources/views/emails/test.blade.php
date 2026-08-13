@component('mail::message')
# {{ __('admin/settings.mail.test_heading_mail') }}

{{ __('admin/settings.mail.test_body', ['site' => $siteName]) }}

@component('mail::panel')
{{ __('admin/settings.mail.test_sent_at') }}: **{{ $sentAt }}**
@endcomponent

{{ __('admin/settings.mail.test_footer') }}

{{ $siteName }}
@endcomponent
