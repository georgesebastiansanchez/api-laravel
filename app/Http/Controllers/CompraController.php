<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CompraController extends Controller
{
    /**
     * Verificar si el usuario es admin o empleado
     */
    private function checkEmployeeAccess()
    {
        $user = auth('api')->user();

        if (!$user || !in_array($user->Role, ['admin', 'usuario', 'user'])) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo empleados y administradores.',
            ], 403);
        }

        return null;
    }

    /**
     * Convertir estado Frontend -> Backend
     * Frontend usa: Pendiente, En Proceso, Completada, Cancelada
     * Backend usa: pendiente, recibida, cancelada
     */
    private function convertEstadoToDb($estadoFront)
    {
        $map = [
            'Pendiente' => 'pendiente',
            'recibida' => 'recibida',
            'Cancelada' => 'cancelada'
        ];
        return $map[$estadoFront] ?? 'pendiente';
    }

    /**
     * Convertir estado Backend -> Frontend
     */
    private function convertEstadoToFront($estadoDb)
    {
        $map = [
            'pendiente' => 'Pendiente',
            'recibida' => 'recibida',
            'cancelada' => 'Cancelada'
        ];
        return $map[$estadoDb] ?? 'Pendiente';
    }

    /**
     * Obtener compras
     */
    public function getCompras(Request $request)
    {
        $employeeCheck = $this->checkEmployeeAccess();
        if ($employeeCheck) return $employeeCheck;

        try {
            $compras = DB::table('compras')
                ->leftJoin('proveedores', 'compras.IdProveedor', '=', 'proveedores.IdProveedor')
                ->leftJoin('usuarios', 'compras.NumeroDocumentoUsuario', '=', 'usuarios.NumeroDocumento')
                ->select(
                    'compras.IdCompras',
                    'compras.FechaCompra',
                    'compras.Total',
                    'compras.Estado',
                    'compras.IdProveedor',
                    'compras.NumeroDocumentoUsuario',
                    'proveedores.NombreProveedor',
                    DB::raw("CONCAT(IFNULL(usuarios.Nombre1, ''), ' ', IFNULL(usuarios.Apellido1, '')) as NombreUsuario")
                )
                ->orderBy('compras.FechaCreacion', 'desc')
                ->get();

            // Transformar estados
            $comprasTransformadas = $compras->map(function($compra) {
                $compra->Estado = $this->convertEstadoToFront($compra->Estado);
                return $compra;
            });

            return response()->json([
                'success' => true,
                'message' => 'Compras obtenidas exitosamente',
                'data' => $comprasTransformadas
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al obtener compras: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las compras',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear compra
     */
    public function addCompra(Request $request)
    {
        $employeeCheck = $this->checkEmployeeAccess();
        if ($employeeCheck) return $employeeCheck;

        // Validación con tipos correctos
        $validator = Validator::make($request->all(), [
            'IdProveedor' => 'required|integer|exists:proveedores,IdProveedor',
            'FechaCompra' => 'required|date',
            'Total' => 'required|numeric|min:0',
            'Estado' => 'required|string|in:Pendiente,En Proceso,recibida
            ,Cancelada',
            'NumeroDocumentoUsuario' => 'required|numeric|exists:usuarios,NumeroDocumento'
        ], [
            'NumeroDocumentoUsuario.numeric' => 'El número de documento debe ser numérico',
            'NumeroDocumentoUsuario.exists' => 'El usuario no existe',
            'IdProveedor.exists' => 'El proveedor no existe',
            'Estado.in' => 'El estado debe ser: Pendiente, En Proceso, recibida o Cancelada'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // Convertir estado de Frontend a Backend
            $estadoDB = $this->convertEstadoToDb($request->Estado);

            Log::info('Creando compra', [
                'proveedor' => $request->IdProveedor,
                'documento' => $request->NumeroDocumentoUsuario,
                'total' => $request->Total,
                'estado_frontend' => $request->Estado,
                'estado_backend' => $estadoDB
            ]);

            // Insertar directamente en la base de datos
            $idCompra = DB::table('compras')->insertGetId([
                'IdProveedor' => (int)$request->IdProveedor,
                'NumeroDocumentoUsuario' => (int)$request->NumeroDocumentoUsuario,
                'FechaCompra' => $request->FechaCompra,
                'Total' => (float)$request->Total,
                'Estado' => $estadoDB,
                'FechaCreacion' => now()
            ]);

            Log::info('Compra creada con ID: ' . $idCompra);

            // Obtener la compra creada con sus relaciones
            $compra = DB::table('compras')
                ->leftJoin('proveedores', 'compras.IdProveedor', '=', 'proveedores.IdProveedor')
                ->leftJoin('usuarios', 'compras.NumeroDocumentoUsuario', '=', 'usuarios.NumeroDocumento')
                ->select(
                    'compras.IdCompras',
                    'compras.FechaCompra',
                    'compras.Total',
                    'compras.Estado',
                    'compras.IdProveedor',
                    'compras.NumeroDocumentoUsuario',
                    'proveedores.NombreProveedor',
                    DB::raw("CONCAT(IFNULL(usuarios.Nombre1, ''), ' ', IFNULL(usuarios.Apellido1, '')) as NombreUsuario")
                )
                ->where('compras.IdCompras', $idCompra)
                ->first();

            // Convertir estado de Backend a Frontend
            $compra->Estado = $this->convertEstadoToFront($compra->Estado);

            return response()->json([
                'success' => true,
                'message' => 'Compra creada exitosamente',
                'data' => $compra
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al crear compra: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar compra
     */
    public function updateCompra(Request $request, $id)
    {
        $employeeCheck = $this->checkEmployeeAccess();
        if ($employeeCheck) return $employeeCheck;

        // Validación con tipos correctos
        $validator = Validator::make($request->all(), [
            'IdProveedor' => 'required|integer|exists:proveedores,IdProveedor',
            'FechaCompra' => 'required|date',
            'Total' => 'required|numeric|min:0',
            'Estado' => 'required|string|in:Pendiente,En Proceso,recibida,Cancelada',
            'NumeroDocumentoUsuario' => 'required|numeric|exists:usuarios,NumeroDocumento'
        ], [
            'NumeroDocumentoUsuario.numeric' => 'El número de documento debe ser numérico',
            'NumeroDocumentoUsuario.exists' => 'El usuario no existe',
            'IdProveedor.exists' => 'El proveedor no existe',
            'Estado.in' => 'El estado debe ser: Pendiente, En Proceso, recibida o Cancelada'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            // Verificar si existe
            $existe = DB::table('compras')->where('IdCompras', $id)->exists();
            
            if (!$existe) {
                return response()->json([
                    'success' => false,
                    'message' => 'Compra no encontrada'
                ], 404);
            }

            // Convertir estado de Frontend a Backend
            $estadoDB = $this->convertEstadoToDb($request->Estado);

            Log::info('Actualizando compra', [
                'id' => $id,
                'proveedor' => $request->IdProveedor,
                'documento' => $request->NumeroDocumentoUsuario,
                'total' => $request->Total,
                'estado_frontend' => $request->Estado,
                'estado_backend' => $estadoDB
            ]);

            // Actualizar directamente en la base de datos
            $affected = DB::table('compras')
                ->where('IdCompras', $id)
                ->update([
                    'IdProveedor' => (int)$request->IdProveedor,
                    'NumeroDocumentoUsuario' => (int)$request->NumeroDocumentoUsuario,
                    'FechaCompra' => $request->FechaCompra,
                    'Total' => (float)$request->Total,
                    'Estado' => $estadoDB
                ]);

            Log::info('Compra actualizada, filas afectadas: ' . $affected);

            // Obtener la compra actualizada
            $compra = DB::table('compras')
                ->leftJoin('proveedores', 'compras.IdProveedor', '=', 'proveedores.IdProveedor')
                ->leftJoin('usuarios', 'compras.NumeroDocumentoUsuario', '=', 'usuarios.NumeroDocumento')
                ->select(
                    'compras.IdCompras',
                    'compras.FechaCompra',
                    'compras.Total',
                    'compras.Estado',
                    'compras.IdProveedor',
                    'compras.NumeroDocumentoUsuario',
                    'proveedores.NombreProveedor',
                    DB::raw("CONCAT(IFNULL(usuarios.Nombre1, ''), ' ', IFNULL(usuarios.Apellido1, '')) as NombreUsuario")
                )
                ->where('compras.IdCompras', $id)
                ->first();

            // Convertir estado de Backend a Frontend
            $compra->Estado = $this->convertEstadoToFront($compra->Estado);

            return response()->json([
                'success' => true,
                'message' => 'Compra actualizada exitosamente',
                'data' => $compra
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al actualizar compra: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar compra
     */
    public function deleteCompra($id)
    {
        $employeeCheck = $this->checkEmployeeAccess();
        if ($employeeCheck) return $employeeCheck;

        try {
            // Verificar si existe
            $compra = DB::table('compras')->where('IdCompras', $id)->first();

            if (!$compra) {
                return response()->json([
                    'success' => false,
                    'message' => 'Compra no encontrada'
                ], 404);
            }

            Log::info('Eliminando compra', [
                'id' => $id,
                'estado' => $compra->Estado
            ]);

            // Eliminar la compra (sin restricción de estado)
            $deleted = DB::table('compras')->where('IdCompras', $id)->delete();

            Log::info('Compra eliminada, filas afectadas: ' . $deleted);

            return response()->json([
                'success' => true,
                'message' => 'Compra eliminada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al eliminar compra: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener compra por ID
     */
    public function getCompraById($id)
    {
        $employeeCheck = $this->checkEmployeeAccess();
        if ($employeeCheck) return $employeeCheck;

        try {
            $compra = DB::table('compras')
                ->leftJoin('proveedores', 'compras.IdProveedor', '=', 'proveedores.IdProveedor')
                ->leftJoin('usuarios', 'compras.NumeroDocumentoUsuario', '=', 'usuarios.NumeroDocumento')
                ->select(
                    'compras.*',
                    'proveedores.NombreProveedor',
                    'proveedores.Contacto',
                    'proveedores.Telefono',
                    'proveedores.Email',
                    DB::raw("CONCAT(IFNULL(usuarios.Nombre1, ''), ' ', IFNULL(usuarios.Apellido1, '')) as NombreUsuario")
                )
                ->where('compras.IdCompras', $id)
                ->first();

            if (!$compra) {
                return response()->json([
                    'success' => false,
                    'message' => 'Compra no encontrada'
                ], 404);
            }

            // Convertir estado
            $compra->Estado = $this->convertEstadoToFront($compra->Estado);

            return response()->json([
                'success' => true,
                'message' => 'Compra encontrada',
                'data' => $compra
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al obtener compra: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la compra',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}