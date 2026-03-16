<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProveedorController extends Controller
{
    /**
     * Verificar si el usuario es admin o empleado
     */
    private function checkEmployeeAccess()
    {
        $user = auth('api')->user();
        
        if (!$user || !in_array($user->Role, ['admin', 'usuario','user'])) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo usuario y administradores.',
            ], 403);
        }
        
        return null;
    }

    /**
     * Obtener todos los proveedores (solo admin/empleado)
     */
    public function getAllProveedores()
    {
        $employeeCheck = $this->checkEmployeeAccess();
        if ($employeeCheck) return $employeeCheck;

        try {
            $proveedores = Proveedor::where('Activo', true)
                                   ->select('IdProveedor', 'NombreProveedor', 'Contacto', 'Telefono', 'Email')
                                   ->orderBy('NombreProveedor')
                                   ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Proveedores obtenidos exitosamente',
                'data' => $proveedores
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener proveedores',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear proveedor (solo admin)
     */
    public function addProveedor(Request $request)
    {
        $user = auth('api')->user();
        
        if (!$user || $user->Role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo administradores.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'NombreProveedor' => 'required|string|max:100|unique:Proveedores,NombreProveedor',
            'Contacto' => 'nullable|string|max:100',
            'Telefono' => 'nullable|string|max:20',
            'Email' => 'nullable|email|max:100',
            'Direccion' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $proveedor = Proveedor::create([
                'NombreProveedor' => $request->NombreProveedor,
                'Contacto' => $request->Contacto,
                'Telefono' => $request->Telefono,
                'Email' => $request->Email,
                'Direccion' => $request->Direccion
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Proveedor creado exitosamente',
                'data' => $proveedor
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener proveedor por ID (solo admin/empleado)
     */
    public function getProveedorById($id)
    {
        $employeeCheck = $this->checkEmployeeAccess();
        if ($employeeCheck) return $employeeCheck;

        try {
            $proveedor = Proveedor::find($id);
            
            if (!$proveedor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proveedor no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Proveedor encontrado',
                'data' => $proveedor
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar proveedor (solo admin)
     */
    public function updateProveedor(Request $request, $id)
    {
        $user = auth('api')->user();
        
        if (!$user || $user->Role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo administradores.',
            ], 403);
        }

        try {
            $proveedor = Proveedor::find($id);
            
            if (!$proveedor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proveedor no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'NombreProveedor' => 'sometimes|string|max:100|unique:Proveedores,NombreProveedor,' . $id . ',IdProveedor',
                'Contacto' => 'nullable|string|max:100',
                'Telefono' => 'nullable|string|max:20',
                'Email' => 'nullable|email|max:100',
                'Direccion' => 'nullable|string',
                'Activo' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 400);
            }

            $updateData = array_filter($request->only([
                'NombreProveedor', 'Contacto', 'Telefono', 'Email', 'Direccion', 'Activo'
            ]), function($value) {
                return $value !== null;
            });

            $proveedor->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Proveedor actualizado exitosamente',
                'data' => $proveedor->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar proveedor (solo admin)
     */
    public function deleteProveedor($id)
    {
        $user = auth('api')->user();
        
        if (!$user || $user->Role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo administradores.',
            ], 403);
        }

        try {
            $proveedor = Proveedor::find($id);
            
            if (!$proveedor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Proveedor no encontrado'
                ], 404);
            }

            // Verificar si tiene compras asociadas
            if ($proveedor->compras()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el proveedor porque tiene compras asociadas'
                ], 400);
            }

            $nombreProveedor = $proveedor->NombreProveedor;
            $proveedor->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Proveedor '{$nombreProveedor}' eliminado exitosamente"
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}