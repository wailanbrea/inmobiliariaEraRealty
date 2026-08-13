@extends('admin.settings.partials.layout')

@section('settings')

<form method="POST" action="{{ route('admin.settings.mail.update') }}" class="space-y-md"
      x-data="{ enviarPrueba: false }">
    @csrf @method('PUT')

    <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
        <h2 class="mb-base font-heading text-title-lg text-on-surface">
            {{ __('admin/settings.mail.heading') }}
        </h2>
        <p class="mb-sm text-caption text-on-surface-variant">
            {{ __('admin/settings.mail.intro') }}
        </p>

        <div class="grid gap-sm md:grid-cols-2">
            <x-admin.field name="mail_mailer" :label="__('admin/settings.mail.mailer')"
                           type="select" :value="$values['mail_mailer']" required
                           :options="['smtp' => 'SMTP', 'sendmail' => 'Sendmail', 'log' => 'Log (solo pruebas)']" />

            <x-admin.field name="mail_host" :label="__('admin/settings.mail.host')"
                           :value="$values['mail_host']" placeholder="smtp.gmail.com" />

            <x-admin.field name="mail_port" :label="__('admin/settings.mail.port')"
                           type="number" :value="$values['mail_port']" placeholder="587" />

            <x-admin.field name="mail_encryption" :label="__('admin/settings.mail.encryption')"
                           type="select" :value="$values['mail_encryption']"
                           :options="['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'Ninguna']" />

            <x-admin.field name="mail_username" :label="__('admin/settings.mail.username')"
                           :value="$values['mail_username']" autocomplete="off" />

            {{-- La contrasena nunca vuelve al formulario. Vacia = conservar. --}}
            <x-admin.field name="mail_password" :label="__('admin/settings.mail.password')"
                           type="password" autocomplete="new-password"
                           :placeholder="$hasPassword ? '••••••••' : ''"
                           :help="$hasPassword
                               ? __('admin/settings.mail.password_set') . ' ' . __('admin/settings.mail.password_help')
                               : __('admin/settings.mail.password_help')" />

            <x-admin.field name="mail_from_address" :label="__('admin/settings.mail.from_address')"
                           type="email" :value="$values['mail_from_address']" required />

            <x-admin.field name="mail_from_name" :label="__('admin/settings.mail.from_name')"
                           :value="$values['mail_from_name']" required />
        </div>

        <div class="mt-sm">
            <x-admin.field name="mail_send_client_confirmation" type="checkbox"
                           :label="__('admin/settings.mail.send_client_confirmation')"
                           :value="$values['mail_send_client_confirmation']"
                           :help="__('admin/settings.mail.send_client_confirmation')" />
        </div>
    </section>

    {{-- Prueba de envio --}}
    <section class="rounded-xl border border-secondary/30 bg-secondary-fixed/30 p-md">
        <h2 class="mb-base flex items-center gap-xs font-heading text-title-lg text-on-surface">
            <span class="material-symbols-outlined text-[24px] text-secondary">outgoing_mail</span>
            {{ __('admin/settings.mail.test_heading') }}
        </h2>
        <p class="mb-sm text-body-md text-on-surface-variant">
            {{ __('admin/settings.mail.test_intro') }}
        </p>

        <div class="flex flex-col gap-sm sm:flex-row sm:items-end">
            <div class="flex-1">
                <x-admin.field name="test_recipient" :label="__('admin/settings.mail.test_recipient')"
                               type="email" :value="auth()->user()->email" />
            </div>

            <button type="submit" name="send_test" value="1"
                    class="flex items-center justify-center gap-xs rounded-lg bg-secondary px-md py-xs
                           text-label-md font-semibold text-on-secondary shadow-sm
                           transition-all hover:shadow-ambient-hover">
                <span class="material-symbols-outlined text-[20px]">send</span>
                {{ __('admin/settings.mail.test_button') }}
            </button>
        </div>
    </section>

    <div class="sticky bottom-0 flex justify-end border-t border-outline-variant/40
                bg-surface/95 py-sm backdrop-blur">
        <button type="submit"
                class="flex items-center gap-xs rounded-lg bg-primary-container px-md py-xs
                       text-label-md font-semibold text-on-primary shadow-sm
                       transition-all hover:shadow-ambient-hover">
            <span class="material-symbols-outlined text-[20px]">save</span>
            {{ __('admin/settings.save') }}
        </button>
    </div>
</form>

@endsection
