<?php

namespace App\Modules\Pages\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Properties\Models\Property;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    /** Paginas con bloques editables. */
    public const PAGES = ['home', 'invest'];

    public function index(Request $request): View
    {
        $this->authorize('create', Property::class);

        $pageKey = $request->query('pagina', 'home');

        abort_unless(in_array($pageKey, self::PAGES, true), 404);

        return view('admin.content.index', [
            'pageKey' => $pageKey,
            'pages' => self::PAGES,
        ]);
    }
}
