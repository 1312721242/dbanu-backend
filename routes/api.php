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
use App\Http\Controllers\CpuTextosMensajesController;
use App\Http\Controllers\CpuFuncionesTextosController;
use App\Http\Controllers\CpuYearController;
use App\Http\Controllers\CpuObjetivoNacionalController;
use App\Http\Controllers\CpuFuenteInformacionController;
use App\Http\Controllers\CpuEvidenciaController;
use App\Http\Controllers\CpuBecadoController;
use App\Http\Controllers\CpuConsumoBecadoController;
use App\Http\Controllers\TurnosController;
use App\Http\Controllers\CpuPersonaController;
use App\Http\Controllers\CpuTipoDiscapacidadController;
use App\Http\Controllers\CpuTipoSangreController;
use App\Http\Controllers\CpuIndicadorController;
use App\Http\Controllers\CpuAtencionesController;
use App\Http\Controllers\CpuEstandarController;

// Autenticación
Route::get('credencial-pdf/{identificacion}/{periodo}', [CpuBecadoController::class, 'generarCredencialPDF']);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/loginapp', [AuthController::class, 'loginApp']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

// Route::get('legalizacion-matricula/export-template', [LegalizacionMatriculaSecretariaController::class, 'exportTemplate']);
Route::middleware(['auth:sanctum'])->group(function () {
    // Menú
    Route::get('/menu', [MenuController::class, 'index']);
    // menu aspirantes
    Route::get('/menuaspirantes', [MenuController::class, 'menuaspirantes']);
    Route::get('/all-menu-items', [MenuController::class, 'getAllMenuItems']);
    // Usuario
    Route::get('/user', function (Request $request) {
        return $request->user();
    });


    //rutas para manejar el excel de subida de cupos asignados para matriculas
    // ruta para exportar la platilla de excel
    Route::get('legalizacion-matricula/export-template', [LegalizacionMatriculaSecretariaController::class, 'exportTemplate']);

    // Ruta para subir el archivo con la data de los asignados para que se matriculen
    Route::post('legalizacion-matricula/upload/{id_periodo}', [LegalizacionMatriculaSecretariaController::class, 'upload']);


    // Roles
    Route::post('/agregar-role-usuario', [RoleController::class, 'agregarRoleUsuario']);
    Route::put('/modificar-role-usuario/{id}', [RoleController::class, 'modificarRoleUsuario']);
    Route::delete('/eliminar-role-usuario/{id}', [RoleController::class, 'eliminarRoleUsuario']);
    Route::get('/consultar-roles', [RoleController::class, 'consultarRoles']);

    //menus
    Route::post('/agregar-menu', [MenuController::class, 'agregarMenu']);

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

    // años
    Route::post('/agregar-year', [CpuYearController::class, 'agregarYear']);
    Route::put('/modificar-year/{id}', [CpuYearController::class, 'modificarYear']);
    Route::delete('/eliminar-year/{id}', [CpuYearController::class, 'eliminarYear']);
    Route::get('/consultar-years', [CpuYearController::class, 'consultarYear']);

    // Indicadores
    Route::post('/agregar-indicador', [CpuIndicadorController::class, 'agregarIndicador']);
    Route::put('/modificar-indicador/{id}', [CpuIndicadorController::class, 'modificarIndicador']);
    Route::delete('/eliminar-indicador/{id}', [CpuIndicadorController::class, 'eliminarIndicador']);
    // Route::get('/consultar-indicador', [CpuIndicadorController::class, 'consultarIndicador']);
    Route::get('/consultar-indicador/{id_year}', [CpuIndicadorController::class, 'consultarIndicador']);

    //Estándares
    Route::get('/obtener-estandares/{id_year}/{id_indicador}', [CpuEstandarController::class, 'obtenerEstandares']);
    Route::post('/estandares', [CpuEstandarController::class, 'store']);
    Route::put('/actualizar-estandar/{id}', [CpuEstandarController::class, 'edit']);

    // Objetivo Nacional
    Route::post('/agregar-objetivo', [CpuObjetivoNacionalController::class, 'agregarObjetivoNacional']);
    Route::put('/modificar-objetivo/{id}', [CpuObjetivoNacionalController::class, 'modificarObjetivoNacional']);
    Route::delete('/eliminar-objetivo/{id}', [CpuObjetivoNacionalController::class, 'eliminarObjetivoNacional']);
    Route::get('/consultar-objetivos', [CpuObjetivoNacionalController::class, 'consultarObjetivoNacional']);

    // fuentes de informacion
    Route::post('/agregar-fuente-informacion', [CpuFuenteInformacionController::class, 'agregarFuenteInformacion']);
    Route::put('/modificar-fuente-informacion/{id}', [CpuFuenteInformacionController::class, 'modificarFuenteInformacion']);
    Route::delete('/eliminar-fuente-informacion/{id}', [CpuFuenteInformacionController::class, 'eliminarFuenteInformacion']);
    Route::get('/consultar-fuente-informacion', [CpuFuenteInformacionController::class, 'consultarFuenteInformacion']);


    // Carrera
    Route::post('/agregar-carrera', [CpuCarreraController::class, 'agregarCarrera']);
    Route::put('/modificar-carrera/{id}', [CpuCarreraController::class, 'modificarCarrera']);
    Route::delete('/eliminar-carrera/{id}', [CpuCarreraController::class, 'eliminarCarrera']);
    Route::get('/consultar-carreras', [CpuCarreraController::class, 'consultarCarreras']);


    //administracion de usuarios
    Route::post('/agregar-usuario', [UsuarioController::class, 'agregarUsuario']);
    Route::put('/dar-de-baja-usuario/{id}', [UsuarioController::class, 'darDeBajaUsuario']);
    Route::put('/actualizar-estado-usuario/{userId}', [UsuarioController::class, 'darDeBajaUsuario']);
    Route::put('/dar-de-alta-usuario/{id}', [UsuarioController::class, 'darDeAltaUsuario']);
    Route::put('/cambiar-password/{id}', [UsuarioController::class, 'cambiarPassword']);
    Route::put('/actualizar-informacion-personal/{id}', [UsuarioController::class, 'actualizarInformacionPersonal']);
    Route::get('/users/search', [UsuarioController::class, 'search']);
    Route::post('cambiar-password-app', [UsuarioController::class, 'cambiarPasswordApp']);
    Route::get('funcionarios/{id}', [UsuarioController::class, 'obtenerInformacion']);


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

    //periodos
    Route::post('/agregar-periodos', [CpuPeriodosController::class, 'agregarPeriodos']);
    Route::put('/modificar-periodos/{id}', [CpuPeriodosController::class, 'modificarPeriodos']);
    Route::delete('/eliminar-periodos/{id}', [CpuPeriodosController::class, 'eliminarPeriodos']);
    Route::get('/consultar-periodos', [CpuPeriodosController::class, 'consultarPeriodos']);

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
    Route::post('/agregarFunciones', [CpuUserfunctionController::class, 'agregarFunciones']);


    //userrolefunction
    Route::post('/agregar-funcion-rol', [CpuUserrolefunctionController::class, 'agregarFuncion']);
    // Route::post('/agregar-userrolefuncion', [CpuUserrolefunctionController::class, 'agregarFuncion']);
    Route::put('/modificar-userrolefuncion/{id}', [CpuUserrolefunctionController::class, 'modificarFuncion']);
    Route::delete('/eliminar-fuserroleuncion/{id}', [CpuUserrolefunctionController::class, 'eliminarFuncion']);
    Route::get('/consultar-userrolefunciones', [CpuUserrolefunctionController::class, 'consultarFunciones']);
    Route::get('/obtener-funciones-distinct', [CpuUserrolefunctionController::class, 'obtenerFuncionesDistinct']);
    Route::post('/obtener-funciones-distinct-role', [CpuUserrolefunctionController::class, 'obtenerFuncionesDistinctRole']);
    Route::get('/funciones-con-asignadas', [CpuUserrolefunctionController::class, 'obtenerFuncionesConAsignadas']);
    //periodos
    //Route::get('/consultar-periodos', [CpuPeriodosController::class, 'consultarPeriodos']);

    //generar plantilla y subir arhivo de excel a la base de datos
    // Ruta para generar la plantilla de archivo
    Route::get('legalizacion-matricula/export-template', [LegalizacionMatriculaSecretariaController::class, 'exportTemplate']);

    // // Ruta para subir el archivo con la data de los asignados para que se matriculen
    Route::post('legalizacion-matricula/upload', [LegalizacionMatriculaSecretariaController::class, 'upload']);


    // Rutas para el controlador CpuMatriculaConfiguracionController
    Route::get('cpu_matricula_configuracion', [CpuMatriculaConfiguracionController::class, 'index']);
    Route::get('cpu_matricula_configuracion-fechas/{id_periodo}', [CpuMatriculaConfiguracionController::class, 'fechasMatricula']);
    Route::get('cpu_matricula_configuracion/{id}', [CpuMatriculaConfiguracionController::class, 'show']);
    Route::get('cpu_matricula_periodo_activo', [CpuMatriculaConfiguracionController::class, 'periodoActivo']);
    Route::post('cpu_matricula_configuracion/save', [CpuMatriculaConfiguracionController::class, 'store']);


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

    // toma textos para mostrar en el sistema de legalización de matricula de los estudiante
    Route::get('/textos-legalizar-matricula-aspirantes', [CpuTextosMensajesController::class, 'obtenerTextosFuncionTres']);
    Route::put('/editar-textos-funcion-tres', [CpuTextosMensajesController::class, 'editarTextosFuncionTres']);

    // funciones que llevan textos
    Route::get('/funciones-textos', [CpuFuncionesTextosController::class, 'index']);
    Route::post('/funciones-textos', [CpuFuncionesTextosController::class, 'store']);
    Route::get('/funciones-textos/{id}', [CpuFuncionesTextosController::class, 'show']);
    Route::put('/funciones-textos/{id}', [CpuFuncionesTextosController::class, 'update']);
    Route::delete('/funciones-textos/{id}', [CpuFuncionesTextosController::class, 'destroy']);

    //actualizar email
    Route::put('actualizar-email/{id}', [LegalizacionMatriculaSecretariaController::class, 'actualizarEmail']);
    Route::get('consultar-num-casos', [LegalizacionMatriculaSecretariaController::class, 'consultarNumCasos']);
    Route::post('reasignar-casos', [LegalizacionMatriculaSecretariaController::class, 'reasignarCasos']);
    Route::post('delete-records/{id_periodo}', [LegalizacionMatriculaSecretariaController::class, 'deleteRecords']);
    Route::get('obtener-estudiantes/{id_periodo}', [LegalizacionMatriculaSecretariaController::class, 'consultarEstudiantes']);
    Route::get('consultar-num-casos-por-secretaria', [LegalizacionMatriculaSecretariaController::class, 'consultarNumCasosPorSecretaria']);

    // Rutas para el controlador CpuEvidenciaController
    Route::post('/evidencia/agregar', [CpuEvidenciaController::class, 'agregarEvidencia']);
    Route::put('/evidencia/actualizar/{id}', [CpuEvidenciaController::class, 'actualizarEvidencia']);
    Route::delete('/evidencia/{id}/eliminar', [CpuEvidenciaController::class, 'eliminarEvidencia']);
    Route::get('/evidencia', [CpuEvidenciaController::class, 'consultarEvidencias']);
    Route::get('obtener-informacion/{ano}', [CpuEvidenciaController::class, 'obtenerInformacionPorAno']);
    Route::get('descargar-archivo/{ano}/{archivo}', [CpuEvidenciaController::class, 'descargarArchivo'])->name('descargar-archivo');

    //becados
    Route::get('consultar-becado/{identificacion}/{periodo}', [CpuBecadoController::class, 'consultarPorIdentificacionYPeriodo']);
    Route::post('generar-plantilla-becados', [CpuBecadoController::class, 'generarExcel']);
    Route::post('cargar-becados', [CpuBecadoController::class, 'importarExcel']);
    Route::get('qr-code/{identificacion}/{periodo}', [CpuBecadoController::class, 'generarQRCode']);


    Route::post('/registrar-consumo', [CpuConsumoBecadoController::class, 'registrarConsumo']);
    Route::get('registros-por-fecha/{fecha}', [CpuConsumoBecadoController::class, 'registrosPorFecha']);
    Route::get('detalle-registro/{fecha}', [CpuConsumoBecadoController::class,'detalleRegistro']);

    // routes/api.php


    Route::post('/agregarTurnos', [TurnosController::class, 'agregarTurnos']);
    Route::post('/turnos', [TurnosController::class, 'listarTurnos']); // Cambiar a POST
    Route::post('/turnos/eliminar', [TurnosController::class, 'eliminarTurno']);
    Route::post('/turnos/funcionario', [TurnosController::class, 'listarTurnosPorFuncionario']);
    Route::post('/turnos/actualizar', [TurnosController::class, 'reservarTurno']);


    //rutas para registros medico ocupaconal
    Route::get('/cpu-persona/{cedula}', [CpuPersonaController::class, 'show']);
    Route::put('/cpu-persona-update/{cedula}', [CpuPersonaController::class, 'update']);
    //registros bienestar
    Route::get('/cpu-persona-bienestar/{cedula}', [CpuPersonaController::class, 'showBienestar']);
    Route::put('/cpu-persona-update-bienestar/{cedula}', [CpuPersonaController::class, 'updateBienestar']);


    //tipos discapacidad
    Route::get('/cpu-tipos-discapacidad', [CpuTipoDiscapacidadController::class, 'index']);
    Route::post('/cpu-tipos-discapacidad', [CpuTipoDiscapacidadController::class, 'store']);
    Route::get('/cpu-tipos-discapacidad/{id}', [CpuTipoDiscapacidadController::class, 'show']);
    Route::put('/cpu-tipos-discapacidad/{id}', [CpuTipoDiscapacidadController::class, 'update']);
    Route::delete('/cpu-tipos-discapacidad/{id}', [CpuTipoDiscapacidadController::class, 'destroy']);

    //tipo de sangre

    Route::get('/tipos-sangre', [CpuTipoSangreController::class, 'index']);

    // buscar funcionario por rol
    Route::post('/users/buscarfuncionariorol', [UsuarioController::class, 'buscarfuncionariorol']);

    //guardar atenciones
    Route::post('/atenciones/guardar', [CpuAtencionesController::class, 'guardarAtencion']);



});

// Route::put('/cpu-persona-update/{cedula}', [CpuPersonaController::class, 'update']);
