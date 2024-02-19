<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CpuSedeController;
use App\Http\Controllers\CpuFacultadController;
use App\Http\Controllers\CpuCarreraController;
use App\Http\Controllers\UsuarioController; // Agregado el controlador de Usuario
use App\Http\Controllers\CpuProfesionController;


// Autenticación
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Menú
Route::middleware('auth:sanctum')->get('/menu', [MenuController::class, 'index']);

// Roles
Route::middleware('auth:sanctum')->post('/agregar-role-usuario', [RoleController::class, 'agregarRoleUsuario']);
Route::middleware('auth:sanctum')->put('/modificar-role-usuario/{id}', [RoleController::class, 'modificarRoleUsuario']);
Route::middleware('auth:sanctum')->delete('/eliminar-role-usuario/{id}', [RoleController::class, 'eliminarRoleUsuario']);
Route::middleware('auth:sanctum')->get('/consultar-roles', [RoleController::class, 'consultarRoles']);

// Sede
Route::middleware('auth:sanctum')->post('/agregar-sede', [CpuSedeController::class, 'agregarSede']);
Route::middleware('auth:sanctum')->put('/modificar-sede/{id}', [CpuSedeController::class, 'modificarSede']);
Route::middleware('auth:sanctum')->delete('/eliminar-sede/{id}', [CpuSedeController::class, 'eliminarSede']);
Route::middleware('auth:sanctum')->get('/consultar-sedes', [CpuSedeController::class, 'consultarSedes']);

// Facultad
Route::middleware('auth:sanctum')->post('/agregar-facultad', [CpuFacultadController::class, 'agregarFacultad']);
Route::middleware('auth:sanctum')->put('/modificar-facultad/{id}', [CpuFacultadController::class, 'modificarFacultad']);
Route::middleware('auth:sanctum')->delete('/eliminar-facultad/{id}', [CpuFacultadController::class, 'eliminarFacultad']);
Route::middleware('auth:sanctum')->get('/consultar-facultades', [CpuFacultadController::class, 'consultarFacultades']);

// Carrera
Route::middleware('auth:sanctum')->post('/agregar-carrera', [CpuCarreraController::class, 'agregarCarrera']);
Route::middleware('auth:sanctum')->put('/modificar-carrera/{id}', [CpuCarreraController::class, 'modificarCarrera']);
Route::middleware('auth:sanctum')->delete('/eliminar-carrera/{id}', [CpuCarreraController::class, 'eliminarCarrera']);
Route::middleware('auth:sanctum')->get('/consultar-carreras', [CpuCarreraController::class, 'consultarCarreras']);

// Usuario
Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/agregar-usuario', [UsuarioController::class, 'agregarUsuario']);
    Route::put('/dar-de-baja-usuario/{id}', [UsuarioController::class, 'darDeBajaUsuario']);
    Route::put('/dar-de-alta-usuario/{id}', [UsuarioController::class, 'darDeAltaUsuario']);
    Route::put('/cambiar-password/{id}', [UsuarioController::class, 'cambiarPassword']);
    Route::put('/actualizar-informacion-personal/{id}', [UsuarioController::class, 'actualizarInformacionPersonal']);
});


//profesiones

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/agregar-profesion', [CpuProfesionController::class, 'agregarProfesion']);
    Route::put('/modificar-profesion/{id}', [CpuProfesionController::class, 'modificarProfesion']);
    Route::delete('/eliminar-profesion/{id}', [CpuProfesionController::class, 'eliminarProfesion']);
    Route::get('/consultar-profesiones', [CpuProfesionController::class, 'consultarProfesiones']);
});
