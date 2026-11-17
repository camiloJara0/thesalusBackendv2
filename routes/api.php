<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
use App\Http\Controllers\EpsController;
use App\Http\Controllers\ProfesionController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfesionalController;
use App\Http\Controllers\SeccionesController;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\HistoriaClinicaController;
use App\Http\Controllers\AnalisisController;
use App\Http\Controllers\ExamenFisicoController;
use App\Http\Controllers\PlanManejoMedicamentoController;
use App\Http\Controllers\PlanManejoProcedimientoController;
use App\Http\Controllers\PlanManejoEquipoController;
use App\Http\Controllers\PlanManejoInsumoController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\AntecedenteController;
use App\Http\Controllers\DiagnosticoController;
use App\Http\Controllers\DiagnosticoRelacionadoController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\DescripcionNotaController;
use App\Http\Controllers\SoftwareController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\EnfermedadController;
use App\Http\Controllers\InformacionUserController;
use App\Http\Controllers\TerapiaController;
use App\Http\Controllers\ServicioController;
use App\Http\Controllers\Cie10Controller;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InsumoController;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\KardexController;
use App\Http\Controllers\HistorialCambioSondaController;
use App\Http\Controllers\ProfesionalHasPermisosController;
use App\Http\Controllers\CeldaColorController;
use App\Http\Controllers\VadecumController;
use App\Http\Controllers\ConvenioController;
use App\Http\Controllers\TipoEquipoController;
use App\Http\Controllers\HistorialInsumoprestadoController;
use App\Http\Controllers\Backoffice\PanelController;
use App\Http\Controllers\Backoffice\ErroresController;
use App\Http\Controllers\Backoffice\TrabajosController;
use App\Http\Controllers\Backoffice\AuditoriaController;
use App\Http\Controllers\Backoffice\UsuariosController;
use App\Http\Controllers\Backoffice\LimpiezaController;
use App\Http\Controllers\Backoffice\RespaldosController;

Route::post('/v1/login', [UserController::class, 'login'])->middleware('throttle:5,1');
Route::post('/v1/recuperarContraseña', [UserController::class, 'verificacion'])->middleware('throttle:3,1');
Route::post('/v1/cambiarContraseña', [UserController::class, 'verificarCodigo'])->middleware('throttle:5,1');
Route::post('/v1/cambiarContraseñaPrimerVez', [UserController::class, 'verificarCodigoPrimerVez'])->middleware('throttle:5,1');
Route::post('/v1/primerIngreso', [UserController::class, 'verificarUsuario'])->middleware('throttle:10,1');

