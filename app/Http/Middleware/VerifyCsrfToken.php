<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
        'api/login',
        'api/upload-pdf',
        '/api/obtener-funciones-distinct-role', 
        '/api/obtener-funciones-distinct',
        '/api/agregar-usuario', 
        '/api/agregar-funcion', 
        '/api/agregarFunciones', 
    ];
}
