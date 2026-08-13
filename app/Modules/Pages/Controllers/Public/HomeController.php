<?php

namespace App\Modules\Pages\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        // Fase 4: propiedades destacadas, de inversion, noticias recientes y
        // secciones editables desde content_sections.
        return view('public.home');
    }
}
