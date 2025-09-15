<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CpuAspirantesEvaluacionesController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\CpuSedeController;
use App\Http\Controllers\CpuFacultadController;
use App\Http\Controllers\CpuCarreraController;
use App\Http\Controllers\UsuarioController; // Agregado el controlador de Usuario
use App\Http\Controllers\CpuProfesionController;
use App\Http\Controllers\SecretariasMatriculasControllers;
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
use App\Http\Controllers\CpuAtencionesTrabajoSocialController;
use App\Http\Controllers\CpuAtencionTriajeController;
use App\Http\Controllers\CpuAtencionesFisioterapiaContoller;
use App\Http\Controllers\CpuComidaController;
use App\Http\Controllers\CpuDerivacionController;
use App\Http\Controllers\CpuEstandarController;
use App\Http\Controllers\CpuElementoFundamentalController;
use App\Http\Controllers\CpuTipoComidaController;
use App\Http\Controllers\CpuValorConsumoDiarioBecaController;
use App\Http\Controllers\CpuDatosSocialesController;
use App\Http\Controllers\CpuTipoUsuarioController;
use App\Http\Controllers\CpuInsumoController;
use App\Http\Controllers\ICDController;
use App\Http\Controllers\CpuInsumoOcupadoController;
use App\Http\Controllers\CpuAtencionPsicologiaController;
use App\Http\Controllers\CpuCasosPsicologiaController;
use App\Http\Controllers\CpuCertificadoNivelacionController;
use App\Http\Controllers\CpuClientesTastyController;
use App\Http\Controllers\CpuDatosMedicosController;
use App\Http\Controllers\CpuDienteController;
use App\Http\Controllers\CpuAtencionOdontologiaController;
use App\Http\Controllers\CpuCargoController;
use App\Http\Controllers\CpuCorreoEnviadoController;
use App\Http\Controllers\CpuDirAdminController;
use App\Http\Controllers\CpuTerapiaLenguajeController;
use App\Http\Controllers\CpuTipoBecaController;
use App\Http\Controllers\CpuTramiteController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\CpuPedidoCosturaController;
use App\Http\Controllers\ProductosControllers;
use App\Http\Controllers\ProveedoresControllers;
use App\Http\Controllers\CategoriaActivosControllers;
use App\Http\Controllers\IngresosControllers;
use App\Http\Controllers\EgresosControllers;
use App\Http\Controllers\ApiControllers;
use App\Http\Controllers\CpuResumenAgendaFisioterapiaController;
use App\Http\Controllers\OrdenesAnalisisControllers;
use App\Http\Controllers\TiposAnalisisControllers;
use App\Models\CpuAtencionFisioterapia;
use App\Http\Controllers\NvPeriodosController;
use App\Http\Controllers\NvDocentesController;
use App\Http\Controllers\NvAsignaturasController;
use App\Http\Controllers\NvParalelosController;
use App\Http\Controllers\NvDocenteAsignaturaController;

use App\Http\Controllers\AtencionesDiversidadController;
use App\Http\Controllers\CpuCasosMedicosController;
use App\Http\Controllers\CpuBodegasController;
use App\Http\Controllers\CpuInventariosController;

// Autenticación
Route::get('credencial-pdf/{identificacion}/{periodo}', [CpuBecadoController::class, 'generarCredencialPDF']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/loginapp', [AuthController::class, 'loginApp']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');



