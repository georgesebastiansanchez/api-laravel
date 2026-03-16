<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProductoController extends Controller
{
    /**
     * Listar productos (GET /api/productos)
     */
    public function index()
    {
        try {
            // Solo devuelve los campos de la tabla productos, incluyendo IdCategoria (foránea)
            $productos = Producto::orderBy('FechaCreacion', 'desc')->get();

            return response()->json([
                'success' => true,
                'message' => 'Productos obtenidos exitosamente',
                'data' => $productos
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear producto (POST /api/productos)
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'Nombre' => 'required|string|max:100',
            'Descripcion' => 'nullable|string',
            'Marca' => 'nullable|string|max:100',
            'Color' => 'nullable|string|max:50',
            'Talla' => 'nullable|string|max:50',
            'PrecioBase' => 'required|numeric|min:0',
            'IdCategoria' => 'required|integer|exists:categoria,IdCategoria',
            'Stock' => 'required|integer|min:0',
            'Activo' => 'boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $producto = Producto::create([
                'Nombre' => $request->Nombre,
                'Descripcion' => $request->Descripcion,
                'Marca' => $request->Marca,
                'Color' => $request->Color,
                'Talla' => $request->Talla,
                'PrecioBase' => $request->PrecioBase,
                'IdCategoria' => $request->IdCategoria, // 👈 solo guardamos la foránea
                'Stock' => $request->Stock,
                'Activo' => $request->Activo ?? true,
                'FechaCreacion' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Producto creado exitosamente',
                'data' => $producto
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar producto por ID (GET /api/productos/{id})
     */
    public function show($id)
    {
        try {
            $producto = Producto::find($id);

            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Producto encontrado',
                'data' => $producto
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar producto (PUT /api/productos/{id})
     */
    public function update(Request $request, $id)
    {
        try {
            $producto = Producto::find($id);

            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'Nombre' => 'sometimes|string|max:100',
                'Descripcion' => 'nullable|string',
                'Marca' => 'nullable|string|max:100',
                'Color' => 'nullable|string|max:50',
                'Talla' => 'nullable|string|max:50',
                'PrecioBase' => 'sometimes|numeric|min:0',
                'IdCategoria' => 'sometimes|integer|exists:categoria,IdCategoria', // 👈 validamos foránea
                'Stock' => 'sometimes|integer|min:0',
                'Activo' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 400);
            }

            $producto->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado exitosamente',
                'data' => $producto
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar producto (DELETE /api/productos/{id})
     */
    public function destroy($id)
    {
        try {
            $producto = Producto::find($id);

            if (!$producto) {
                return response()->json([
                    'success' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            $producto->delete();

            return response()->json([
                'success' => true,
                'message' => 'Producto eliminado exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}