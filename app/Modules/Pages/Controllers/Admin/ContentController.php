<?php

namespace App\Modules\Pages\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\Properties\Models\Property;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function index(): View
    {
        $this->authorize('create', Property::class);

        return view('admin.content.index');
    }
}
