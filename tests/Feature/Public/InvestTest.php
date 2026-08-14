<?php

use App\Modules\Leads\Models\Lead;
use App\Modules\Pages\Models\ContentSection;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Services\PropertyService;
use App\Modules\PropertyTypes\Models\PropertyType;
use App\Modules\Settings\Services\SettingsService;
use Database\Seeders\ContentSectionSeeder;
use Database\Seeders\PropertyTypeSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Crypt;

beforeEach(function () {
    $this->seed(SettingsSeeder::class);
    $this->seed(PropertyTypeSeeder::class);
    $this->seed(ContentSectionSeeder::class);
    app(SettingsService::class)->flush();
});

function propiedadInversion(string $titulo, bool $esInversion = true): Property
{
    $property = Property::factory()->published()->create([
        'property_type_id' => PropertyType::first()->id,
        'is_investment' => $esInversion,
    ]);

    app(PropertyService::class)->syncTranslations($property, [
        'es' => ['title' => $titulo],
        'en' => ['title' => $titulo.' EN'],
    ]);

    return $property->fresh(['translations']);
}

/*
|--------------------------------------------------------------------------
| Pagina
|--------------------------------------------------------------------------
*/

it('muestra la pagina de inversion', function () {
    $this->get('/invierte')
        ->assertOk()
        ->assertSee('Invierte en República Dominicana', escape: false);
});

it('existe en ingles con su segmento traducido', function () {
    $this->get('/en/invest')
        ->assertOk()
        ->assertSee('Invest in the Dominican Republic', escape: false);
});

it('muestra los cuatro motivos para invertir', function () {
    $respuesta = $this->get('/invierte');

    $respuesta->assertSee('Sin restricciones para extranjeros', escape: false);
    $respuesta->assertSee('Incentivos fiscales', escape: false);
});

it('traduce los motivos al ingles', function () {
    $this->get('/en/invest')->assertSee('No restrictions for foreigners', escape: false);
});

it('muestra los pasos del proceso numerados', function () {
    $this->get('/invierte')
        ->assertSee('Cómo trabajamos', escape: false)
        ->assertSee('Debida diligencia', escape: false)
        ->assertSee('Paso 1', escape: false);
});

/*
|--------------------------------------------------------------------------
| Aviso legal
|--------------------------------------------------------------------------
*/

it('publica el aviso de que no es asesoria legal ni fiscal', function () {
    // Una pagina de inversion sin este aviso expone al cliente.
    $this->get('/invierte')
        ->assertSee('no constituye asesoría legal', escape: false);
});

it('publica el aviso tambien en ingles', function () {
    $this->get('/en/invest')
        ->assertSee('does not constitute legal, tax or investment advice', escape: false);
});

/*
|--------------------------------------------------------------------------
| Oportunidades
|--------------------------------------------------------------------------
*/

it('solo lista propiedades marcadas como inversion', function () {
    propiedadInversion('Oportunidad de inversion', true);
    propiedadInversion('Vivienda normal', false);

    $this->get('/invierte')
        ->assertSee('Oportunidad de inversion', escape: false)
        ->assertDontSee('Vivienda normal', escape: false);
});

it('no muestra borradores marcados como inversion', function () {
    $borrador = Property::factory()->draft()->create([
        'property_type_id' => PropertyType::first()->id,
        'is_investment' => true,
    ]);
    app(PropertyService::class)->syncTranslations($borrador, ['es' => ['title' => 'Borrador inversion']]);

    $this->get('/invierte')->assertDontSee('Borrador inversion', escape: false);
});

it('muestra un estado vacio honesto cuando no hay oportunidades', function () {
    // No se finge un catalogo que no existe.
    $this->get('/invierte')
        ->assertOk()
        ->assertSee('no hay oportunidades publicadas', escape: false);
});

it('enlaza al listado filtrado por inversion', function () {
    propiedadInversion('Con enlace');

    $this->get('/invierte')->assertSee('inversion=1', escape: false);
});

/*
|--------------------------------------------------------------------------
| WhatsApp
|--------------------------------------------------------------------------
*/

it('el CTA de inversion usa el mensaje de inversion', function () {
    app(SettingsService::class)->setMany([
        'whatsapp_investment_message' => ['es' => 'Quiero invertir en RD', 'en' => 'I want to invest'],
        'contact_whatsapp_message' => ['es' => 'Mensaje general', 'en' => 'General message'],
    ]);

    $html = $this->get('/invierte')->getContent();

    // El mensaje general TAMBIEN aparece, y es correcto: el boton flotante
    // global lo usa en todas las paginas. Lo que se comprueba aqui es que el
    // CTA propio de la pagina lleva el suyo.
    expect($html)->toContain(rawurlencode('Quiero invertir en RD'));

    // Y que el enlace del CTA (el que va dentro de la banda final) no es el
    // del boton flotante.
    preg_match_all('/href="(https:\/\/wa\.me\/[^"]+)"/', $html, $coincidencias);

    expect($coincidencias[1])->toContain(
        'https://wa.me/18095550100?text='.rawurlencode('Quiero invertir en RD')
    );
});

it('el boton flotante global sigue usando el mensaje general', function () {
    app(SettingsService::class)->setMany([
        'whatsapp_investment_message' => ['es' => 'Quiero invertir en RD', 'en' => 'I want to invest'],
        'contact_whatsapp_message' => ['es' => 'Mensaje general', 'en' => 'General message'],
    ]);

    $this->get('/invierte')->assertSee(rawurlencode('Mensaje general'), escape: false);
});

/*
|--------------------------------------------------------------------------
| Contenido editable
|--------------------------------------------------------------------------
*/

it('usa el titular editado desde el panel', function () {
    $hero = ContentSection::where('page_key', 'invest')->where('section_key', 'hero')->first();
    $hero->translations()->where('locale', 'es')->update(['title' => 'Titular de inversión cambiado']);
    ContentSection::flushCache('invest');

    $this->get('/invierte')->assertSee('Titular de inversión cambiado', escape: false);
});

it('permite ocultar una seccion completa', function () {
    $proceso = ContentSection::where('page_key', 'invest')->where('section_key', 'process')->first();
    $proceso->update(['is_active' => false]);
    ContentSection::flushCache('invest');

    $this->get('/invierte')->assertDontSee('Debida diligencia', escape: false);
});

/*
|--------------------------------------------------------------------------
| Panel
|--------------------------------------------------------------------------
*/

it('el panel permite elegir que pagina editar', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->get('/admin/contenido?pagina=invest')
        ->assertOk()
        ->assertSee('Por qué invertir', escape: false);
});

it('rechaza una pagina de contenido inventada', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->get('/admin/contenido?pagina=inventada')->assertNotFound();
});

it('guarda una consulta de inversion', function () {
    $this->post('/invierte', [
        'name' => 'Inversionista',
        'phone' => '8295550101',
        'email' => 'invest@example.com',
        'budget_range' => '250000-500000 USD',
        'preferred_contact' => 'email',
        'message' => 'Busco una propiedad para renta vacacional.',
        'form_token' => Crypt::encryptString((string) now()->subSeconds(4)->timestamp),
    ])->assertRedirect('/invierte');

    $lead = Lead::firstOrFail();
    expect($lead->source->value)->toBe('investment_page')
        ->and($lead->interest_type)->toBe('invest')
        ->and($lead->budget_range)->toBe('250000-500000 USD');
});

it('muestra el formulario de inversion en ingles', function () {
    $this->get('/en/invest')->assertOk()->assertSee('Tell us about your investment', false);
});
