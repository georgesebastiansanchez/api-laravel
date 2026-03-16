<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use App\Models\DetalleVenta;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; 

class ReportesController extends Controller
{
    /**
     * Revisa si el usuario tiene rol de 'admin' o 'user'.
     *
     * Se implementa un doble chequeo: primero con la inyección personalizada
     * (auth_user) y luego con la fachada Auth de Laravel.
     * * @param Request $request
     * @return \Illuminate\Http\JsonResponse|null
     */
    private function checkEmployeeAccess(Request $request)
    {
       
        $user = $request->get('auth_user'); 

        
        if (!$user) {
            $user = Auth::user();
        }

        
        if (!$user || !in_array(strtolower($user->Role), ['admin','user'])) {
            // Si el token es válido pero el rol no es permitido, denegar.
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo empleados y administradores.'
            ], 403);
        }
        
        // Si todo es correcto, permite el acceso.
        return null;
    }

    public function getDashboard(Request $request)
    {
        // ✅ Todos los métodos ya llaman correctamente a checkEmployeeAccess($request)
        $employeeCheck = $this->checkEmployeeAccess($request);
        if ($employeeCheck) return $employeeCheck;
        try {
            $fechaDesde = $request->get('fecha_desde', now()->startOfMonth()->toDateString());
            $fechaHasta = $request->get('fecha_hasta', now()->toDateString());

            if (strtotime($fechaDesde) > strtotime($fechaHasta)) {
                $temp = $fechaDesde;
                $fechaDesde = $fechaHasta;
                $fechaHasta = $temp;
            }

            // Estadísticas de ventas
            $ventasStats = (object)[
                'total_ventas' => Venta::whereBetween('FechaVenta', [$fechaDesde, $fechaHasta])
                                           ->where('Estado', 'completada')
                                           ->count(),
                'ingresos_totales' => Venta::whereBetween('FechaVenta', [$fechaDesde, $fechaHasta])
                                               ->where('Estado', 'completada')
                                               ->sum('Total') ?? 0,
                'ticket_promedio' => Venta::whereBetween('FechaVenta', [$fechaDesde, $fechaHasta])
                                             ->where('Estado', 'completada')
                                             ->avg('Total') ?? 0
            ];

            // Productos más vendidos
            $ventasIds = Venta::whereBetween('FechaVenta', [$fechaDesde, $fechaHasta])
                                 ->where('Estado', 'completada')
                                 ->pluck('IdVenta')
                                 ->toArray();

            $productosPopulares = [];
            if (count($ventasIds) > 0) {
                $productosPopulares = DetalleVenta::selectRaw('IdProducto, SUM(Cantidad) as total_vendido, COALESCE(SUM(Total), 0) as ingresos_totales')
                                                 ->whereIn('IdVenta', $ventasIds)
                                                 ->groupBy('IdProducto')
                                                 ->orderBy('total_vendido', 'desc')
                                                 ->limit(5)
                                                 ->get()
                                                 ->map(function($item) {
                                                     $producto = Producto::find($item->IdProducto);
                                                     return [
                                                         'IdProducto' => $item->IdProducto,
                                                         'total_vendido' => $item->total_vendido,
                                                         'ingresos_totales' => $item->ingresos_totales,
                                                         'producto' => $producto ? [
                                                             'IdProducto' => $producto->IdProducto,
                                                             'Nombre' => $producto->Nombre,
                                                             'Marca' => $producto->Marca,
                                                             'Talla' => $producto->Talla,
                                                             'Color' => $producto->Color,
                                                             'PrecioBase' => $producto->PrecioBase,
                                                             'Stock' => $producto->Stock
                                                         ] : null
                                                     ];
                                                 });
            }

            // Inventario - Estadísticas simplificadas
            $totalProductos = Producto::where('Activo', true)->count();
            $sinStock = Producto::where('Activo', true)->where('Stock', 0)->count();
            $stockBajo = Producto::where('Activo', true)->where('Stock', '>', 0)->where('Stock', '<=', 5)->count();
            
            // Valor de inventario usando PrecioBase como costo de referencia
            $valorInventario = Producto::where('Activo', true)
                ->get()
                ->sum(function($producto) {
                    return $producto->Stock * $producto->PrecioBase;
                });

            $inventarioStats = [
                'total_productos' => $totalProductos,
                'sin_stock' => $sinStock,
                'stock_bajo' => $stockBajo,
                'valor_inventario' => $valorInventario
            ];

            // Ventas por día (últimos 7 días)
            $ventasPorDia = Venta::selectRaw('DATE(FechaVenta) as fecha, COUNT(*) as cantidad, COALESCE(SUM(Total), 0) as total')
                                             ->where('Estado', 'completada')
                                             ->whereBetween('FechaVenta', [now()->subDays(7), now()])
                                             ->groupBy(DB::raw('DATE(FechaVenta)'))
                                             ->orderBy('fecha', 'desc')
                                             ->get();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard obtenido exitosamente',
                'data' => [
                    'periodo' => ['desde' => $fechaDesde, 'hasta' => $fechaHasta],
                    'ventas' => $ventasStats,
                    'productos_populares' => $productosPopulares,
                    'inventario' => $inventarioStats,
                    'ventas_por_dia' => $ventasPorDia
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en getDashboard', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el dashboard',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function getReporteVentas(Request $request)
    {
        $employeeCheck = $this->checkEmployeeAccess($request); 
        if ($employeeCheck) return $employeeCheck;

        try {
            $fechaDesde = $request->get('fecha_desde', now()->startOfMonth()->toDateString());
            $fechaHasta = $request->get('fecha_hasta', now()->toDateString());

            if (strtotime($fechaDesde) > strtotime($fechaHasta)) {
                $temp = $fechaDesde;
                $fechaDesde = $fechaHasta;
                $fechaHasta = $temp;
            }

            $query = Venta::with(['detalles.producto' => function($q) {
                                         $q->select('IdProducto', 'Nombre', 'Marca', 'Talla', 'Color', 'PrecioBase');
                                     }])
                                     ->whereBetween('FechaVenta', [$fechaDesde, $fechaHasta]);

            if ($request->has('estado')) {
                $query->where('Estado', $request->estado);
            }

            if ($request->has('metodo_pago')) {
                $query->where('MetodoPago', $request->metodo_pago);
            }

            if ($request->has('marca')) {
                $query->whereHas('detalles.producto', function($q) use ($request) {
                    $q->where('Marca', $request->marca);
                });
            }

            $ventas = $query->orderBy('FechaVenta', 'desc')->paginate(20);

            $totales = (object)[
                'total_ventas' => Venta::whereBetween('FechaVenta', [$fechaDesde, $fechaHasta])
                                             ->where('Estado', 'completada')
                                             ->count(),
                'total' => Venta::whereBetween('FechaVenta', [$fechaDesde, $fechaHasta])
                                ->where('Estado', 'completada')
                                ->sum('Total') ?? 0
            ];

            return response()->json([
                'success' => true,
                'message' => 'Reporte de ventas obtenido exitosamente',
                'data' => [
                    'periodo' => ['desde' => $fechaDesde, 'hasta' => $fechaHasta],
                    'ventas' => $ventas,
                    'totales' => $totales
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en getReporteVentas: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el reporte de ventas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getProductosMasVendidos(Request $request)
    {
        $employeeCheck = $this->checkEmployeeAccess($request);
        if ($employeeCheck) return $employeeCheck;

        try {
            $fechaDesde = $request->get('fecha_desde', now()->startOfMonth()->toDateString());
            $fechaHasta = $request->get('fecha_hasta', now()->toDateString());
            $limite = $request->get('limite', 10);

            if (strtotime($fechaDesde) > strtotime($fechaHasta)) {
                $temp = $fechaDesde;
                $fechaDesde = $fechaHasta;
                $fechaHasta = $temp;
            }

            $ventasIds = Venta::whereBetween('FechaVenta', [$fechaDesde, $fechaHasta])
                                 ->where('Estado', 'completada')
                                 ->pluck('IdVenta')
                                 ->toArray();

            $productos = [];
            if (count($ventasIds) > 0) {
                $productos = DetalleVenta::selectRaw('IdProducto, SUM(Cantidad) as total_vendido, COALESCE(SUM(Total), 0) as ingresos_totales')
                                             ->whereIn('IdVenta', $ventasIds)
                                             ->groupBy('IdProducto')
                                             ->orderBy('total_vendido', 'desc')
                                             ->limit($limite)
                                             ->get()
                                             ->map(function($item) {
                                                 $producto = Producto::find($item->IdProducto);
                                                 return [
                                                     'IdProducto' => $item->IdProducto,
                                                     'total_vendido' => $item->total_vendido,
                                                     'ingresos_totales' => $item->ingresos_totales,
                                                     'producto' => $producto ? [
                                                         'IdProducto' => $producto->IdProducto,
                                                         'Nombre' => $producto->Nombre,
                                                         'Marca' => $producto->Marca,
                                                         'Talla' => $producto->Talla,
                                                         'Color' => $producto->Color,
                                                         'PrecioBase' => $producto->PrecioBase,
                                                         'Stock' => $producto->Stock
                                                     ] : null
                                                 ];
                                             });
            }

            return response()->json([
                'success' => true,
                'message' => 'Productos más vendidos obtenidos exitosamente',
                'data' => [
                    'periodo' => ['desde' => $fechaDesde, 'hasta' => $fechaHasta],
                    'productos' => $productos
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en getProductosMasVendidos: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos más vendidos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getReportePorMarca(Request $request)
    {
        $employeeCheck = $this->checkEmployeeAccess($request);
        if ($employeeCheck) return $employeeCheck;

        try {
            $fechaDesde = $request->get('fecha_desde', now()->startOfMonth()->toDateString());
            $fechaHasta = $request->get('fecha_hasta', now()->toDateString());

            if (strtotime($fechaDesde) > strtotime($fechaHasta)) {
                $temp = $fechaDesde;
                $fechaDesde = $fechaHasta;
                $fechaHasta = $temp;
            }

            $ventasIds = Venta::whereBetween('FechaVenta', [$fechaDesde, $fechaHasta])
                                 ->where('Estado', 'completada')
                                 ->pluck('IdVenta')
                                 ->toArray();

            $marcas = [];
            if (count($ventasIds) > 0) {
                $detalles = DetalleVenta::with('producto:IdProducto,Marca')
                                             ->whereIn('IdVenta', $ventasIds)
                                             ->get();

                $marcas = $detalles->groupBy('producto.Marca')->map(function($items, $marca) {
                    return [
                        'marca' => $marca ?? 'Sin marca',
                        'total_vendido' => $items->sum('Cantidad'),
                        'ingresos_totales' => $items->sum('Total')
                    ];
                })->values();
            }

            return response()->json([
                'success' => true,
                'message' => 'Reporte por marca obtenido exitosamente',
                'data' => [
                    'periodo' => ['desde' => $fechaDesde, 'hasta' => $fechaHasta],
                    'marcas' => $marcas
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error en getReportePorMarca: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener reporte por marca',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
