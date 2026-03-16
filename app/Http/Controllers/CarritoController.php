<?php

namespace App\Http\Controllers;

use App\Models\Carrito;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CarritoController extends Controller
{
    /**
     * Obtener carrito del usuario autenticado
     */
    public function getCarrito()
    {
        try {
            $user = auth('api')->user();
            
            $carrito = Carrito::with([
                'producto:IdProducto,Nombre,PrecioBase,ImagenPrincipal'
            ])
            ->where('NumeroDocumentoUsuario', $user->NumeroDocumento)
            ->get();

            $total = 0;
            $carritoConSubtotal = $carrito->map(function ($item) use (&$total) {
                $precioUnitario = $item->producto->PrecioBase;
                $subtotal = $item->Cantidad * $precioUnitario;
                $total += $subtotal;

                return [
                    'IdCarrito' => $item->IdCarrito,
                    'IdProducto' => $item->IdProducto,
                    'Cantidad' => $item->Cantidad,
                    'PrecioUnitario' => $precioUnitario,
                    'Subtotal' => $subtotal,
                    'Producto' => $item->producto,
                    'FechaAgregado' => $item->FechaAgregado
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Carrito obtenido exitosamente',
                'data' => [
                    'items' => $carritoConSubtotal,
                    'total' => $total,
                    'cantidad_items' => $carrito->count()
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el carrito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Agregar producto al carrito
     */
    public function addToCarrito(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'IdProducto' => 'required|exists:Productos,IdProducto',
            'Cantidad' => 'required|integer|min:1'
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

            // Verificar stock disponible
            if ($producto->StockActual < $request->Cantidad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente. Stock disponible: ' . $producto->StockActual
                ], 400);
            }

            // Verificar si ya existe en el carrito
            $carritoExistente = Carrito::where('NumeroDocumentoUsuario', $user->NumeroDocumento)
                                      ->where('IdProducto', $request->IdProducto)
                                      ->first();

            if ($carritoExistente) {
                $nuevaCantidad = $carritoExistente->Cantidad + $request->Cantidad;
                
                // Verificar stock para la nueva cantidad
                if ($producto->StockActual < $nuevaCantidad) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Stock insuficiente. Ya tienes ' . $carritoExistente->Cantidad . ' en el carrito. Stock disponible: ' . $producto->StockActual
                    ], 400);
                }

                $carritoExistente->update(['Cantidad' => $nuevaCantidad]);
                $carrito = $carritoExistente;
            } else {
                $carrito = Carrito::create([
                    'NumeroDocumentoUsuario' => $user->NumeroDocumento,
                    'IdProducto' => $request->IdProducto,
                    'Cantidad' => $request->Cantidad
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Producto agregado al carrito exitosamente',
                'data' => $carrito
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar producto al carrito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar cantidad en carrito
     */
    public function updateCarritoItem(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'Cantidad' => 'required|integer|min:1'
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
            $carritoItem = Carrito::where('IdCarrito', $id)
                                 ->where('NumeroDocumentoUsuario', $user->NumeroDocumento)
                                 ->first();

            if (!$carritoItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item no encontrado en el carrito'
                ], 404);
            }

            // Verificar stock
            if ($carritoItem->producto->StockActual < $request->Cantidad) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock insuficiente. Stock disponible: ' . $carritoItem->producto->StockActual
                ], 400);
            }

            $carritoItem->update(['Cantidad' => $request->Cantidad]);

            return response()->json([
                'success' => true,
                'message' => 'Cantidad actualizada exitosamente',
                'data' => $carritoItem
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el item del carrito',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Eliminar item del carrito
     */
    public function removeFromCarrito($id)
    {
        try {
            $user = auth('api')->user();
            $carritoItem = Carrito::where('IdCarrito', $id)
                                 ->where('NumeroDocumentoUsuario', $user->NumeroDocumento)
                                 ->first();

            if (!$carritoItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item no encontrado en el carrito'
                ], 404);
            }

            $carritoItem->delete();

            return response()->json([
                'success' => true,
                'message' => 'Item eliminado del carrito exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar item del carrito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Limpiar carrito completo
     */
    public function clearCarrito()
    {
        try {
            $user = auth('api')->user();
            
            Carrito::where('NumeroDocumentoUsuario', $user->NumeroDocumento)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Carrito vaciado exitosamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al vaciar el carrito',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}