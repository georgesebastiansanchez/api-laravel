<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\MovimientoInventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class InventarioController extends Controller
{
    /**
     * Verificar si el usuario es admin o empleado
     */
    private function checkEmployeeAccess()
    {
        $user = auth('api')->user();
        
        if (!$user || !in_array($user->Role, ['admin', 'empleado'])) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo empleados y administradores.',
            ], 403);
        }
        
        return null;
    }

    /**
     * Obtener reporte de inventario
     */
    public function getInventario(Request $request)
    {
        $employeeCheck = $this->checkEmployeeAccess();
        if ($employeeCheck) return $employeeCheck;

        try {
            $query = Producto::with([
                'categoria:IdCategoria,NombreCategoria',
                'marca:IdMarca,NombreMarca'
            ])->where('Activo', true);

            // Filtros
            if ($request->has('categoria')) {
                $query->where('IdCategoria', $request->categoria);
            }

            if ($request->has('marca')) {
                $query->where('IdMarca', $request->marca);
            }

            if ($request->has('stock_bajo') && $request->stock_bajo == true) {
                $query->whereRaw('StockActual <= StockMinimo');
            }

            if ($request->has('sin_stock') && $request->sin_stock == true) {
                $query->where('StockActual', 0);
            }

            $inventario = $query->orderBy('StockActual', 'asc')->paginate(20);

            // Estadísticas generales
            $estadisticas = [
                'total_productos' => Producto::where('Activo', true)->count(),
                'sin_stock' => Producto::where('Activo', true)->where('StockActual', 0)->count(),
                'stock_bajo' => Producto::where('Activo', true)->whereRaw('StockActual <= StockMinimo')->count(),
                'valor_inventario' => Producto::where('Activo', true)
                    ->get()
                    ->sum(function($producto) {
                        return $producto->StockActual * $producto->Costo;
                    })
            ];

            return response()->json([
                'success' => true,
                'message' => 'Inventario obtenido exitosamente',
                'data' => [
                    'inventario' => $inventario,
                    'estadisticas' => $estadisticas
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el inventario',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Ajustar stock manualmente
     */
    public function ajustarStock(Request $request)
    {
        $employeeCheck = $this->checkEmployeeAccess();
        if ($employeeCheck) return $employeeCheck;

        $validator = Validator::make($request->all(), [
            'IdProducto' => 'required|exists:Productos,IdProducto',
            'StockNuevo' => 'required|integer|min:0',
            'Motivo' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $user = auth('api')->user();
            $producto = Producto::find($request->IdProducto);
            
            $stockAnterior = $producto->StockActual;
            $stockNuevo = $request->StockNuevo;
            $diferencia = $stockNuevo - $stockAnterior;

            // Actualizar stock
            $producto->update(['StockActual' => $stockNuevo]);

            // Registrar movimiento
            MovimientoInventario::create([
                'IdProducto' => $request->IdProducto,
                'TipoMovimiento' => 'ajuste',
                'Cantidad' => abs($diferencia),
                'StockAnterior' => $stockAnterior,
                'StockNuevo' => $stockNuevo,
                'ReferenciaId' => null,
                'TipoReferencia' => 'ajuste',
                'Motivo' => $request->Motivo,
                'NumeroDocumentoUsuario' => $user->NumeroDocumento
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Stock ajustado exitosamente',
                'data' => [
                    'stock_anterior' => $stockAnterior,
                    'stock_nuevo' => $stockNuevo,
                    'diferencia' => $diferencia
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al ajustar el stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener movimientos de inventario
     */
    public function getMovimientos(Request $request)
    {
        $employeeCheck = $this->checkEmployeeAccess();
        if ($employeeCheck) return $employeeCheck;

        try {
            $query = MovimientoInventario::with([
                'producto:IdProducto,Nombre',
                'usuario:NumeroDocumento,Nombre1,Apellido1'
            ]);

            // Filtros
            if ($request->has('tipo_movimiento')) {
                $query->where('TipoMovimiento', $request->tipo_movimiento);
            }

            if ($request->has('fecha_desde')) {
                $query->whereDate('FechaMovimiento', '>=', $request->fecha_desde);
            }
            
            if ($request->has('fecha_hasta')) {
                $query->whereDate('FechaMovimiento', '<=', $request->fecha_hasta);
            }

            if ($request->has('producto')) {
                $query->where('IdProducto', $request->producto);
            }

            $movimientos = $query->orderBy('FechaMovimiento', 'desc')->paginate(20);

            return response()->json([
                'success' => true,
                'message' => 'Movimientos obtenidos exitosamente',
                'data' => $movimientos
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los movimientos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}