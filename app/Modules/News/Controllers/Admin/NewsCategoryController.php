<?php

namespace App\Modules\News\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\News\Models\NewsCategory;
use App\Modules\News\Requests\NewsCategoryRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NewsCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.news.categories.index', ['categories' => NewsCategory::withCount('posts')->orderBy('sort_order')->get()]);
    }

    public function store(NewsCategoryRequest $request): RedirectResponse
    {
        NewsCategory::create($this->data($request));

        return back()->with('success', __('admin/news.saved'));
    }

    public function update(NewsCategoryRequest $request, NewsCategory $category): RedirectResponse
    {
        $category->update($this->data($request));

        return back()->with('success', __('admin/news.saved'));
    }

    public function destroy(NewsCategory $category): RedirectResponse
    {
        $category->delete();

        return back()->with('success', __('admin/news.deleted'));
    }

    private function data(NewsCategoryRequest $request): array
    {
        return [
            'name' => ['es' => $request->string('name_es')->toString(), 'en' => $request->string('name_en')->toString()],
            'slug' => $request->string('slug')->toString(),
            'description' => ['es' => $request->input('description_es'), 'en' => $request->input('description_en')],
            'color' => $request->input('color'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->integer('sort_order'),
        ];
    }
}
