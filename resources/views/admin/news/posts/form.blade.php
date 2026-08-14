@extends('admin.layouts.app')
@section('title', $post->exists ? __('admin/news.edit') : __('admin/news.new'))
@section('content')
@php $action = $post->exists ? route('admin.news.posts.update', $post) : route('admin.news.posts.store'); @endphp
<a href="{{ route('admin.news.posts.index') }}" class="mb-md inline-flex min-h-11 items-center text-secondary">{{ __('admin/news.actions.back') }}</a>
@if(session('success'))<div role="status" class="mb-md rounded-lg bg-tertiary-fixed p-sm">{{ session('success') }}</div>@endif
@if($errors->any())<div role="alert" class="mb-md rounded-lg bg-error-container p-sm text-on-error-container">{{ $errors->first() }}</div>@endif
<form method="POST" action="{{ $action }}" class="space-y-md">@csrf @if($post->exists) @method('PUT') @endif
    <section class="grid gap-sm rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow md:grid-cols-2 lg:grid-cols-4">
        <label class="grid gap-1"><span>{{ __('admin/news.fields.category') }}</span><select name="category_id" class="min-h-11 rounded-lg border px-xs"><option value="">-</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) old('category_id', $post->category_id) === (string) $category->id)>{{ $category->name }}</option>@endforeach</select></label>
        <label class="grid gap-1"><span>{{ __('admin/news.fields.status') }}</span><select name="status" class="min-h-11 rounded-lg border px-xs">@foreach($statuses as $status)<option value="{{ $status->value }}" @selected(old('status', $post->status?->value ?? 'draft') === $status->value)>{{ $status->value }}</option>@endforeach</select></label>
        <label class="grid gap-1"><span>{{ __('admin/news.fields.published_at') }}</span><input type="datetime-local" name="published_at" value="{{ old('published_at', $post->published_at?->format('Y-m-d\TH:i')) }}" class="min-h-11 rounded-lg border px-xs"></label>
        <label class="flex items-center gap-xs pt-md"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $post->is_featured))> {{ __('admin/news.fields.featured') }}</label>
        <label class="grid gap-1 md:col-span-2 lg:col-span-4"><span>{{ __('admin/news.fields.image') }}</span><input name="featured_image" value="{{ old('featured_image', $post->featured_image) }}" class="min-h-11 rounded-lg border px-xs"></label>
    </section>
    @foreach(['es' => 'Espanol', 'en' => 'English'] as $locale => $label)
        @php $translation = $post->translations->firstWhere('locale', $locale); @endphp
        <section class="space-y-sm rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
            <h2 class="text-title-lg">{{ $label }}</h2>
            <div class="grid gap-sm md:grid-cols-2"><label class="grid gap-1"><span>{{ __('admin/news.fields.title') }} {{ strtoupper($locale) }}</span><input name="title_{{ $locale }}" value="{{ old('title_'.$locale, $translation?->title) }}" @required($locale === 'es') class="min-h-11 rounded-lg border px-xs"></label><label class="grid gap-1"><span>{{ __('admin/news.fields.slug') }}</span><input name="slug_{{ $locale }}" value="{{ old('slug_'.$locale, $translation?->slug) }}" class="min-h-11 rounded-lg border px-xs"></label></div>
            <label class="grid gap-1"><span>{{ __('admin/news.fields.excerpt') }}</span><textarea name="excerpt_{{ $locale }}" rows="2" class="rounded-lg border px-xs py-xs">{{ old('excerpt_'.$locale, $translation?->excerpt) }}</textarea></label>
            <label class="grid gap-1"><span>{{ __('admin/news.fields.content') }}</span><textarea name="content_{{ $locale }}" rows="14" @required($locale === 'es') class="rounded-lg border px-xs py-xs font-mono text-body-md">{{ old('content_'.$locale, $translation?->content) }}</textarea></label>
            <div class="grid gap-sm md:grid-cols-2"><label class="grid gap-1"><span>{{ __('admin/news.fields.seo_title') }}</span><input name="meta_title_{{ $locale }}" value="{{ old('meta_title_'.$locale, $translation?->meta_title) }}" class="min-h-11 rounded-lg border px-xs"></label><label class="grid gap-1"><span>{{ __('admin/news.fields.seo_description') }}</span><input name="meta_description_{{ $locale }}" value="{{ old('meta_description_'.$locale, $translation?->meta_description) }}" class="min-h-11 rounded-lg border px-xs"></label></div>
        </section>
    @endforeach
    <button class="w-full rounded-lg bg-primary-container px-md py-sm font-semibold text-on-primary">{{ __('admin/news.actions.save') }}</button>
</form>
@if($post->exists)<form method="POST" action="{{ route('admin.news.posts.destroy', $post) }}" class="mt-md">@csrf @method('DELETE')<button class="rounded-lg border border-error px-md py-sm text-error">{{ __('admin/news.actions.delete') }}</button></form>@endif
@endsection
