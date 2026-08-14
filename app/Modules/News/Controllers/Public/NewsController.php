<?php

namespace App\Modules\News\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Modules\News\Models\NewsCategory;
use App\Modules\News\Models\NewsPost;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NewsController extends Controller
{
    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $query = NewsPost::published()->whereHas('translations', fn ($q) => $q->where('locale', $locale))
            ->with(['translations', 'category', 'author'])
            ->when($request->filled('category'), fn ($q) => $q->whereHas('category', fn ($category) => $category->where('slug', $request->string('category'))))
            ->when($request->filled('q'), function ($q) use ($request, $locale) {
                $term = '%'.str_replace(['%', '_'], ['\\%', '\\_'], (string) $request->string('q')).'%';
                $q->whereHas('translations', fn ($translation) => $translation
                    ->where('locale', $locale)
                    ->where(fn ($fields) => $fields->where('title', 'like', $term)->orWhere('excerpt', 'like', $term)));
            });

        return view('public.news.index', [
            'posts' => $query->latest('published_at')->paginate(9)->withQueryString(),
            'featured' => NewsPost::published()->whereHas('translations', fn ($q) => $q->where('locale', $locale))
                ->with(['translations', 'category'])->where('is_featured', true)->latest('published_at')->first(),
            'categories' => NewsCategory::active()->withCount(['posts' => fn ($q) => $q->published()
                ->whereHas('translations', fn ($translation) => $translation->where('locale', $locale))])->get(),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $post = NewsPost::published()->with(['translations', 'category', 'author'])
            ->whereHas('translations', fn ($query) => $query->where('locale', app()->getLocale())->where('slug', $slug))
            ->first();

        if (! $post) {
            throw new NotFoundHttpException;
        }

        $request->route()->setParameter('slug', $post);

        $key = "viewed_news_{$post->id}";
        if (! $request->session()->has($key)) {
            $request->session()->put($key, true);
            $post->incrementQuietly('views_count');
        }

        $related = NewsPost::published()->with(['translations', 'category'])
            ->whereKeyNot($post->id)
            ->whereHas('translations', fn ($query) => $query->where('locale', app()->getLocale()))
            ->when($post->category_id, fn ($q) => $q->where('category_id', $post->category_id))
            ->latest('published_at')->limit(3)->get();

        return view('public.news.show', compact('post', 'related'));
    }
}