Route::middleware(['auth:sanctum', 'check.token.expiration'])->group(function () {
        Route::apiResource('/v1/eps', EpsController::class);
        Route::apiResource('/v1/professions', ProfesionController::class);
        Route::apiResource('/v1/empresas', EmpresaController::class);
        Route::apiResource('/v1/users', UserController::class);
        Route::apiResource('/v1/profesionals', ProfesionalController::class);
        Route::apiResource('/v1/pacientes', PacienteController::class);
        Route::apiResource('/v1/informacionUsers', InformacionUserController::class);

        // Historias post diferentes servicios
        Route::apiResource('/v1/historiasClinicas', HistoriaClinicaController::class);
        Route::post('/v1/historiasClinicasNutricion', [HistoriaClinicaController::class, 'storeNutricion']);
        Route::post('/v1/historiasClinicasTrabajoSocial', [HistoriaClinicaController::class, 'storeTrabajoSocial']);
        Route::post('/v1/historiasClinicasNota', [HistoriaClinicaController::class, 'storeNota']);
        Route::apiResource('/v1/terapias', TerapiaController::class);

        Route::apiResource('/v1/analisis', AnalisisController::class);
        Route::apiResource('/v1/examenFisicos', ExamenFisicoController::class);
        Route::apiResource('/v1/planManejoMedicamentos', PlanManejoMedicamentoController::class);
        Route::apiResource('/v1/planManejoProcedimientos', PlanManejoProcedimientoController::class);
        Route::apiResource('/v1/planManejoEquipos', PlanManejoEquipoController::class);
        Route::apiResource('/v1/planManejoInsumos', PlanManejoInsumoController::class);
        Route::apiResource('/v1/antecedentes', AntecedenteController::class);
        Route::apiResource('/v1/diagnosticos', DiagnosticoController::class);
        Route::apiResource('/v1/diagnosticosCIF', DiagnosticoRelacionadoController::class);
        Route::apiResource('/v1/notas', NotaController::class);
        Route::apiResource('/v1/descripcionNotas', DescripcionNotaController::class);
        Route::apiResource('/v1/software', SoftwareController::class);
        Route::apiResource('/v1/facturaciones', FacturacionController::class);
        Route::apiResource('/v1/enfermedades', EnfermedadController::class);
        Route::apiResource('/v1/servicios', ServicioController::class);
        Route::apiResource('/v1/cie10', Cie10Controller::class);
        Route::apiResource('/v1/insumos', InsumoController::class);
        Route::apiResource('/v1/movimientos', MovimientoController::class);
        // Route::apiResource('/v1/kardex', KardexController::class);

        // Kardex dinámico
        Route::get('/v1/kardex/plantilla', [KardexController::class, 'plantillas']);
        Route::get('/v1/kardex/plantilla/{id}', [KardexController::class, 'plantillaCampos']);
        Route::get('/v1/kardex/registros/{idPaciente}', [KardexController::class, 'registrosPaciente']);
        Route::post('/v1/kardex/registrosPlantilla', [KardexController::class, 'registrosPlantilla']);
        Route::post('/v1/kardex/registros', [KardexController::class, 'guardarRegistros']);
        Route::post('/v1/kardex/historialCambioSonda', [KardexController::class, 'storeHistorialCambioSonda']);
        Route::post('/v1/kardex/campo', [KardexController::class, 'storeCampo']);
        Route::get('/v1/kardex/campo', [KardexController::class, 'getCampos']);
        Route::put('/v1/kardex/campo/{id}', [KardexController::class, 'updateCampo']);
        Route::delete('/v1/kardex/campo/{id}', [KardexController::class, 'destroyCampo']);
        Route::post('/v1/kardex/plantilla', [KardexController::class, 'storePlantilla']);
        Route::delete('/v1/kardex/plantilla/{id}', [KardexController::class, 'destroyPlantilla']);
        Route::put('/v1/kardex/plantilla/{id}', [KardexController::class, 'updatePlantilla']);
        Route::post('/v1/kardex/plantilla/{idPlantilla}/campo', [KardexController::class, 'addCampoPlantilla']);
        Route::put('/v1/kardex/plantilla/{idPlantilla}/campo', [KardexController::class, 'updateCampoPlantilla']);
        Route::delete('/v1/kardex/plantilla/{idPlantilla}/campo/{idCampo}', [KardexController::class, 'removeCampoPlantilla']);

        Route::apiResource('/v1/historialCambioSonda', HistorialCambioSondaController::class);
        Route::apiResource('/v1/profesionalHasPermisos', ProfesionalHasPermisosController::class);
        Route::apiResource('/v1/celdaColors', CeldaColorController::class);
        Route::apiResource('/v1/vadecum', VadecumController::class);
        Route::apiResource('/v1/convenios', ConvenioController::class);
        Route::apiResource('/v1/tipo_equipos', TipoEquipoController::class);
        Route::apiResource('/v1/prestaciones', HistorialInsumoprestadoController::class);
        
        Route::apiResource('/v1/citas', CitaController::class);
        Route::get('/v1/infoCitas', [CitaController::class, 'infoCitas']);
        Route::get('/v1/citasHoy', [CitaController::class, 'citasHoy']);
        Route::post('/v1/citasPorRango', [CitaController::class, 'citasPorRango']);
        Route::post('/v1/citasPaginadas', [CitaController::class, 'citasPaginadas']);
        Route::post('/v1/citasFiltradas', [CitaController::class, 'citasFiltradas']);
        Route::get('/v1/filtrosCitas', [CitaController::class, 'filtrosCitas']);
        Route::post('/v1/variasCitas', [CitaController::class, 'variasCitas']);

        Route::get('/v1/analisisInicial', [HistoriaClinicaController::class, 'analisisInicial']);
        Route::post('/v1/analisisPaciente', [HistoriaClinicaController::class, 'analisisPaciente']);
        Route::get('/v1/analisis/{id}/completa', [HistoriaClinicaController::class, 'analisisCompleta']);
        Route::post('/v1/analisisPaginado', [HistoriaClinicaController::class, 'analisisPaginado']);
        Route::post('/v1/analisisFiltrado', [HistoriaClinicaController::class, 'analisisFiltrado']);
        Route::get('/v1/filtrosAnalisis', [HistoriaClinicaController::class, 'filtrosAnalisis']);

        Route::post('/v1/diasAsignadosRestantes', [PlanManejoProcedimientoController::class, 'diasAsignadosRestantes']);
        Route::get('/v1/administradores', [UserController::class, 'administradores']);
        Route::get('/v1/secciones', [SeccionesController::class, 'index']);
        Route::get('/v1/dashboard', [DashboardController::class, 'dashboard']);
        Route::post('/v1/logout', [UserController::class, 'logout'])->middleware('throttle:3,1');

        // Traer grupos de tablas
        Route::get('/v1/traeDatosHistoria', [HistoriaClinicaController::class, 'traeDatosHistoria']);
        Route::post('/v1/pacientesConHistoria', [HistoriaClinicaController::class, 'pacientesConHistoria']);
        Route::get('/v1/traeDatosPlanManejo', [HistoriaClinicaController::class, 'traeDatosPlanManejo']);
        Route::get('/v1/miPerfil', [UserController::class, 'miPerfil']);
        Route::post('/v1/traePacientes', [PacienteController::class, 'traePacientes']);
        Route::get('/v1/pacientesInactivos', [PacienteController::class, 'pacientesInactivos']);
        Route::get('/v1/traeKardex', [PacienteController::class, 'traeKardex']);
        Route::get('/v1/traeProfesionales', [ProfesionalController::class, 'traeProfesionales']);
        Route::get('/v1/profesionalesInactivos', [ProfesionalController::class, 'profesionalesInactivos']);

        // Generacion de PDF
        Route::post('/v1/exportarPdf', [HistoriaClinicaController::class, 'exportarPdf']);
        Route::get('/exportaciones/capacidad', [HistoriaClinicaController::class, 'capacidadExport']);
        Route::post('/v1/exportar/async', [HistoriaClinicaController::class, 'exportarPdfAsync']);
        Route::get('/v1/exportar/{id}/estado', [HistoriaClinicaController::class, 'estadoExport']);
        Route::get('/v1/exportar/{id}/descarga', [HistoriaClinicaController::class, 'descargarExport']);
        Route::post('/v1/exportarExcel', [HistoriaClinicaController::class, 'exportarExcel']);
        Route::get('/v1/Nota/{id}/pdf', [NotaController::class, 'imprimir']);
        Route::get('/v1/Terapia/{id}/pdf', [TerapiaController::class, 'imprimir']);
        Route::get('/v1/Evolucion/{id}/pdf', [HistoriaClinicaController::class, 'imprimirEvolucion']);
        Route::get('/v1/Trabajo Social/{id}/pdf', [HistoriaClinicaController::class, 'imprimirTrabajoSocial']);
        Route::get('/v1/Medicina/{id}/pdf', [HistoriaClinicaController::class, 'imprimirMedicina']);
        Route::get('/v1/Formula/{id}/pdf', [PlanManejoMedicamentoController::class, 'imprimirFormulaMedica']);
        Route::get('/v1/Tratamiento/{id}/pdf', [PlanManejoMedicamentoController::class, 'imprimirTratamiento']);

        //Permisos
        Route::post('/v1/solicitarPermiso', [ProfesionalHasPermisosController::class, 'solicitarPermiso'])->middleware('throttle:3,1');
        Route::post('/v1/aprobarPermiso', [ProfesionalHasPermisosController::class, 'aprobarPermiso'])->middleware('throttle:10,1');
        Route::post('/v1/verificarPermisos', [ProfesionalHasPermisosController::class, 'verificarPermisos'])->middleware('throttle:30,1');
        Route::post('/v1/consumirPermiso', [ProfesionalHasPermisosController::class, 'consumirPermiso'])->middleware('throttle:10,1');

        //Importaciones
        Route::post('/v1/importarInsumos', [InsumoController::class, 'importar'])->middleware('throttle:5,1');
        Route::post('/v1/insumosPrestados', [MovimientoController::class, 'insumosPrestados']);
        Route::get('/v1/imprimirComoDato/{id}', [PlanManejoMedicamentoController::class, 'imprimirComodato']);
});

