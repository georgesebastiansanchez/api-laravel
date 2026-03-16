<?php

namespace App\Http\Controllers;

use App\Models\TipoDocumento;
use Illuminate\Http\Request;

class TipoDocumentoController extends Controller
{
    /**
     * Obtener todos los tipos de documento
     * Esta ruta puede ser pública para formularios de registro
     */
    public function getAllTiposDocumento()
    {
        try {
            $tiposDocumento = TipoDocumento::select('IdTipoDocumento', 'Nombre')->get();
            
            return response()->json([
                'success' => true,
                'message' => 'Tipos de documento obtenidos exitosamente',
                'data' => $tiposDocumento
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener tipos de documento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}