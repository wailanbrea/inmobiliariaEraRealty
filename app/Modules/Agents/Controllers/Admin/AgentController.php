<?php

namespace App\Modules\Agents\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Properties\Models\Property;
use Illuminate\View\View;

class AgentController extends Controller
{
    public function index(): View
    {
        // Los agentes los gestiona quien puede gestionar propiedades: son
        // parte de la misma operación comercial.
        $this->authorize('create', Property::class);

        return view('admin.agents.index');
    }
}
