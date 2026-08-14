@extends('layouts.public')
@php $translation = $post->translated(); @endphp
@section('title')
{{ ($translation?->meta_title ?: $post->title).' · '.setting('site_name') }}
@endsection
@section('description')
{{ $translation?->meta_description ?: $post->excerpt ?: __('news.description') }}
@endsection
@push('head')
<script type="application/ld+json">{!! json_encode(['@'.'context' => 'https://schema.org', '@type' => 'Article', 'headline' => $post->title, 'datePublished' => $post->published_at?->toIso8601String(), 'author' => ['@type' => 'Person', 'name' => $post->author->name]], JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
@endpush
@section('content')
<article>
    <header class="bg-primary-container py-xl"><div class="mx-auto max-w-4xl px-margin-mobile md:px-gutter"><nav class="mb-md text-caption text-on-primary-container"><a href="{{ lroute('home') }}">{{ __('common.nav.home') }}</a> / <a href="{{ lroute('news.index') }}">{{ __('news.title') }}</a></nav><div class="mb-sm flex flex-wrap gap-xs text-caption text-on-secondary-container"><span>{{ $post->category?->name }}</span><span>{{ __('news.published', ['date' => $post->published_at->format('d/m/Y')]) }}</span><span>{{ __('news.minutes', ['count' => $post->reading_time]) }}</span></div><h1 class="font-display text-display-lg-mobile text-on-primary md:text-display-lg">{{ $post->title }}</h1><p class="mt-sm text-body-lg text-on-primary/85">{{ $post->excerpt }}</p></div></header>
    @if($post->featured_image)<div class="mx-auto -mt-md max-w-5xl px-margin-mobile md:px-gutter"><img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}" class="max-h-[560px] w-full rounded-xl object-cover ambient-shadow"></div>@endif
    <div class="mx-auto max-w-3xl px-margin-mobile py-xl text-body-lg leading-8 text-on-surface md:px-gutter [&_a]:text-secondary [&_h2]:mb-sm [&_h2]:mt-lg [&_h2]:font-heading [&_h2]:text-headline-md [&_h3]:mb-xs [&_h3]:mt-md [&_h3]:text-title-lg [&_li]:mb-xs [&_ol]:ml-md [&_ol]:list-decimal [&_p]:mb-md [&_ul]:ml-md [&_ul]:list-disc">{!! $post->content !!}</div>
</article>
@if($related->isNotEmpty())<section class="bg-surface-container-low py-xl"><div class="mx-auto max-w-container-max px-margin-mobile md:px-gutter"><h2 class="mb-md font-heading text-headline-md">{{ __('news.related') }}</h2><div class="grid gap-gutter md:grid-cols-3">@foreach($related as $item)<article class="rounded-xl bg-surface-container-lowest p-md ambient-shadow"><p class="text-caption text-secondary">{{ $item->category?->name }}</p><h3 class="mt-xs text-title-lg"><a href="{{ lroute('news.show', ['slug' => $item->slug]) }}">{{ $item->title }}</a></h3><p class="mt-xs text-body-md text-on-surface-variant">{{ $item->excerpt }}</p></article>@endforeach</div></div></section>@endif
@endsection
