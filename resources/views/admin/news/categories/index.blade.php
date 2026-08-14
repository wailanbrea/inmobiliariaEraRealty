@extends('admin.layouts.app')
@section('title', __('admin/news.categories'))
@section('content')
@if(session('success'))<div role="status" class="mb-md rounded-lg bg-tertiary-fixed p-sm">{{ session('success') }}</div>@endif
<div class="grid gap-md lg:grid-cols-3">
    <section class="rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-md ambient-shadow">
        <h2 class="mb-sm text-title-lg">Nueva categoria</h2>
        <form method="POST" action="{{ route('admin.news.categories.store') }}" class="space-y-xs">@csrf
            <input name="name_es" required placeholder="Nombre ES" class="min-h-11 w-full rounded-lg border border-outline-variant px-xs">
            <input name="name_en" placeholder="Name EN" class="min-h-11 w-full rounded-lg border border-outline-variant px-xs">
            <input name="slug" required placeholder="slug" class="min-h-11 w-full rounded-lg border border-outline-variant px-xs">
            <div class="grid grid-cols-2 gap-xs"><input type="color" name="color" value="#0058BE" class="min-h-11 w-full rounded-lg border"><input type="number" name="sort_order" value="0" min="0" class="min-h-11 rounded-lg border border-outline-variant px-xs"></div>
            <label class="flex items-center gap-xs"><input type="checkbox" name="is_active" value="1" checked> Activa</label>
            <button class="w-full rounded-lg bg-primary-container px-sm py-sm text-on-primary">{{ __('admin/news.actions.save') }}</button>
        </form>
    </section>
    <section class="space-y-sm lg:col-span-2">
        @foreach($categories as $category)
            <form method="POST" action="{{ route('admin.news.categories.update', $category) }}" class="grid gap-xs rounded-xl border border-outline-variant/40 bg-surface-container-lowest p-sm md:grid-cols-6">@csrf @method('PUT')
                <input name="name_es" value="{{ $category->getTranslation('name', 'es') }}" required class="min-h-11 rounded-lg border px-xs md:col-span-2"><input name="name_en" value="{{ $category->getTranslation('name', 'en') }}" class="min-h-11 rounded-lg border px-xs md:col-span-2"><input name="slug" value="{{ $category->slug }}" required class="min-h-11 rounded-lg border px-xs"><input type="color" name="color" value="{{ $category->color ?: '#0058BE' }}" class="min-h-11 rounded-lg border">
                <input type="hidden" name="sort_order" value="{{ $category->sort_order }}"><label class="flex items-center gap-xs md:col-span-2"><input type="checkbox" name="is_active" value="1" @checked($category->is_active)> Activa ? {{ $category->posts_count }} posts</label>
                <button class="rounded-lg border border-secondary px-sm text-secondary md:col-span-2">{{ __('admin/news.actions.save') }}</button>
                <button form="delete-category-{{ $category->id }}" class="rounded-lg border border-error px-sm text-error md:col-span-2">{{ __('admin/news.actions.delete') }}</button>
            </form>
            <form id="delete-category-{{ $category->id }}" method="POST" action="{{ route('admin.news.categories.destroy', $category) }}" class="hidden">@csrf @method('DELETE')</form>
        @endforeach
    </section>
</div>
@endsection
