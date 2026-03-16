<?php
namespace App\Http\Controllers;
use App\Models\Modulo;
use Illuminate\Http\Request;

class ModuloController extends Controller
{
    // Obtener todos los módulos
    public function index()
    {
        $modulos = Modulo::where('Activo', true)
            ->orderBy('Orden')
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $modulos
        ]);
    }

    // Obtener módulos disponibles para un usuario
    public function modulosUsuario(Request $request)
    {
        $usuario = $request->user();
        
        if ($usuario->Role === 'admin') {
            // Admin tiene acceso a todos
            $modulos = Modulo::where('Activo', true)
                ->orderBy('Orden')
                ->get()
                ->map(function($modulo) {
                    $modulo->TieneAcceso = true;
                    return $modulo;
                });
        } else {
            // Usuario normal: obtener solo módulos con acceso
            $modulos = Modulo::leftJoin('permisos_usuario', function($join) use ($usuario) {
                $join->on('modulos.IdModulo', '=', 'permisos_usuario.IdModulo')
                     ->where('permisos_usuario.NumeroDocumento', '=', $usuario->NumeroDocumento);
            })
            ->where('modulos.Activo', true)
            ->orderBy('modulos.Orden')
            ->select([
                'modulos.*',
                'permisos_usuario.TieneAcceso'
            ])
            ->get();
        }
        
        return response()->json([
            'success' => true,
            'data' => $modulos
        ]);
    }
}