// Route::get('legalizacion-matricula/export-template', [LegalizacionMatriculaSecretariaController::class, 'exportTemplate']);
Route::middleware(['auth:sanctum'])->group(function () {
    // Route::middleware(['api'])->group(function () {
    // Menú
    Route::get('/menu', [MenuController::class, 'index']);
    // menu aspirantes
    Route::get('/menuaspirantes', [MenuController::class, 'menuaspirantes']);
    Route::get('/all-menu-items', [MenuController::class, 'getAllMenuItems']);
    // Usuario
    // Route::get('/user', function (Request $request) {
    //     return $request->user();
    // });
    Route::get('/user', function (Request $request) {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }

        // Convertir la respuesta a un array
        $userData = $user->toArray();

        // Modificar el campo `foto_perfil` para devolver la URL completa
        $userData['foto_perfil'] = $user->foto_perfil ? url('Perfiles/' . $user->foto_perfil) : null;

        return response()->json($userData);
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
    Route::get('/consultar-roles-areas', [RoleController::class, 'consultarAreas']);

    //menus
    Route::post('/agregar-menu', [MenuController::class, 'agregarMenu']);
    Route::get('/cpu_tipo_comida', [CpuTipoComidaController::class, 'index']);
    Route::post('/cpu_tipo_comida', [CpuTipoComidaController::class, 'store']);
    Route::get('/cpu_tipo_comida/{id}', [CpuTipoComidaController::class, 'show']);
    Route::put('/cpu_tipo_comida/{id}', [CpuTipoComidaController::class, 'update']);
    Route::delete('/cpu_tipo_comida/{id}', [CpuTipoComidaController::class, 'destroy']);
    Route::get('/cpu_comidas', [CpuComidaController::class, 'index']);
    Route::get('/cpu_comidas-tipo-comida', [CpuComidaController::class, 'indexTipoComida']);
    Route::post('/cpu_comidas', [CpuComidaController::class, 'store']);
    Route::get('/cpu_comidas/{id}', [CpuComidaController::class, 'show']);
    Route::put('/cpu_comidas/{id}', [CpuComidaController::class, 'update']);
    Route::delete('/cpu_comidas/{id}', [CpuComidaController::class, 'destroy']);

    //tasty funcionarios comunidad
    Route::get('/clientes/tasty/export-template', [CpuClientesTastyController::class, 'exportClientesTastyTemplate']);
    Route::post('/clientes/tasty/upload', [CpuClientesTastyController::class, 'uploadClientesTasty']);
    Route::post('/clientes/tasty/disable', [CpuClientesTastyController::class, 'disableClientesTasty']);
    Route::post('/clientes/tasty/disable/individual', [CpuClientesTastyController::class, 'disableClientesTastyIndividual']);
    Route::get('/clientes/tasty/cargos', [CpuClientesTastyController::class, 'getCargos']);



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
    Route::get('/consultar-facultades-tasty/{id_sede}/{id_facultad}/{usr_tipo}', [CpuFacultadController::class, 'consultarFacultadesPorNombreTasty']);

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

    //Elementos Fundamentales
    // Route::get('/consultar-fuente-informacion/{id_sede}/{id_estandar}', [CpuElementoFundamentalController::class, 'consultarFuenteInformacionsede']);
    Route::get('/consultar-fuente-informacion/{id_estandar}', [CpuElementoFundamentalController::class, 'consultarFuenteInformacionsede']);

    Route::post('/elementos', [CpuElementoFundamentalController::class, 'agregarFuenteInformacione']);
    Route::put('/actualizar-elemento/{id}', [CpuElementoFundamentalController::class, 'modificarFuenteInformacion']);
    Route::delete('/eliminar-fuente-informacion/{id}', [CpuElementoFundamentalController::class, 'eliminarFuenteSInformacion']);

    //Fuentes de Información
    Route::get('/fuente-informacion/{id_indicador}', [CpuFuenteInformacionController::class, 'getFuenteInformacion']);
    Route::post('/fuente-informacion', [CpuFuenteInformacionController::class, 'storeFuenteInformacion']);
    Route::put('/fuente-informacion/{id}', [CpuFuenteInformacionController::class, 'updateFuenteInformacion']);

    //Elementos Fundamentales
    // Route::post('/crearatencionpsicologia', [CpuElementoFundamentalController::class, 'agregarFuenteInformacione']);


    // Objetivo Nacional
    Route::post('/agregar-objetivo', [CpuObjetivoNacionalController::class, 'agregarObjetivoNacional']);
    Route::put('/modificar-objetivo/{id}', [CpuObjetivoNacionalController::class, 'modificarObjetivoNacional']);
    Route::delete('/eliminar-objetivo/{id}', [CpuObjetivoNacionalController::class, 'eliminarObjetivoNacional']);
    Route::get('/consultar-objetivos', [CpuObjetivoNacionalController::class, 'consultarObjetivoNacional']);

    // // fuentes de informacion
    // Route::post('/agregar-fuente-informacion', [CpuFuenteInformacionController::class, 'agregarFuenteInformacion']);
    // Route::put('/modificar-fuente-informacion/{id}', [CpuFuenteInformacionController::class, 'modificarFuenteInformacion']);
    // Route::delete('/eliminar-fuente-informacion/{id}', [CpuFuenteInformacionController::class, 'eliminarFuenteSInformacion']);
    // Route::get('/consultar-fuente-informacion', [CpuFuenteInformacionController::class, 'consultarFuenteInformacion']);

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
    Route::patch('/cambiar-password/{id}', [UsuarioController::class, 'cambiarPassword']);
    Route::put('/actualizar-informacion-personal/{id}', [UsuarioController::class, 'actualizarInformacionPersonal']);
    Route::get('/users/search', [UsuarioController::class, 'search']);
    Route::post('cambiar-password-app', [UsuarioController::class, 'cambiarPasswordApp']);
    Route::get('funcionarios/{id}', [UsuarioController::class, 'obtenerInformacion']);
    Route::post('cambiar-contrasena', [UsuarioController::class, 'cambiarContrasena']);

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
    Route::delete('/eliminarFuncionesUsuario/{id}', [CpuUserfunctionController::class, 'eliminarFuncionesUsuario']);

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
    Route::post('/evidencia/{id}/actualizar', [CpuEvidenciaController::class, 'actualizarEvidencia']);
    Route::delete('/evidencia/{id}/eliminar', [CpuEvidenciaController::class, 'eliminarEvidencia']);
    Route::get('/evidencia', [CpuEvidenciaController::class, 'consultarEvidencias']);
    Route::get('obtener-informacion/{ano}', [CpuEvidenciaController::class, 'obtenerInformacionPorAno']);
    Route::get('descargar-archivo/{ano}/{archivo}', [CpuEvidenciaController::class, 'descargarArchivo'])->name('descargar-archivo');

    //becados
    Route::get('consultar-becado/{identificacion}/{periodo}', [CpuBecadoController::class, 'consultarPorIdentificacionYPeriodo']);
    Route::post('generar-plantilla-becados', [CpuBecadoController::class, 'generarExcel']);
    Route::post('cargar-becados', [CpuBecadoController::class, 'importarExcel']);
    Route::get('qr-code/{identificacion}/{periodo}', [CpuBecadoController::class, 'generarQRCode']);
    Route::get('/consultar-por-codigo-tarjeta/{codigoTarjeta}', [CpuBecadoController::class, 'consultarPorCodigoTarjeta']);
    Route::get('/cpu_becados', [CpuBecadoController::class, 'index']);
    Route::put('/cpu_becado/actualizarCodigoTarjeta/{identificacion}', [CpuBecadoController::class, 'actualizarCodigoTarjeta']);

    Route::post('/registrar-consumo', [CpuConsumoBecadoController::class, 'registrarConsumo']);
    Route::get('registros-por-fechas/{fechaInicio}/{fechaFin}', [CpuConsumoBecadoController::class, 'registrosPorFechas']);
    Route::get('detalle-registros/{fechaInicio}/{fechaFin}', [CpuConsumoBecadoController::class, 'detalleRegistros']);
    Route::get('detalle-registros/{fechaInicio}/{fechaFin}', [CpuConsumoBecadoController::class, 'detalleRegistros']);
    Route::get('/becados/resumen', [CpuConsumoBecadoController::class, 'obtenerResumenBecados']);
    Route::get('/becados/periodos', [CpuConsumoBecadoController::class, 'obtenerPeriodos']);

    Route::get('/obtener-cie10', [CpuAtencionPsicologiaController::class, 'obtenerCie10']);
    // routes/api.php
    //Route::post('/agregarTurnos', [TurnosController::class, 'agregarTurnos']);
    Route::post('/generar-turno', [TurnosController::class, 'generarTurnos']);

    Route::post('/turnos', [TurnosController::class, 'listarTurnos']); // Cambiar a POST
    Route::post('/turnos/eliminar', [TurnosController::class, 'eliminarTurno']);
    // Listar turnos por funcionario
    Route::get('/turnos/funcionario', [TurnosController::class, 'listarTurnosPorFuncionario']);

    Route::post('/turnos/actualizar', [TurnosController::class, 'reservarTurno']);


    //rutas para registros medico ocupaconal
    Route::get('/cpu-persona/{cedula}', [CpuPersonaController::class, 'show']);
    // Route::put('/cpu-persona-update/{cedula}', [CpuPersonaController::class, 'update']);
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

    // Buscar funcionario por rol
    Route::get('/users/buscarfuncionariorol', [UsuarioController::class, 'buscarfuncionariorol']);


    //guardar atenciones
    Route::post('/atenciones/guardar', [CpuAtencionesController::class, 'guardarAtencion']);
    Route::post('/atenciones/triaje', [CpuAtencionesController::class, 'guardarAtencionConTriaje']);
    // Route::get('/atenciones/{id_persona}/{id_funcionario}', [CpuAtencionesController::class, 'obtenerAtencionesPorPaciente']);
    Route::get('/atenciones/{id_persona}/{id_funcionario}/{usr_tipo?}', [CpuAtencionesController::class, 'obtenerAtencionesPorPaciente']);

    // Cambiar la ruta a PUT en lugar de DELETE
    Route::put('/atencionesEliminar/{atencionId}/{nuevoEstado}', [CpuAtencionesController::class, 'eliminarAtencion']);
    Route::post('/atencion/nutricion', [CpuAtencionesController::class, 'guardarAtencionNutricion']);

    //atenciones Triaje
    Route::get('/triaje/talla-peso/{id_paciente}', [CpuAtencionTriajeController::class, 'obtenerTallaPesoPaciente']);
    Route::get('/triaje/datos', [CpuAtencionTriajeController::class, 'obtenerDatosTriajePorDerivacion']);

    //agregar derivación
    Route::post('/derivaciones/guardar', [CpuDerivacionController::class, 'store']);
    Route::get('/derivaciones/filtrar', [CpuDerivacionController::class, 'getDerivacionesByDoctorAndDate']);
    Route::get('/derivaciones/all', [CpuDerivacionController::class, 'getDerivacionesAll']);
    Route::post('/derivaciones/update', [CpuDerivacionController::class, 'updateDerivacion']);
    Route::post('/reagendar', [CpuDerivacionController::class, 'reagendar']);
    Route::put('/derivaciones/no-asistio/{id}', [CpuDerivacionController::class, 'noAsistioCita']);

    //datos del valor de consumo por dia para becas
    Route::get('cpu-valor-consumo-diario-beca', [CpuValorConsumoDiarioBecaController::class, 'consultar']);
    Route::post('cpu-valor-consumo-diario-beca', [CpuValorConsumoDiarioBecaController::class, 'editar']);

    //evaluación de competencias
    Route::get('/evaluaciones', [CpuAspirantesEvaluacionesController::class, 'getEvaluaciones']);
    Route::get('/evaluaciones-cedula', [CpuAspirantesEvaluacionesController::class, 'getEvaluacionesCedula']);
    Route::post('/actualizar-asistencia', [CpuAspirantesEvaluacionesController::class, 'updateAsistencia']);

    //actualizar datos personales
    // Route::put('/persona/{cedula}', [CpuPersonaController::class, 'updateDatosPersonales']);
    Route::put('/cpu_personas/{cedula}', [CpuPersonaController::class, 'update']);

    //api para guardar datos sociales en registro
    Route::post('/guardarDatosSociales', [CpuDatosSocialesController::class, 'store']);
    Route::post('/persona/{cedula}', [CpuPersonaController::class, 'updateDatosPersonales']);

    //apis para tipos de usuarios
    Route::post('/cpu-tipos-usuario', [CpuTipoUsuarioController::class, 'store']);
    Route::get('/cpu-tipos-usuario', [CpuTipoUsuarioController::class, 'index']);
    Route::get('/cpu-tipos-usuario/{id}', [CpuTipoUsuarioController::class, 'show']);
    Route::get('/cpu-tipos-usuarioN/{tipo_usu}', [CpuTipoUsuarioController::class, 'filtrotipousuario']);
    Route::put('/cpu-tipos-usuario/{id}', [CpuTipoUsuarioController::class, 'update']);
    Route::delete('/cpu-tipos-usuario/{id}', [CpuTipoUsuarioController::class, 'destroy']);

    //INSUMOS
    Route::get('/cpu-insumos', [CpuInsumoController::class, 'getInsumos']);
    Route::get('/get-insumo', [CpuInsumoController::class, 'consultarInsumos']);
    Route::get('/get-tipo-insumo', [CpuInsumoController::class, 'consultarTiposInsumos']);
    Route::post('/guardar-insumo', [CpuInsumoController::class, 'saveInsumos']);
    Route::put('/modificar-insumo/{id}', [CpuInsumoController::class, 'modificarInsumo']);
    Route::get('/get-insumo-id/{id}', [CpuInsumoController::class, 'getInsumoById']);


    //apis para busqueda de cie11
    Route::post('/get-token', [ICDController::class, 'getToken']);
    Route::get('/search', [ICDController::class, 'searchICD']);

    //funciones para administrar el consumo de insumos medicos
    Route::post('/insumos_ocupados', [CpuInsumoOcupadoController::class, 'store']);
    Route::get('/insumos_ocupados/fechas', [CpuInsumoOcupadoController::class, 'getByDateRange']);
    Route::get('/insumos_ocupados/funcionario/{id_funcionario}', [CpuInsumoOcupadoController::class, 'getByFuncionario']);
    Route::get('/insumos_ocupados/paciente/{id_paciente}', [CpuInsumoOcupadoController::class, 'getByPaciente']);

    //MODULO DE PSICOLOGIA
    Route::post('/atenciones-psicologia', [CpuAtencionPsicologiaController::class, 'store']);
    Route::get('/casos/{tipo_atencion}/{usr_tipo}/{id_persona}', [CpuCasosPsicologiaController::class, 'getCasos']);
    Route::get('/ultima-consulta/{area_atencion}/{usr_tipo}/{id_persona}/{id_caso}', [CpuAtencionesController::class, 'obtenerUltimaConsulta']);
    Route::post('/atenciones/triajesico', [CpuAtencionPsicologiaController::class, 'guardarAtencionConTriaje']);
    Route::post('/atenciones/updatederivacionsico', [CpuAtencionPsicologiaController::class, 'actulizarderivacionsico']);
    Route::get('/obtener-cie10', [CpuAtencionPsicologiaController::class, 'obtenerCie10']);
    Route::get('/obtener-cie10', [CpuAtencionPsicologiaController::class, 'obtenerCie10']);

    // Ruta para certificados
    Route::get('/certificados/{periodo_certificado}/{sede}/{carrera}', [CpuCertificadoNivelacionController::class, 'datosCertificado']);
    Route::get('/sedes-periodo-certificado/{periodo}', [CpuCertificadoNivelacionController::class, 'getSedesByPeriodo']);
    Route::get('/carreras-certificado/{periodo_certificado}/{sede}', [CpuCertificadoNivelacionController::class, 'getCarrerasByPeriodoAndSede']);

    // Rutas para CpuDatosMedicos

    Route::post('/datos-medicos', [CpuDatosMedicosController::class, 'store']);
    Route::get('/datos-medicos/{id_persona}', [CpuDatosMedicosController::class, 'show']);
    Route::patch('/datos-medicos/{id}', [CpuDatosMedicosController::class, 'update'])->withoutMiddleware(['csrf']);
    Route::delete('/datos-medicos/{id}', [CpuDatosMedicosController::class, 'destroy']);

    //atenciones medicina general
    Route::post('/atenciones-medicina-general', [CpuAtencionesController::class, 'guardarAtencionMedicinaGeneral']);
    Route::get('/dientes/{id_paciente}', [CpuDienteController::class, 'buscarPorPaciente']);
    // Ruta para guardar atención odontológica
    Route::post('/atenciones-odontologicas', [CpuAtencionOdontologiaController::class, 'store']);

    // historia clinica
    Route::get('/historia-clinica/{id_paciente}', [CpuAtencionesController::class, 'historiaClinica']);

    //agregar usuarios externos
    Route::post('/usuarios/externos', [CpuPersonaController::class, 'store']);
    //reporte para obtener valores unicos de cada campo requerido
    Route::get('/valores-unicos', [ReporteController::class, 'getAllUnifiedUniqueValuesForSelects']);
    // API para obtener el total de atenciones por fecha
    Route::post('/reporte/total-atenciones-por-fecha', [ReporteController::class, 'getTotalAtencionesPorFecha']);
    Route::post('/reporte/total-atenciones-por-areas', [ReporteController::class, 'getTotalAtencionesPorArea']);
    Route::post('/reporte/total-atenciones-por-sede', [ReporteController::class, 'getTotalAtencionesPorSedes']);

    // API para guardar o actualizar datos sociales
    Route::post('/datos-sociales', [CpuDatosSocialesController::class, 'store']);
    Route::post('/datos-sociales/{id_persona}', [CpuDatosSocialesController::class, 'updateByPersonaId']);
    Route::get('/datos-sociales/{id_persona}', [CpuDatosSocialesController::class, 'show']);

    // API para guardar atenciones de trabajo social
    Route::post('/atenciones-trabajo-social', [CpuAtencionesTrabajoSocialController::class, 'store']);
    Route::post('/atenciones-trabajo-social/upload', [CpuAtencionesTrabajoSocialController::class, 'update']);

    // API para obtener cargos
    Route::get('/cargos', [CpuCargoController::class, 'index']);
    // API para obtener direcciones administrativas
    Route::get('/direcciones-administrativas', [CpuDirAdminController::class, 'index']);

    // API para guardar tramites
    Route::post('/tramites', [CpuTramiteController::class, 'create']);
    Route::put('/tramites/{id}', [CpuTramiteController::class, 'update']);
    Route::delete('/tramites/{id}', [CpuTramiteController::class, 'destroy']);
    Route::get('/tramites', [CpuTramiteController::class, 'show']);
    // NUEVOS endpoints optimizados:
    Route::get('/tramites/hoy', [CpuTramiteController::class, 'hoy']);
    Route::get('/tramites/no-finalizados', [CpuTramiteController::class, 'noFinalizados']);
    // API para obtener tipos de becas filtrados
    Route::get('/tipos-beca/filtrados', [CpuTipoBecaController::class, 'show']);

    // API para guardar atenciones de tramites
    Route::post('/atenciones-tramites', [CpuAtencionesController::class, 'guardarAtencionTramites']);

    // Enviar correo de atención de admisión de salud
    Route::post('/enviar-correo-admision-salud', [CpuCorreoEnviadoController::class, 'enviarCorreoAtencionAdmisionSalud']);
    Route::post('/enviar-correo-derivacion-funcionario', [CpuCorreoEnviadoController::class, 'enviarCorreoDerivacionAreaSaludFuncionario']);
    Route::post('/enviar-correo-derivacion-paciente', [CpuCorreoEnviadoController::class, 'enviarCorreoDerivacionAreaSaludPaciente']);
    Route::post('/enviar-correo-atencion-paciente', [CpuCorreoEnviadoController::class, 'enviarCorreoAtencionAreaSaludPaciente']);

    Route::get('/ultima-consulta-fisioterapia/{area_atencion}/{usr_tipo}/{id_persona}/{id_caso}', [CpuAtencionesFisioterapiaContoller::class, 'obtenerUltimaConsultaFisioterapia']);

    //API para guardar atenciones fisioterapia
    Route::post('/atenciones-fisioterapia', [CpuAtencionesFisioterapiaContoller::class, 'guardarAtencionFisioterapia']);
    Route::get('/ultima-consulta-fisioterapia/{area_atencion}/{usr_tipo}/{id_persona}/{id_caso}', [CpuAtencionesFisioterapiaContoller::class, 'obtenerUltimaConsultaFisioterapia']);
    Route::get('/resumen-agenda-fisioterapia', [CpuResumenAgendaFisioterapiaController::class, 'resumen']);

    //API  - secretaria de direccion
    Route::post('/usuarios/externos/secretaria', [CpuPersonaController::class, 'store']);

    //Taller costura
    Route::post('/pedidos-costura', [CpuPedidoCosturaController::class, 'store']);
    Route::get('/pedidos-costura', [CpuPedidoCosturaController::class, 'index']);
    Route::put('/pedidos-costura/{id}', [CpuPedidoCosturaController::class, 'update']);

    //profesiones
    Route::post('/agregar-profesion', [CpuProfesionController::class, 'agregarProfesion']);
    Route::put('/modificar-profesion/{id}', [CpuProfesionController::class, 'modificarProfesion']);
    Route::delete('/eliminar-profesion/{id}', [CpuProfesionController::class, 'eliminarProfesion']);
    Route::get('/consultar-profesiones', [CpuProfesionController::class, 'consultarProfesiones']);

    //Secretaria  Matriculas
    Route::get('/consultar-secretaria-matricula', [SecretariasMatriculasControllers::class, 'consultarSecretariasMatriculas']);
    Route::post('/agregar-secretaria-matricula', [SecretariasMatriculasControllers::class, 'agregarSecretariaMatricula']);

    //Productos
    Route::get('/get-producto', [ProductosControllers::class, 'consultarProductos']);


    //Proveedores
    Route::get('/get-proveedor', [ProveedoresControllers::class, 'consultarProveedores']);
    Route::post('/guardar-proveedor', [ProveedoresControllers::class, 'guardarProveedores']);
    Route::put('/modificar-proveedor/{id}', [ProveedoresControllers::class, 'modificarProveedores']);

    //Categoria de Activos
    Route::get('/get-categoria-activo', [CategoriaActivosControllers::class, 'consultarCategoriaActivos']);
    Route::post('/guardar-categoria-activo', [CategoriaActivosControllers::class, 'guardarCategoriaActivos']);
    Route::put('/modificar-categoria-activo/{id}', [CategoriaActivosControllers::class, 'modificarCategoriaActivos']);

    //Ingresos
    Route::get('/get-ingreso', [IngresosControllers::class, 'consultarIngresos']);
    Route::post('/guardar-ingreso-activo', [IngresosControllers::class, 'guardarIngresos']);
    //Route::post('/guardar-ingreso-activo-p', [IngresosControllers::class, '/get-ingreso']);
    Route::get('/get-nro-ingreso', [IngresosControllers::class, 'getIdNumeroIngreso']);
    Route::get('/get-ingreso-id/{id}', [IngresosControllers::class, 'getConsultarIngresosId']);
    Route::post('ruta-comprobante-ingreso-id', [IngresosControllers::class, 'descargarComprobanteIngresosId']);
    Route::get('/get-kardex-movimiento', [IngresosControllers::class, 'getKardexMovimiento']);

    //Egresos
    Route::get('/get-egreso', [EgresosControllers::class, 'consultarEgresos']);
    Route::get('/get-egreso-id/{id}', [EgresosControllers::class, 'getConsultarEgresosId']);
    Route::post('/guardar-atencion-egreso', [EgresosControllers::class, 'guardarAtencionEgreso']);
    //PROFESIONES

    Route::post('/consultar-profesiones ', [CpuProfesionController::class, 'consultarProfesiones']);

    //API
    Route::get('/api-tipo-analisis', [ApiControllers::class, 'ApiConsultarTiposAnalisis']);

    //AORDEN DE ANALISIS
    Route::get('/get-orden-analisis', [OrdenesAnalisisControllers::class, 'ConsultarOrdenAnalisis']);
    Route::get('/get-orden-analisis-cedula/{cedula}', [OrdenesAnalisisControllers::class, 'ConsultarOrdenAnalisisCedula']);
    Route::post('/guardar-orden-analisis', [OrdenesAnalisisControllers::class, 'GuardarOrdenAnalisis']);


    //TIPOS DE ANALISIS
    Route::get('/get-tipo-analisis', [TiposAnalisisControllers::class, 'ConsultarTiposAnalisisOrion']);
    Route::get('/get-tipo-analisis/{id}', [TiposAnalisisControllers::class, 'ConsultarTiposAnalisisOrionId']);

    // Periodos NV
    Route::get('/nv/periodos',            [NvPeriodosController::class, 'index']);
    Route::get('/nv/periodos/cpu',        [NvPeriodosController::class, 'cpuPeriodos']); // para el select
    Route::post('/nv/periodos',            [NvPeriodosController::class, 'store']);
    Route::put('/nv/periodos/{id}',       [NvPeriodosController::class, 'update']);
    Route::put('/nv/periodos/{id}/toggle', [NvPeriodosController::class, 'toggleActivo']);
    Route::post('/nv/periodos/bulk',       [NvPeriodosController::class, 'bulkUpsert']);

    // Docentes
    Route::get('/nv/docentes',                 [NvDocentesController::class, 'index']);
    Route::post('/nv/docentes',                [NvDocentesController::class, 'store']);
    Route::put('/nv/docentes/{id}',            [NvDocentesController::class, 'update']);        // ⬅️ NUEVA
    Route::patch('/nv/docentes/{id}/toggle',   [NvDocentesController::class, 'toggle']);        // ⬅️ NUEVA
    Route::post('/nv/docentes/bulk',           [NvDocentesController::class, 'bulkUpsert']);
    Route::get('/nv/usuarios/buscar',          [NvDocentesController::class, 'buscarUsuarios']);
    Route::get('/nv/docentes/buscar',          [NvDocentesController::class, 'buscarDocentes']);
    Route::post('/nv/docentes/desde-usuario',  [NvDocentesController::class, 'crearDesdeUsuario']);

    // Asignaturas
    // Asignaturas
    Route::get('/nv/asignaturas',             [NvAsignaturasController::class, 'index']);
    Route::post('/nv/asignaturas',            [NvAsignaturasController::class, 'store']);
    Route::put('/nv/asignaturas/{id}',        [NvAsignaturasController::class, 'update']);
    Route::patch('/nv/asignaturas/{id}/toggle', [NvAsignaturasController::class, 'toggle']);
    Route::post('/nv/asignaturas/bulk',       [NvAsignaturasController::class, 'bulkUpsert']);
    Route::delete('/nv/asignaturas/{id}',     [NvAsignaturasController::class, 'destroy']); // opcional

    // Paralelos
    Route::get('/nv/paralelos',              [NvParalelosController::class, 'index']);
    Route::post('/nv/paralelos',              [NvParalelosController::class, 'store']);
    Route::post('/nv/paralelos/bulk',         [NvParalelosController::class, 'bulkUpsert']);
    Route::put('/nv/paralelos/{id}',         [NvParalelosController::class, 'update']);   // <-- NUEVA
    Route::put('/nv/paralelos/{id}/toggle',  [NvParalelosController::class, 'toggle']);


    // Docente–Asignatura (por periodo y paralelo)
    Route::get('/nv/docente-asignatura',           [NvDocenteAsignaturaController::class, 'index']);
    Route::post('/nv/docente-asignatura',           [NvDocenteAsignaturaController::class, 'store']);
    Route::post('/nv/docente-asignatura/bulk',      [NvDocenteAsignaturaController::class, 'bulkUpsert']);
    Route::put('/nv/docente-asignatura/{id}',      [NvDocenteAsignaturaController::class, 'update']);
    Route::delete('/nv/docente-asignatura/{id}',      [NvDocenteAsignaturaController::class, 'destroy']);

    // Diversidad – Entrevistas (histórico por atención)
    Route::get('/diversidad/entrevistas',             [AtencionesDiversidadController::class, 'index']);
    Route::get('/diversidad/entrevistas/{id}',        [AtencionesDiversidadController::class, 'show']);
    Route::post('/diversidad/entrevistas',            [AtencionesDiversidadController::class, 'store']);
    Route::put('/diversidad/entrevistas/{id}',        [AtencionesDiversidadController::class, 'update']);
    Route::patch('/diversidad/entrevistas/{id}',      [AtencionesDiversidadController::class, 'update']);
    // Route::delete('/diversidad/entrevistas/{id}',   [AtencionesDiversidadController::class, 'destroy']); // opcional si habilitas borrado

    // Casos Médicos
    Route::get('/casos-abiertos/{id_funcionario}', [CpuCasosMedicosController::class, 'getCasosAbiertos']);


    // Diversidad – Prefetch (última entrevista + conteos + salud)
    Route::get('/diversidad/prefetch', [AtencionesDiversidadController::class, 'prefetch']);

    // Diversidad – Actualizar salud (maestro cpu_datos_medicos)
    Route::put('/diversidad/salud', [AtencionesDiversidadController::class, 'actualizarSalud']);
    // Diversidad – listas auxiliares y segmento
    Route::get('/diversidad/carreras', [AtencionesDiversidadController::class, 'listarCarrerasDistinct']);
    Route::get('/diversidad/personas/{personaId}/ultima-carrera', [AtencionesDiversidadController::class, 'ultimaCarreraDePersona']);
    Route::get('/diversidad/segmento', [AtencionesDiversidadController::class, 'resolverSegmentoPorCedula']);
    Route::put('/diversidad/personas/{personaId}/segmento', [AtencionesDiversidadController::class, 'actualizarSegmentoPersona']);

    //MODULO DE TERAPIA DE LENGUAJE
    Route::get('/terapia-lenguaje', [CpuTerapiaLenguajeController::class, 'guardarConsultaTerapia']);

    Route::get('/datos-medicos', [CpuDatosMedicosController::class, 'index']);
    Route::get('/roles/{id}', [RoleController::class, 'consultarRol']);


    // Carrera
    Route::post('/agregar-carrera', [CpuCarreraController::class, 'agregarCarrera']);
    Route::put('/modificar-carrera/{id}', [CpuCarreraController::class, 'modificarCarrera']);
    Route::delete('/eliminar-carrera/{id}', [CpuCarreraController::class, 'eliminarCarrera']);

    Route::get('/consultar-carreras', [CpuCarreraController::class, 'consultarCarreras']);
    Route::get('/get-carrera', [CpuCarreraController::class, 'getCarreras']);


    // Bodegas
    Route::get('/consultar-bodega/{idSede}/{idFacultad}', [CpuBodegasController::class, 'getBodegas']);
    Route::get('/get-bodega-id/{idSede}/{idFacultad}/{idBodega}', [CpuBodegasController::class, 'getIdBodegas']);

    //Inventario
   // Route::get('/get-inventario', [CpuInventarioController::class, 'consultarInventario']);
    Route::post('/guardar-inventario-inicial', [CpuInventariosController::class, 'guardarInventarioInicial']);
    Route::get('/get-stock-bodega-insumo-id/{id}', [CpuInventariosController::class, 'getStockBodegaInsumoId']);
    // Route::put('/modificar-inventario/{id}', [CpuInventarioController::class, 'modificarInventario']);
});

// Route::put('/cpu-persona-update/{cedula}', [CpuPersonaController::class, 'update']);
