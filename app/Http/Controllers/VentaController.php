<?php

namespace App\Http\Controllers;

use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class VentaController extends Controller
{
    /**
     * Obtener todas las ventas
     */
  public function index(Request $request)
{
    try {
        $user = auth('api')->user();
        
        $query = Venta::query();

        if (!in_array($user->Role, ['admin', 'user'])) {
            $query->where('NumeroDocumentoCliente', $user->NumeroDocumento);
        }

        $ventas = $query->orderBy('FechaVenta', 'desc')->get();

        $ventasTransformadas = $ventas->map(function($venta) {
            return [
                'IdVenta' => $venta->IdVenta,
                'DocumentoCliente' => $venta->NumeroDocumentoCliente,
                'FechaVenta' => $venta->FechaVenta,
                'Total' => $venta->Total,
                'MetodoPago' => $venta->MetodoPago,
                'Estado' => $venta->Estado,
                'Notas' => $venta->Notas,
                'DocumentoUsuario' => $venta->DocumentoUsuario
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Ventas obtenidas exitosamente',
            'data' => $ventasTransformadas
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al obtener las ventas',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Crear nueva venta
     */
   public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'DocumentoCliente' => 'required|string|max:20',
        'FechaVenta' => 'required|date',
        'Total' => 'required|numeric|min:0',
        'MetodoPago' => 'required|string|max:255',
        'Estado' => 'required|string|max:255',
        'Notas' => 'nullable|string'
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
        
        $venta = Venta::create([
            'NumeroDocumentoCliente' => $request->DocumentoCliente,
            'DocumentoUsuario' => $user->NumeroDocumento,
            'FechaVenta' => $request->FechaVenta,
            'Total' => $request->Total,
            'MetodoPago' => $request->MetodoPago,
            'Estado' => $request->Estado,
            'Notas' => $request->Notas
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Venta creada exitosamente',
            'data' => [
                'IdVenta' => $venta->IdVenta,
                'DocumentoCliente' => $venta->NumeroDocumentoCliente,
                'FechaVenta' => $venta->FechaVenta,
                'Total' => $venta->Total,
                'MetodoPago' => $venta->MetodoPago,
                'Estado' => $venta->Estado,
                'Notas' => $venta->Notas,
                'DocumentoUsuario' => $venta->DocumentoUsuario
            ]
        ], 201);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al crear la venta',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Actualizar venta
     */
   public function update(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'DocumentoCliente' => 'required|string|max:20',
        'FechaVenta' => 'required|date',
        'Total' => 'required|numeric|min:0',
        'MetodoPago' => 'required|string|max:255',
        'Estado' => 'required|string|max:255',
        'Notas' => 'nullable|string'
    ]);

    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Error de validación',
            'errors' => $validator->errors()
        ], 400);
    }

    try {
        $venta = Venta::find($id);

        if (!$venta) {
            return response()->json([
                'success' => false,
                'message' => 'Venta no encontrada'
            ], 404);
        }

        $venta->update([
            'NumeroDocumentoCliente' => $request->DocumentoCliente,
            'FechaVenta' => $request->FechaVenta,
            'Total' => $request->Total,
            'MetodoPago' => $request->MetodoPago,
            'Estado' => $request->Estado,
            'Notas' => $request->Notas
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Venta actualizada exitosamente',
            'data' => [
                'IdVenta' => $venta->IdVenta,
                'DocumentoCliente' => $venta->NumeroDocumentoCliente,
                'FechaVenta' => $venta->FechaVenta,
                'Total' => $venta->Total,
                'MetodoPago' => $venta->MetodoPago,
                'Estado' => $venta->Estado,
                'Notas' => $venta->Notas,
                'DocumentoUsuario' => $venta->DocumentoUsuario
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al actualizar la venta',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Eliminar venta
     */
    public function destroy($id)
    {
        try {
            $venta = Venta::find($id);

            if (!$venta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venta no encontrada'
                ], 404);
            }

            $venta->delete();

            return response()->json([
                'success' => true,
                'message' => 'Venta eliminada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la venta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener venta por ID
     */
    public function show($id)
    {
        try {
            $venta = Venta::find($id);
            
            if (!$venta) {
                return response()->json([
                    'success' => false,
                    'message' => 'Venta no encontrada'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Venta encontrada',
                'data' => [
                    'IdVenta' => $venta->IdVenta,
                    'DocumentoCliente' => $venta->NumeroDocumentoCliente,
                    'FechaVenta' => $venta->FechaVenta,
                    'Total' => $venta->Total,
                    'MetodoPago' => $venta->MetodoPago,
                    'Estado' => $venta->Estado,
                    'Notas' => $venta->Notas,
                    'Usuario' => $venta->NumeroDocumento
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la venta',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}