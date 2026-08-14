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
            'ultimosLeads' => Lead::with('property')->latest()->limit(5)->get(),
            'desde' => $desde,
            'hasta' => $hasta,
        ]);
    }
}
