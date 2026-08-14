<?php

use App\Enums\LeadSource;
use App\Modules\Leads\Models\Lead;
use App\Modules\Locations\Models\Province;
use App\Modules\Properties\Models\Property;
use App\Modules\PropertyTypes\Models\PropertyType;
use Database\Seeders\PropertyTypeSeeder;
use Database\Seeders\SettingsSeeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->seed([SettingsSeeder::class, PropertyTypeSeeder::class]);
    Province::create(['name' => 'Distrito Nacional', 'slug' => 'distrito-nacional', 'is_active' => true]);
});

function publishFormToken(): string
{
    return Crypt::encryptString((string) now()->subSeconds(4)->timestamp);
}

it('muestra publica tu propiedad en ambos idiomas', function () {
    $this->get('/publica-tu-propiedad')->assertOk()->assertSee('Publica tu propiedad', false);
    $this->get('/en/list-your-property')->assertOk()->assertSee('List your property', false);
});

it('guarda la solicitud sin crear una propiedad', function () {
    Mail::fake();
    $type = PropertyType::firstOrFail();
    $province = Province::firstOrFail();

    $this->post('/publica-tu-propiedad', [
        'name' => "Luis G\u{00F3}mez", 'phone' => '8295550101', 'email' => 'luis@example.com',
        'property_type_id' => $type->id, 'operation_type' => 'sale',
        'province_id' => $province->id, 'location' => 'Piantini',
        'bedrooms' => 3, 'bathrooms' => 2.5, 'area' => 180,
        'expected_price' => 350000, 'currency' => 'USD', 'consent' => '1',
        'form_token' => publishFormToken(),
    ])->assertRedirect('/publica-tu-propiedad');

    $lead = Lead::firstOrFail();
    expect($lead->source)->toBe(LeadSource::PublishProperty)
        ->and($lead->details['location'])->toBe('Piantini')
        ->and(Property::count())->toBe(0);
});

it('exige consentimiento y datos basicos de la propiedad', function () {
    $this->from('/publica-tu-propiedad')->post('/publica-tu-propiedad', [
        'name' => 'Luis', 'phone' => '8295550101', 'form_token' => publishFormToken(),
    ])->assertRedirect('/publica-tu-propiedad')
        ->assertSessionHasErrors(['property_type_id', 'operation_type', 'province_id', 'location', 'consent']);

    expect(Lead::count())->toBe(0);
});
