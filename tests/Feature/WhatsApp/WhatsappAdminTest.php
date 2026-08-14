<?php

use App\Modules\WhatsApp\Models\WhatsappClick;

function whatsappClick(array $attributes = []): WhatsappClick
{
    return WhatsappClick::create(array_merge([
        'source' => 'website',
        'phone_number' => '18095550100',
        'generated_message' => 'Mensaje de prueba',
        'ip_address' => '127.0.0.1',
    ], $attributes));
}

it('protege el reporte de whatsapp', function () {
    $this->get('/admin/whatsapp')->assertRedirect(route('admin.login'));
});

it('muestra metricas y origenes', function () {
    $admin = userWithRole('admin');
    whatsappClick(['source' => 'float']);
    whatsappClick(['source' => 'property_detail', 'phone_number' => '18295550100']);

    $this->actingAs($admin)->get('/admin/whatsapp')
        ->assertOk()->assertSee('float')->assertSee('property_detail')->assertSee('2');
});

it('filtra el reporte por origen', function () {
    $admin = userWithRole('admin');
    whatsappClick(['source' => 'visible_source', 'generated_message' => 'Visible']);
    whatsappClick(['source' => 'hidden_source', 'generated_message' => 'Oculto']);

    $this->actingAs($admin)->get('/admin/whatsapp?source=visible_source')
        ->assertOk()->assertSee('Visible')->assertDontSee('Oculto');
});

it('valida el rango de fechas', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->get('/admin/whatsapp?from=2026-08-14&to=2026-08-01')
        ->assertSessionHasErrors('to');
});
