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
        'api/obtener-funciones-distinct-role', 
        'api/obtener-funciones-distinct',
        'api/agregar-usuario', 
        'api/agregar-funcion', 
        'api/agregarFunciones', 
        'api/agregar-menu',
        'api/agregar-funcion-rol',
        'api/agregarTurnos',
        'api/turnos',
        'api/turnos/eliminar',
        'api/cpu-persona/{cedula}',
        'api/cpu-persona-update/{cedula}',
        'api/users/buscarfuncionariorol',
        'api/turnos/funcionario',
        'api/turnos/actualizar',
        'api/atenciones/guardar',
        'api/agregar-role-usuario',
        '/consultar-por-codigo-tarjeta/{codigoTarjeta}',
        'api/registrar-consumo',
        'api/agregar-menu',
        'api/cpu_tipo_comida',
        'api/cpu_tipo_comida',
        'api/cpu_tipo_comida/{id}',
        'api/cpu_tipo_comida/{id}',
        'api/cpu_tipo_comida/{id}',
        'api/cpu_comidas',
        'api/cpu_comidas-tipo-comida',
        'api/cpu_comidas',
        'api/cpu_comidas/{id}',
        'api/cpu_comidas/{id}',
        'api/cpu_comidas/{id}',
        'api/eliminar-fuente-informacion/{id}',
        'api/atenciones-psicologia',
        'api/persona/{cedula}',
        'api/atenciones/triaje',
        'api/atenciones/triajesico',
        'api/atenciones/updatederivacionsico',
        'api/clientes/tasty/upload',
        'api/atencionesEliminar/{atencionId}/{nuevoEstado}',
    ];
}
