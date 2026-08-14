<?php

namespace App\Modules\Users\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Solo un super_admin gestiona usuarios.
     *
     * Un 'admin' puede tocar todo el contenido, pero no repartir accesos: esa
     * es la separacion que impide que un panel comprometido se convierta en
     * un panel perdido.
     */
    public function index(): View
    {
        abort_unless(auth()->user()?->isSuperAdmin(), 403);

        return view('admin.users.index');
    }
}
