<?php

namespace App\Http\Controllers;

use App\Models\PermisoUsuario;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermisoController extends Controller
{
    // Obtener TODOS los permisos (para admin)
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

    // Obtener permisos de un usuario específico (por documento)
    public function getPermisosUsuario($numeroDocumento)
    {
        $permisos = PermisoUsuario::with('modulo')
            ->where('NumeroDocumento', $numeroDocumento)
            ->get();
        
        return response()->json([
            'success' => true,
            'data' => $permisos
        ]);
    }

    // Obtener permisos del usuario autenticado
    public function misPermisos(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $permisos = PermisoUsuario::with('modulo')
                ->where('NumeroDocumento', $user->NumeroDocumento)
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $permisos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener permisos: ' . $e->getMessage()
            ], 500);
        }
    }

    // Asignar o actualizar permisos de usuario (solo admin)
    public function asignarPermisos(Request $request)
    {
        $admin = $request->user();
        
        if (!$admin || $admin->Role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción'
            ], 403);
        }

        $validated = $request->validate([
            'NumeroDocumento' => 'required|exists:usuarios,NumeroDocumento',
            'permisos' => 'required|array',
            'permisos.*.IdModulo' => 'required|exists:modulos,IdModulo',
            'permisos.*.TieneAcceso' => 'required|boolean'
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['permisos'] as $permiso) {
                PermisoUsuario::updateOrCreate(
                    [
                        'NumeroDocumento' => $validated['NumeroDocumento'],
                        'IdModulo' => $permiso['IdModulo']
                    ],
                    [
                        'TieneAcceso' => $permiso['TieneAcceso'],
                        'AsignadoPor' => $admin->NumeroDocumento
                    ]
                );

                // Crear notificación si se otorgó acceso
                if ($permiso['TieneAcceso']) {
                    Notificacion::create([
                        'NumeroDocumento' => $validated['NumeroDocumento'],
                        'Tipo' => 'permiso_otorgado',
                        'Titulo' => 'Nuevo Permiso Otorgado',
                        'Mensaje' => "Se te ha otorgado acceso a un nuevo módulo del sistema."
                    ]);
                }
            }

            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Permisos actualizados correctamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar permisos: ' . $e->getMessage()
            ], 500);
        }
    }

    // Revocar permiso de un usuario a un módulo
    public function revocarPermiso(Request $request, $numeroDocumento, $idModulo)
    {
        $admin = $request->user();
        
        if (!$admin || $admin->Role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción'
            ], 403);
        }

        try {
            // Buscar el permiso específico
            $permiso = PermisoUsuario::where('NumeroDocumento', $numeroDocumento)
                ->where('IdModulo', $idModulo)
                ->first();

            if (!$permiso) {
                return response()->json([
                    'success' => false,
                    'message' => 'Permiso no encontrado'
                ], 404);
            }

            // Eliminar el permiso
            $permiso->delete();

            // Crear notificación para el usuario
            Notificacion::create([
                'NumeroDocumento' => $numeroDocumento,
                'Tipo' => 'permiso_revocado',
                'Titulo' => 'Permiso Revocado',
                'Mensaje' => 'Se ha revocado tu acceso a un módulo del sistema.'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Permiso revocado correctamente'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al revocar permiso: ' . $e->getMessage()
            ], 500);
        }
    }
}