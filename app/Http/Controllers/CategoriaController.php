<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CategoriaController extends Controller
{
    /**
     * Verificar si el usuario es admin
     */
    private function checkAdminAccess()
    {
        $user = auth('api')->user();
        
        if (!$user || $user->Role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado. Solo administradores.',
            ], 403);
        }
        
        return null;
    }

    /**
     * Obtener todas las categorías (usuarios autenticados)
     */
    public function getAllCategorias()
    {
        try {
            $categorias = Categoria::where('Activa', true)
                                  ->select('IdCategoria', 'NombreCategoria', 'Descripcion')
                                  ->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Categorías obtenidas exitosamente',
                'data' => $categorias
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener categorías',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear categoría (solo admin)
     */
    public function addCategoria(Request $request)
    {
        $adminCheck = $this->checkAdminAccess();
        if ($adminCheck) return $adminCheck;

        $validator = Validator::make($request->all(), [
            'NombreCategoria' => 'required|string|max:50|unique:Categorias,NombreCategoria',
            'Descripcion' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $categoria = Categoria::create([
                'NombreCategoria' => $request->NombreCategoria,
                'Descripcion' => $request->Descripcion
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Categoría creada exitosamente',
                'data' => $categoria
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar categoría (solo admin)
     */
    public function updateCategoria(Request $request, $id)
    {
        $adminCheck = $this->checkAdminAccess();
        if ($adminCheck) return $adminCheck;

        try {
            $categoria = Categoria::find($id);
            
            if (!$categoria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'NombreCategoria' => 'sometimes|string|max:50|unique:Categorias,NombreCategoria,' . $id . ',IdCategoria',
                'Descripcion' => 'nullable|string',
                'Activa' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 400);
            }

            $updateData = array_filter($request->only(['NombreCategoria', 'Descripcion', 'Activa']), function($value) {
                return $value !== null;
            });

            $categoria->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Categoría actualizada exitosamente',
                'data' => $categoria->fresh()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar categoría (solo admin)
     */
    public function deleteCategoria($id)
    {
        $adminCheck = $this->checkAdminAccess();
        if ($adminCheck) return $adminCheck;

        try {
            $categoria = Categoria::find($id);
            
            if (!$categoria) {
                return response()->json([
                    'success' => false,
                    'message' => 'Categoría no encontrada'
                ], 404);
            }

            // Verificar si tiene productos asociados
            if ($categoria->productos()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la categoría porque tiene productos asociados'
                ], 400);
            }

            $nombreCategoria = $categoria->NombreCategoria;
            $categoria->delete();
            
            return response()->json([
                'success' => true,
                'message' => "Categoría '{$nombreCategoria}' eliminada exitosamente"
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
