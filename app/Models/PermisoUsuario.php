<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermisoUsuario extends Model

{
    protected $table = 'permisos_usuario';
    protected $primaryKey = 'IdPermiso';
    public $timestamps = false;

    protected $fillable = [
        'NumeroDocumento',
        'IdModulo',
        'TieneAcceso',
        'AsignadoPor'
    ];

    protected $casts = [
        'TieneAcceso' => 'boolean',
    ];
// 🆕 AGREGAR ESTE MÉTODO AL INICIO
public function index(Request $request)
{
    $admin = $request->user();
    
    if (!$admin || $admin->Role !== 'admin') {
        return response()->json([
            'success' => false,
            'message' => 'No tienes permisos para realizar esta acción'
        ], 403);
    }

    try {
        $permisos = PermisoUsuario::with(['modulo', 'usuario'])
            ->get()
            ->map(function($permiso) {
                return [
                    'IdPermiso' => $permiso->IdPermiso,
                    'NumeroDocumento' => $permiso->NumeroDocumento,
                    'IdModulo' => $permiso->IdModulo,
                    'TieneAcceso' => $permiso->TieneAcceso,
                    'AsignadoPor' => $permiso->AsignadoPor,
                    'NombreUsuario' => $permiso->usuario->Nombre ?? null,
                    'EmailUsuario' => $permiso->usuario->Email ?? null,
                    'NombreModulo' => $permiso->modulo->NombreModulo ?? null,
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $permisos
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error al obtener permisos: ' . $e->getMessage()
        ], 500);
    }
}
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'NumeroDocumento', 'NumeroDocumento');
    }

    public function modulo()
    {
        return $this->belongsTo(Modulo::class, 'IdModulo', 'IdModulo');
    }
}