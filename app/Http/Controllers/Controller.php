<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Laravel 12 ya no lo incluye por defecto. Lo necesitamos para poder
    // llamar a $this->authorize() en los controladores del panel.
    use AuthorizesRequests;
}
