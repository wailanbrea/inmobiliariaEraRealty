@extends('admin.layouts.guest')
@section('title', __('admin/auth.forgot.title'))
@section('content')
<main class="flex min-h-full items-center justify-center bg-surface px-margin-mobile py-xl">
    <div class="w-full max-w-md rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow md:p-lg">
        <span class="material-symbols-outlined mb-sm text-[36px] text-secondary">lock_reset</span>
        <h1 class="font-heading text-headline-md text-on-surface">{{ __('admin/auth.forgot.title') }}</h1>
        <p class="mt-xs text-body-md text-on-surface-variant">{{ __('admin/auth.forgot.intro') }}</p>
        @if(session('status'))<div role="status" class="mt-md rounded-lg bg-tertiary-fixed p-sm">{{ session('status') }}</div>@endif
        @if($errors->any())<div role="alert" class="mt-md rounded-lg bg-error-container p-sm text-on-error-container">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('admin.password.email') }}" class="mt-md space-y-sm">
            @csrf
            <label class="grid gap-1 text-label-md"><span>Correo electronico</span><input type="email" name="email" required autofocus value="{{ old('email') }}" class="min-h-11 rounded-lg border border-outline-variant bg-surface px-sm"></label>
            <button class="w-full rounded-lg bg-primary-container px-md py-sm text-label-md font-semibold text-on-primary">{{ __('admin/auth.forgot.send') }}</button>
        </form>
        <a href="{{ route('admin.login') }}" class="mt-md inline-flex min-h-11 items-center text-label-md text-secondary">{{ __('admin/auth.back') }}</a>
    </div>
</main>
@endsection
