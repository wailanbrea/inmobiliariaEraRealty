<?php

namespace App\Modules\Audit\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * El registro de auditoria lo ven solo administradores.
     *
     * Un editor no debe poder consultar quien entro, desde que IP ni a que
     * hora: es informacion de seguridad, no de contenido.
     */
    public function index(): View
    {
        abort_unless(auth()->user()?->hasAnyRole(['admin', 'super_admin']), 403);

        return view('admin.audit.index');
    }
}
