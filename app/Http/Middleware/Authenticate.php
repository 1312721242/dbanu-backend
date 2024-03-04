<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        // Verificar si la ruta solicitada es para loginapp
        if ($request->is('loginapp')) {
            return route('loginapp');
        }

        // Por defecto, redirigir a la ruta de login
        return route('login');
    }
}

