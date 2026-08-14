<?php

use App\Enums\NewsStatus;
use App\Modules\News\Models\NewsCategory;
use App\Modules\News\Models\NewsPost;

function newsData(array $overrides = []): array
{
    return array_merge([
        'status' => NewsStatus::Draft->value,
        'title_es' => 'Guia para comprar vivienda',
        'excerpt_es' => 'Pasos esenciales antes de comprar.',
        'content_es' => '<h2>Inicio</h2><p>Contenido seguro.</p>',
        'title_en' => 'Home buying guide',
        'content_en' => '<p>Safe content.</p>',
    ], $overrides);
}

it('protege la administracion de noticias', function () {
    $this->get('/admin/noticias')->assertRedirect(route('admin.login'));
});

it('niega la administracion a usuarios sin permiso', function () {
    $agent = userWithRole('agent');

    $this->actingAs($agent)->get(route('admin.news.posts.index'))->assertForbidden();
    $this->actingAs($agent)->post(route('admin.news.posts.store'), newsData())->assertForbidden();
    $this->actingAs($agent)->get(route('admin.news.categories.index'))->assertForbidden();
    $this->actingAs($agent)->post(route('admin.news.categories.store'), [])->assertForbidden();
});

it('crea y actualiza categorias bilingues', function () {
    $admin = userWithRole('admin');
    $this->actingAs($admin)->post(route('admin.news.categories.store'), [
        'name_es' => 'Mercado', 'name_en' => 'Market', 'slug' => 'mercado',
        'color' => '#0058BE', 'is_active' => '1', 'sort_order' => 1,
    ])->assertRedirect();

    $category = NewsCategory::firstOrFail();
    expect($category->getTranslation('name', 'en'))->toBe('Market')->and($category->is_active)->toBeTrue();

    $this->actingAs($admin)->put(route('admin.news.categories.update', $category), [
        'name_es' => 'Guias', 'name_en' => 'Guides', 'slug' => 'guias',
        'color' => '#7C3AED', 'is_active' => '1', 'sort_order' => 2,
    ])->assertRedirect();
    expect($category->fresh()->slug)->toBe('guias');
});

it('crea una noticia bilingue y sanea html peligroso', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->post(route('admin.news.posts.store'), newsData([
        'content_es' => '<p onclick="alert(1)">Texto</p><script>alert(1)</script>',
    ]))->assertRedirect();

    $post = NewsPost::with('translations')->firstOrFail();
    $content = $post->translations->firstWhere('locale', 'es')->content;
    expect($content)->toContain('Texto')->not->toContain('<script')->not->toContain('onclick');
    expect($post->translations)->toHaveCount(2);
});

it('publica inmediatamente si no se indica fecha', function () {
    $admin = userWithRole('admin');
    $this->actingAs($admin)->post(route('admin.news.posts.store'), newsData([
        'status' => NewsStatus::Published->value,
    ]));

    $post = NewsPost::firstOrFail();
    expect($post->published_at)->not->toBeNull()->and($post->isPublished())->toBeTrue();
});

it('exige fecha para una publicacion programada', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->post(route('admin.news.posts.store'), newsData([
        'status' => NewsStatus::Scheduled->value,
    ]))->assertSessionHasErrors('published_at');
});

it('elimina una traduccion opcional cuando se vacia', function () {
    $admin = userWithRole('admin');
    $post = NewsPost::factory()->translated('Titulo espanol', 'English title')->create();

    $this->actingAs($admin)->put(route('admin.news.posts.update', $post), newsData([
        'title_en' => null,
        'content_en' => null,
    ]))->assertRedirect();

    expect($post->translations()->where('locale', 'en')->exists())->toBeFalse();
});

it('lista y permite editar una noticia', function () {
    $admin = userWithRole('admin');
    $post = NewsPost::factory()->translated('Titulo administrable', 'Editable title')->create();

    $this->actingAs($admin)->get(route('admin.news.posts.index'))->assertOk()->assertSee('Titulo administrable');
    $this->actingAs($admin)->put(route('admin.news.posts.update', $post), newsData(['title_es' => 'Titulo actualizado']))->assertRedirect();
    expect($post->fresh('translations')->title)->toBe('Titulo actualizado');
});

it('conserva visible la categoria inactiva asignada al editar', function () {
    $admin = userWithRole('admin');
    $category = NewsCategory::create([
        'name' => ['es' => 'Anterior', 'en' => 'Previous'],
        'slug' => 'anterior',
        'is_active' => false,
    ]);
    $post = NewsPost::factory()->translated('Con categoria', 'Categorized')->create(['category_id' => $category->id]);

    $this->actingAs($admin)->get(route('admin.news.posts.edit', $post))->assertOk()->assertSee('Anterior');
});
