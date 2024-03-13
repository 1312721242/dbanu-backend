<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CpuSedeController;
use App\Http\Controllers\CpuFacultadController;
use App\Http\Controllers\CpuCarreraController;
use App\Http\Controllers\UsuarioController; // Agregado el controlador de Usuario
use App\Http\Controllers\CpuProfesionController;
use App\Http\Controllers\CpuEstadosController;
use App\Http\Controllers\CpuUsermenuController;
use App\Http\Controllers\CpuUserfunctionController;
use App\Http\Controllers\CpuUserrolefunctionController;
use App\Http\Controllers\CpuMatriculaConfiguracionController;
use App\Http\Controllers\CpuLegalizacionMatriculaController;
use App\Http\Controllers\CpuPeriodosController;
use App\Http\Controllers\LegalizacionMatriculaSecretariaController;
use App\Http\Controllers\CpuCasosMatriculaController;
use App\Http\Controllers\CpuNotificacionMatriculaController;



// Autenticación
Route::post('/login', [AuthController::class, 'login']);
Route::post('/loginapp', [AuthController::class, 'loginApp']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Route::get('legalizacion-matricula/export-template', [LegalizacionMatriculaSecretariaController::class, 'exportTemplate']);

// // Ruta para subir el archivo con la data de los asignados para que se matriculen
// Route::post('legalizacion-matricula/upload', [LegalizacionMatriculaSecretariaController::class, 'upload']);


// // Rutas para el controlador CpuMatriculaConfiguracionController
// Route::get('cpu_matricula_configuracion', [CpuMatriculaConfiguracionController::class, 'index']);
// Route::get('cpu_matricula_configuracion/{id}', [CpuMatriculaConfiguracionController::class, 'show']);
// Agrega más rutas según tus necesidades

Route::middleware(['auth:sanctum'])->group(function () {
    // Menú
    Route::get('/menu', [MenuController::class, 'index']);
    // Usuario
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    //rutas para manejar el excel de subida de cupos asignados para matriculas
    //ruta para exportar la platilla de excel
    Route::get('legalizacion-matricula/export-template', [LegalizacionMatriculaSecretariaController::class, 'exportTemplate']);

    // Ruta para subir el archivo con la data de los asignados para que se matriculen
    Route::post('legalizacion-matricula/upload/{id_periodo}', [LegalizacionMatriculaSecretariaController::class, 'upload']);


    // Roles
    Route::post('/agregar-role-usuario', [RoleController::class, 'agregarRoleUsuario']);
    Route::put('/modificar-role-usuario/{id}', [RoleController::class, 'modificarRoleUsuario']);
    Route::delete('/eliminar-role-usuario/{id}', [RoleController::class, 'eliminarRoleUsuario']);
    Route::get('/consultar-roles', [RoleController::class, 'consultarRoles']);

    // Sede
    Route::post('/agregar-sede', [CpuSedeController::class, 'agregarSede']);
    Route::put('/modificar-sede/{id}', [CpuSedeController::class, 'modificarSede']);
    Route::delete('/eliminar-sede/{id}', [CpuSedeController::class, 'eliminarSede']);
    Route::get('/consultar-sedes', [CpuSedeController::class, 'consultarSedes']);

    // Facultad
    Route::post('/agregar-facultad', [CpuFacultadController::class, 'agregarFacultad']);
    Route::put('/modificar-facultad/{id}', [CpuFacultadController::class, 'modificarFacultad']);
    Route::delete('/eliminar-facultad/{id}', [CpuFacultadController::class, 'eliminarFacultad']);
    Route::get('/consultar-facultades', [CpuFacultadController::class, 'consultarFacultades']);
    Route::get('/consultar-facultades-sede/{id_sede}', [CpuFacultadController::class, 'consultarFacultadesporSede']);

    // Carrera
    Route::post('/agregar-carrera', [CpuCarreraController::class, 'agregarCarrera']);
    Route::put('/modificar-carrera/{id}', [CpuCarreraController::class, 'modificarCarrera']);
    Route::delete('/eliminar-carrera/{id}', [CpuCarreraController::class, 'eliminarCarrera']);
    Route::get('/consultar-carreras', [CpuCarreraController::class, 'consultarCarreras']);


    //administracion de usuarios
    Route::post('/agregar-usuario', [UsuarioController::class, 'agregarUsuario']);
    Route::put('/dar-de-baja-usuario/{id}', [UsuarioController::class, 'darDeBajaUsuario']);
    Route::put('/dar-de-alta-usuario/{id}', [UsuarioController::class, 'darDeAltaUsuario']);
    Route::put('/cambiar-password/{id}', [UsuarioController::class, 'cambiarPassword']);
    Route::put('/actualizar-informacion-personal/{id}', [UsuarioController::class, 'actualizarInformacionPersonal']);

    //profesiones
    Route::post('/agregar-profesion', [CpuProfesionController::class, 'agregarProfesion']);
    Route::put('/modificar-profesion/{id}', [CpuProfesionController::class, 'modificarProfesion']);
    Route::delete('/eliminar-profesion/{id}', [CpuProfesionController::class, 'eliminarProfesion']);
    Route::get('/consultar-profesiones', [CpuProfesionController::class, 'consultarProfesiones']);

    //estados
    Route::post('/agregar-estado', [CpuEstadosController::class, 'agregarEstado']);
    Route::put('/modificar-estado/{id}', [CpuEstadosController::class, 'modificarEstado']);
    Route::delete('/eliminar-estado/{id}', [CpuEstadosController::class, 'eliminarEstado']);
    Route::get('/consultar-estados', [CpuEstadosController::class, 'consultarEstados']);

    //menu
    Route::post('/agregar-menu', [CpuUsermenuController::class, 'agregarMenu']);
    Route::put('/modificar-menu/{id}', [CpuUsermenuController::class, 'modificarMenu']);
    Route::delete('/eliminar-menu/{id}', [CpuUsermenuController::class, 'eliminarMenu']);
    Route::get('/consultar-menus', [CpuUsermenuController::class, 'consultarMenus']);

    //funciones
    Route::post('/agregar-funcion', [CpuUserfunctionController::class, 'agregarFuncion']);
    Route::put('/modificar-funcion/{id}', [CpuUserfunctionController::class, 'modificarFuncion']);
    Route::delete('/eliminar-funcion/{id}', [CpuUserfunctionController::class, 'eliminarFuncion']);
    Route::get('/consultar-funciones', [CpuUserfunctionController::class, 'consultarFunciones']);

    //userrolefunction
    Route::post('/agregar-userrolefuncion', [CpuUserrolefunctionController::class, 'agregarFuncion']);
    Route::put('/modificar-userrolefuncion/{id}', [CpuUserrolefunctionController::class, 'modificarFuncion']);
    Route::delete('/eliminar-fuserroleuncion/{id}', [CpuUserrolefunctionController::class, 'eliminarFuncion']);
    Route::get('/consultar-userrolefunciones', [CpuUserrolefunctionController::class, 'consultarFunciones']);

    //periodos
    Route::get('/consultar-periodos', [CpuPeriodosController::class, 'consultarPeriodos']);

    //generar plantilla y subir arhivo de excel a la base de datos
    // Ruta para generar la plantilla de archivo
    // Route::get('legalizacion-matricula/export-template', [LegalizacionMatriculaSecretariaController::class, 'exportTemplate']);

    // // Ruta para subir el archivo con la data de los asignados para que se matriculen
    // Route::post('legalizacion-matricula/upload', [LegalizacionMatriculaSecretariaController::class, 'upload']);


    // Rutas para el controlador CpuMatriculaConfiguracionController
    Route::get('cpu_matricula_configuracion', [CpuMatriculaConfiguracionController::class, 'index']);
    Route::get('cpu_matricula_configuracion/{id}', [CpuMatriculaConfiguracionController::class, 'show']);
    Route::get('cpu_matricula_periodo_activo', [CpuMatriculaConfiguracionController::class, 'periodoActivo']);

    //subir documentos matricula
    Route::post('upload-pdf', [CpuLegalizacionMatriculaController::class, 'uploadPdf']);
    Route::get('/person-data', [CpuLegalizacionMatriculaController::class, 'getPersonData']);

    //tomar los casos de matricula
    Route::get('casos-matricula/{idSecretaria}/{idPeriodo}', [CpuCasosMatriculaController::class, 'index']);
    Route::post('casos-matricula/{idCaso}/revision-documentos', [CpuCasosMatriculaController::class, 'revisionDocumentos']);
    Route::get('matricula-cases/{id_usuario}/{id_periodo}', [CpuCasosMatriculaController::class, 'getMatriculaCases']);
    Route::get('matricula-cases-all/{id_periodo}', [CpuCasosMatriculaController::class, 'getAllMatriculaCases']);
 
    //apis notificaciones para app de pabelco
    Route::get('/notificaciones', [CpuNotificacionMatriculaController::class, 'index']);
    Route::put('/notificaciones/{id}/marcar-leida', [CpuNotificacionMatriculaController::class, 'markAsRead']);
    Route::get('/notificaciones/sin-leer', [CpuNotificacionMatriculaController::class, 'unreadCount']);

    

});
