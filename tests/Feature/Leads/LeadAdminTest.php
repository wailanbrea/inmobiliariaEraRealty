<?php

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Modules\Leads\Models\Lead;

function adminLead(array $attributes = []): Lead
{
    return Lead::create(array_merge([
        'source' => LeadSource::ContactPage,
        'status' => LeadStatus::New,
        'name' => 'Maria Cliente',
        'phone' => '8095550101',
        'email' => 'maria@example.com',
        'message' => 'Quiero recibir informacion.',
    ], $attributes));
}

it('protege la bandeja de leads', function () {
    $this->get('/admin/leads')->assertRedirect(route('admin.login'));
});

it('lista y filtra leads', function () {
    $admin = userWithRole('admin');
    adminLead(['name' => 'Visible']);
    adminLead(['name' => 'Oculto', 'status' => LeadStatus::Spam]);

    $this->actingAs($admin)->get('/admin/leads?status=new')
        ->assertOk()->assertSee('Visible')->assertDontSee('Oculto');
});

it('muestra el detalle y actualiza su gestion', function () {
    $admin = userWithRole('admin');
    $assignee = userWithRole('agent', ['name' => 'Agente Asignado']);
    $lead = adminLead();

    $this->actingAs($admin)->get(route('admin.leads.show', $lead))
        ->assertOk()->assertSee('Maria Cliente');

    $this->actingAs($admin)->put(route('admin.leads.update', $lead), [
        'status' => LeadStatus::Contacted->value,
        'assigned_to_user_id' => $assignee->id,
        'admin_notes' => 'Llamar el lunes.',
    ])->assertRedirect();

    $lead->refresh();
    expect($lead->status)->toBe(LeadStatus::Contacted)
        ->and($lead->assigned_to_user_id)->toBe($assignee->id)
        ->and($lead->contacted_at)->not->toBeNull();
});

it('exporta en csv respetando filtros', function () {
    $admin = userWithRole('admin');
    adminLead(['name' => 'Exportable']);
    adminLead(['name' => 'No exportar', 'status' => LeadStatus::Spam]);

    $response = $this->actingAs($admin)->get('/admin/leads/exportar?status=new');
    $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
    expect($response->streamedContent())->toContain('Exportable')->not->toContain('No exportar');
});
