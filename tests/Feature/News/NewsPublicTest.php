<?php

use App\Enums\NewsStatus;
use App\Modules\News\Models\NewsCategory;
use App\Modules\News\Models\NewsPost;
use Database\Seeders\SettingsSeeder;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
});

function publicNews(string $es, string $en, array $attributes = []): NewsPost
{
    return NewsPost::factory()->published()->translated($es, $en)->create($attributes)->fresh(['translations', 'category', 'author']);
}

it('muestra el listado en ambos idiomas', function () {
    publicNews('Mercado inmobiliario dominicano', 'Dominican real estate market');

    $this->get('/informate')->assertOk()->assertSee('Mercado inmobiliario dominicano');
    $this->get('/en/insights')->assertOk()->assertSee('Dominican real estate market');
});

it('oculta borradores y publicaciones futuras', function () {
    publicNews('Visible ahora', 'Visible now');
    NewsPost::factory()->translated('Borrador oculto', 'Hidden draft')->create();
    NewsPost::factory()->translated('Futura oculta', 'Hidden future')->create([
        'status' => NewsStatus::Published,
        'published_at' => now()->addDay(),
    ]);

    $this->get('/informate')->assertSee('Visible ahora')->assertDontSee('Borrador oculto')->assertDontSee('Futura oculta');
});

it('publica automaticamente una noticia programada cuando llega su fecha', function () {
    $post = NewsPost::factory()->translated('Programada visible', 'Visible schedule')->create([
        'status' => NewsStatus::Scheduled,
        'published_at' => now()->subMinute(),
    ]);

    expect($post->isPublished())->toBeTrue();
    $this->get('/informate')->assertOk()->assertSee('Programada visible');
});

it('resuelve el detalle por slug traducido', function () {
    $post = publicNews('Guia de inversion', 'Investment guide');
    $slugEs = $post->translations->firstWhere('locale', 'es')->slug;
    $slugEn = $post->translations->firstWhere('locale', 'en')->slug;

    $this->get('/informate/'.$slugEs)->assertOk()->assertSee('Guia de inversion');
    $this->get('/en/insights/'.$slugEn)->assertOk()->assertSee('Investment guide');
});

it('rechaza un slug perteneciente a otro idioma', function () {
    $post = publicNews('Ruta espanola', 'English route');
    $slugEs = $post->translations->firstWhere('locale', 'es')->slug;

    $this->get('/en/insights/'.$slugEs)->assertNotFound();
});

it('genera el enlace alternativo con el slug traducido', function () {
    $post = publicNews('Enlace espanol', 'English link');
    $slugEs = $post->translations->firstWhere('locale', 'es')->slug;
    $slugEn = $post->translations->firstWhere('locale', 'en')->slug;

    $this->get('/informate/'.$slugEs)->assertOk()->assertSee('href="'.url('/en/insights/'.$slugEn).'"', false);
});

it('no expone un borrador por su slug', function () {
    $post = NewsPost::factory()->translated('Privada', 'Private')->create();
    $slug = $post->fresh('translations')->translations->firstWhere('locale', 'es')->slug;

    $this->get('/informate/'.$slug)->assertNotFound();
});

it('filtra por categoria y busqueda', function () {
    $market = NewsCategory::create(['name' => ['es' => 'Mercado', 'en' => 'Market'], 'slug' => 'mercado', 'is_active' => true]);
    publicNews('Informe de precios', 'Price report', ['category_id' => $market->id]);
    publicNews('Consejos legales', 'Legal advice');

    $this->get('/informate?category=mercado')->assertSee('Informe de precios')->assertDontSee('Consejos legales');
    $this->get('/informate?q=precios')->assertSee('Informe de precios')->assertDontSee('Consejos legales');
    $this->get('/informate?q=Price')->assertDontSee('Informe de precios');
});

it('deduplica el contador de vistas por sesion', function () {
    $post = publicNews('Noticia visitada', 'Viewed article');
    $slug = $post->translations->firstWhere('locale', 'es')->slug;

    $this->get('/informate/'.$slug)->assertOk();
    $this->get('/informate/'.$slug)->assertOk();

    expect($post->fresh()->views_count)->toBe(1);
});

it('incluye json ld de articulo', function () {
    $post = publicNews('Noticia estructurada', 'Structured article');
    $slug = $post->translations->firstWhere('locale', 'es')->slug;

    $this->get('/informate/'.$slug)->assertSee('"@type":"Article"', false);
});

it('escapa metadatos y json ld para impedir xss almacenado', function () {
    $post = publicNews('Titulo </title><script>alert(1)</script>', 'Safe English');
    $translation = $post->translations->firstWhere('locale', 'es');
    $translation->update([
        'meta_title' => '</title><script>alert(2)</script>',
        'meta_description' => '"><script>alert(3)</script>',
    ]);

    $response = $this->get('/informate/'.$translation->slug)->assertOk();
    $response->assertDontSee('<script>alert', false);
    $response->assertSee('\\u003C\\/script\\u003E', false);
});
