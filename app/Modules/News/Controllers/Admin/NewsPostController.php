<?php

namespace App\Modules\News\Controllers\Admin;

use App\Enums\NewsStatus;
use App\Http\Controllers\Controller;
use App\Modules\News\Models\NewsCategory;
use App\Modules\News\Models\NewsPost;
use App\Modules\News\Requests\NewsPostRequest;
use App\Modules\News\Services\NewsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsPostController extends Controller
{
    public function index(Request $request): View
    {
        $posts = NewsPost::query()->with(['translations', 'category', 'author'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', (string) $request->string('status')))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.news.posts.index', ['posts' => $posts, 'statuses' => NewsStatus::cases()]);
    }

    public function create(): View
    {
        return $this->form(new NewsPost);
    }

    public function store(NewsPostRequest $request, NewsService $service): RedirectResponse
    {
        $post = $service->create($request->validated());

        return redirect()->route('admin.news.posts.edit', $post)->with('success', __('admin/news.saved'));
    }

    public function edit(NewsPost $post): View
    {
        return $this->form($post->load('translations'));
    }

    public function update(NewsPostRequest $request, NewsPost $post, NewsService $service): RedirectResponse
    {
        $service->update($post, $request->validated());

        return back()->with('success', __('admin/news.saved'));
    }

    public function destroy(NewsPost $post): RedirectResponse
    {
        $post->delete();

        return redirect()->route('admin.news.posts.index')->with('success', __('admin/news.deleted'));
    }

    private function form(NewsPost $post): View
    {
        return view('admin.news.posts.form', [
            'post' => $post,
            'categories' => NewsCategory::query()->active()
                ->when($post->category_id, fn ($query) => $query->orWhere('id', $post->category_id))
                ->get(),
            'statuses' => NewsStatus::cases(),
        ]);
    }
}
