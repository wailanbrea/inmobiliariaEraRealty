<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Leads\Models\Lead;
use App\Modules\Reports\Services\ReportService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(): View
    {
        [$desde, $hasta] = ReportService::defaultRange();

        // Un editor o un agente no ven metricas de negocio: el dashboard les
        // muestra solo el inventario y sus tareas pendientes.
        $puedeVerMetricas = auth()->user()?->hasAnyRole(['admin', 'super_admin']) ?? false;

        return view('admin.dashboard.index', [
            'inventario' => $this->reports->inventory(),
            'resumen' => $puedeVerMetricas ? $this->reports->summary($desde, $hasta) : null,
            'serie' => $puedeVerMetricas ? $this->reports->dailySeries($desde, $hasta) : null,
            'masVistas' => $puedeVerMetricas ? $this->reports->mostViewed($desde, $hasta, 5) : null,

            // El reparto del catalogo lo ve TODO el mundo: un editor tambien
            // necesita saber que hay publicado. Lo que se reserva a los
            // administradores son las metricas de negocio (leads, dinero).
            'porTipo' => $this->reports->byType(),
            'porEstado' => $this->reports->byStatus(),
            'ultimosLeads' => Lead::with('property')->latest()->limit(5)->get(),
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }
}
