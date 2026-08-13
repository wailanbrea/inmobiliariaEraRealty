<?php

namespace App\Modules\Media\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class MediaController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->can('manage_properties'), 403);

        return view('admin.media.index');
    }
}
