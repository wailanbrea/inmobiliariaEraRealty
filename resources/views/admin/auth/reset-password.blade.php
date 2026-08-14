@extends('admin.layouts.guest')
@section('title', __('admin/auth.reset.title'))
@section('content')
<main class="flex min-h-full items-center justify-center bg-surface px-margin-mobile py-xl">
    <div class="w-full max-w-md rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow md:p-lg">
        <h1 class="font-heading text-headline-md text-on-surface">{{ __('admin/auth.reset.title') }}</h1>
        @if($errors->any())<div role="alert" class="mt-md rounded-lg bg-error-container p-sm text-on-error-container">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('admin.password.update') }}" class="mt-md space-y-sm">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <label class="grid gap-1 text-label-md"><span>Correo electronico</span><input type="email" name="email" required value="{{ old('email', $email) }}" class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm"></label>
            <label class="grid gap-1 text-label-md"><span>{{ __('admin/auth.reset.password') }}</span><input type="password" name="password" required autocomplete="new-password" class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm"></label>
            <label class="grid gap-1 text-label-md"><span>{{ __('admin/auth.reset.confirmation') }}</span><input type="password" name="password_confirmation" required autocomplete="new-password" class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm"></label>
            <button class="w-full rounded-lg bg-primary-container px-md py-sm text-label-md font-semibold text-on-primary">{{ __('admin/auth.reset.submit') }}</button>
        </form>
    </div>
</main>
@endsection