/*
|--------------------------------------------------------------------------
| Backoffice de desarrolladores (solo SuperAdmin)
|--------------------------------------------------------------------------
| Doble gate: backoffice.access (rol SuperAdmin) + permiso:Backoffice_*.
| Alta de SuperAdmin solo vía comando artisan superadmin:assign.
*/
Route::middleware(['auth:sanctum', 'check.token.expiration', 'backoffice.access'])
    ->prefix('v1/backoffice')
    ->group(function () {
        Route::get('/ping', [PanelController::class, 'ping'])->middleware('permiso:Backoffice_view');

        // Monitor de errores
        Route::get('/errores', [ErroresController::class, 'index'])->middleware('permiso:Backoffice_errores');
        Route::get('/errores/{id}', [ErroresController::class, 'show'])->middleware('permiso:Backoffice_errores');
        Route::delete('/errores', [ErroresController::class, 'destroy'])->middleware(['permiso:Backoffice_errores', 'throttle:30,1']);

        // Monitor de trabajos
        Route::get('/trabajos', [TrabajosController::class, 'index'])->middleware('permiso:Backoffice_trabajos');
        Route::get('/trabajos/failed/{id}', [TrabajosController::class, 'showFailed'])->middleware('permiso:Backoffice_trabajos');
        Route::post('/trabajos/failed/{id}/reintentar', [TrabajosController::class, 'retryFailed'])->middleware(['permiso:Backoffice_trabajos', 'throttle:30,1']);
        Route::get('/exportaciones', [TrabajosController::class, 'exportaciones'])->middleware('permiso:Backoffice_trabajos');

        // Auditoria
        Route::get('/auditoria', [AuditoriaController::class, 'index'])->middleware('permiso:Backoffice_auditoria');
        Route::get('/auditoria/{id}', [AuditoriaController::class, 'show'])->middleware('permiso:Backoffice_auditoria');

        // Metricas y salud
        Route::get('/metricas', [PanelController::class, 'metricas'])->middleware('permiso:Backoffice_metricas');
        Route::get('/salud', [PanelController::class, 'salud'])->middleware('permiso:Backoffice_salud');

        // Usuarios y tokens
        Route::get('/usuarios', [UsuariosController::class, 'index'])->middleware('permiso:Backoffice_usuarios');
        Route::get('/usuarios/{id}', [UsuariosController::class, 'show'])->middleware('permiso:Backoffice_usuarios');
        Route::post('/usuarios/{id}/tokens/purgar', [UsuariosController::class, 'purgarTokens'])->middleware(['permiso:Backoffice_usuarios', 'throttle:30,1']);

        // Limpieza
        Route::post('/limpieza/temporales', [LimpiezaController::class, 'temporales'])->middleware(['permiso:Backoffice_limpieza', 'throttle:30,1']);
        Route::post('/limpieza/logs', [LimpiezaController::class, 'logs'])->middleware(['permiso:Backoffice_limpieza', 'throttle:30,1']);
        Route::post('/limpieza/tokens', [LimpiezaController::class, 'tokens'])->middleware(['permiso:Backoffice_limpieza', 'throttle:30,1']);

        // Respaldos
        Route::get('/respaldos', [RespaldosController::class, 'index'])->middleware('permiso:Backoffice_respaldos');
        Route::post('/respaldos', [RespaldosController::class, 'store'])->middleware(['permiso:Backoffice_respaldos', 'throttle:30,1']);
        Route::get('/respaldos/{archivo}/descargar', [RespaldosController::class, 'descargar'])->middleware('permiso:Backoffice_respaldos');
    });