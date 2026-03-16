<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductoController;
use App\Http\Controllers\UsuarioController;
use App\Http\Controllers\TipoDocumentoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\MarcaController;
use App\Http\Controllers\CarritoController;
use App\Http\Controllers\VentaController;
use App\Http\Controllers\CompraController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\ProveedorController;
use App\Http\Controllers\TallaController;
use App\Http\Controllers\ColorController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\PermisoController;
use App\Http\Controllers\SolicitudAccesoController;
use App\Http\Controllers\NotificacionController;
use Illuminate\Support\Facades\Route;

//
// RUTAS PÚBLICAS (sin autenticación)
//

// Ruta de prueba
Route::get('test', function () {
    return response()->json(['message' => 'API funcionando correctamente']);
});

// Ruta contraseña olvidar
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Autenticación con JWT
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Catálogos públicos para formularios de registro
Route::get('TiposDocumento', [TipoDocumentoController::class, 'getAllTiposDocumento']);

//
// RUTAS PROTEGIDAS - Requieren autenticación con JWT
//
Route::middleware('jwt.auth')->group(function () {

    //
    // RUTAS DE AUTENTICACIÓN
    //
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('me', [AuthController::class, 'getUser']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    //
    // RUTAS PARA TODOS LOS USUARIOS AUTENTICADOS
    //

    // Productos - Ver productos con variantes
    Route::apiResource('productos', ProductoController::class);
    
    // Catálogos - Para formularios y filtros
    Route::get('categorias', [CategoriaController::class, 'getAllCategorias']);
    
    // Carrito - Gestión del carrito de compras
    Route::prefix('carrito')->group(function () {
        Route::get('/', [CarritoController::class, 'getCarrito']);
        Route::post('/', [CarritoController::class, 'addToCarrito']);
        Route::put('{id}', [CarritoController::class, 'updateCarritoItem']);
        Route::delete('{id}', [CarritoController::class, 'removeFromCarrito']);
        Route::delete('/', [CarritoController::class, 'clearCarrito']);
    });

    // Ventas - Los usuarios pueden ver sus propias ventas
    Route::get('/ventas', [VentaController::class, 'index']);
    Route::post('/ventas', [VentaController::class, 'store']);
    Route::get('/ventas/{id}', [VentaController::class, 'show']);
    Route::put('/ventas/{id}', [VentaController::class, 'update']);
    Route::delete('/ventas/{id}', [VentaController::class, 'destroy']);
    Route::post('/ventas/procesar-carrito', [VentaController::class, 'procesarVentaDesdeCarrito']);

    // Módulos - Disponibles para usuarios autenticados
    Route::get('/modulos', [ModuloController::class, 'index']);
    Route::get('/modulos/mis-modulos', [ModuloController::class, 'modulosUsuario']);

    // PERMISOS del usuario autenticado
    Route::get('/permisos/mis-permisos', [PermisoController::class, 'misPermisos']);

    // SOLICITUDES DE ACCESO
    Route::post('/solicitudes', [SolicitudAccesoController::class, 'solicitarAcceso']);
    Route::get('/solicitudes/mis-solicitudes', [SolicitudAccesoController::class, 'misSolicitudes']);

    // NOTIFICACIONES
    Route::get('/notificaciones', [NotificacionController::class, 'misNotificaciones']);
    Route::put('/notificaciones/{idNotificacion}/leida', [NotificacionController::class, 'marcarLeida']);
    Route::put('/notificaciones/marcar-todas-leidas', [NotificacionController::class, 'marcarTodasLeidas']);
    Route::delete('/notificaciones/{idNotificacion}', [NotificacionController::class, 'eliminar']);

    // 👥 Usuarios autenticados pueden ver proveedores (solo lectura)
    Route::get('/proveedores', [ProveedorController::class, 'getAllProveedores']);
    Route::get('/proveedores/{id}', [ProveedorController::class, 'getProveedorById']);

    // 👥 Usuarios autenticados pueden ver compras (solo lectura)
    Route::get('/compras', [CompraController::class, 'getCompras']);
    Route::get('/compras/{id}', [CompraController::class, 'getCompraById']);

    // ✅ REPORTE DEFINICIÓN ÚNICA (Solo se usa el control de acceso interno del ReportesController)
    Route::prefix('reportes')->group(function () {
        Route::get('/dashboard', [ReportesController::class, 'getDashboard']);
        Route::get('/ventas', [ReportesController::class, 'getReporteVentas']);
        Route::get('/productos-mas-vendidos', [ReportesController::class, 'getProductosMasVendidos']);
        // Se añade la ruta que faltaba
        Route::get('/por-marca', [ReportesController::class, 'getReportePorMarca']);
    });


    //
    // RUTAS SOLO PARA ADMINISTRADORES
    //
    Route::middleware('check.admin')->group(function () {
        
        // PERMISOS
        Route::get('/permisos/{numeroDocumento}', [PermisoController::class, 'getPermisosUsuario']);
        Route::post('/permisos/asignar', [PermisoController::class, 'asignarPermisos']);

        // SOLICITUDES
        Route::get('/solicitudes/pendientes', [SolicitudAccesoController::class, 'solicitudesPendientes']);
        Route::get('/solicitudes/todas', [SolicitudAccesoController::class, 'todasLasSolicitudes']);
        Route::put('/solicitudes/{idSolicitud}/responder', [SolicitudAccesoController::class, 'responderSolicitud']);

        // Proveedores (CRUD completo solo admin)
        Route::prefix('proveedores')->group(function () {
            Route::post('/', [ProveedorController::class, 'addProveedor']);
            Route::put('{id}', [ProveedorController::class, 'updateProveedor']);
            Route::delete('{id}', [ProveedorController::class, 'deleteProveedor']);
        });

        // Compras (CRUD completo solo admin)
        Route::prefix('compras')->group(function () {
            Route::post('/', [CompraController::class, 'addCompra']);
            Route::put('{id}', [CompraController::class, 'updateCompra']);
            Route::delete('{id}', [CompraController::class, 'deleteCompra']);
        });

        // Inventario
        Route::prefix('inventario')->group(function () {
            Route::get('/', [InventarioController::class, 'getInventario']);
            Route::post('ajustar-stock', [InventarioController::class, 'ajustarStock']);
            Route::get('movimientos', [InventarioController::class, 'getMovimientos']);
        });

        // NOTA: Se eliminó el bloque de 'reportes' duplicado de aquí.
        
        // Gestión de productos
        Route::prefix('products')->group(function () {
            Route::post('/', [ProductoController::class, 'addProduct']);
            Route::get('{id}', [ProductoController::class, 'getProductById']);
            Route::put('{id}', [ProductoController::class, 'updateProductById']);
            Route::delete('{id}', [ProductoController::class, 'deleteProductById']);
        });

        // Gestión de usuarios
        Route::prefix('users')->group(function () {
            Route::get('/', [UsuarioController::class, 'getAllUsers']);
            Route::post('/', [UsuarioController::class, 'createUser']);
            Route::get('{id}', [UsuarioController::class, 'getUserById']);
            Route::put('{id}', [UsuarioController::class, 'updateUserById']);
            Route::delete('{id}', [UsuarioController::class, 'deleteUserById']);
        });

        // Gestión de categorías
        Route::prefix('categorias')->group(function () {
            Route::post('/', [CategoriaController::class, 'addCategoria']);
            Route::put('{id}', [CategoriaController::class, 'updateCategoria']);
            Route::delete('{id}', [CategoriaController::class, 'deleteCategoria']);
        });
        
    });
});
