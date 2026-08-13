<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        // Las metricas reales llegan en la Fase 1, cuando existan las tablas
        // de propiedades, leads y noticias. Ver docs/03_ADMIN_PANEL.md 2.3.
        return view('admin.dashboard.index');
    }
}
