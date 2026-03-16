<?php
namespace App\Http\Controllers;

use App\Models\SolicitudAcceso;
use App\Models\PermisoUsuario;
use App\Models\Notificacion;
use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitudAccesoController extends Controller
{
    // Usuario solicita acceso a un módulo
    public function solicitarAcceso(Request $request)
    {
        $usuario = $request->user();

        $validated = $request->validate([
            'IdModulo' => 'required|exists:modulos,IdModulo',
            'Justificacion' => 'required|string|min:10|max:500'
        ]);

        // Verificar si ya tiene acceso
        $tieneAcceso = PermisoUsuario::where('NumeroDocumento', $usuario->NumeroDocumento)
            ->where('IdModulo', $validated['IdModulo'])
            ->where('TieneAcceso', true)
            ->exists();

        if ($tieneAcceso) {
            return response()->json([
                'success' => false,
                'message' => 'Ya tienes acceso a este módulo'
            ], 400);
        }

        // Verificar si ya tiene una solicitud pendiente
        $solicitudExistente = SolicitudAcceso::where('NumeroDocumento', $usuario->NumeroDocumento)
            ->where('IdModulo', $validated['IdModulo'])
            ->where('Estado', 'pendiente')
            ->first();

        if ($solicitudExistente) {
            return response()->json([
                'success' => false,
                'message' => 'Ya tienes una solicitud pendiente para este módulo'
            ], 400);
        }

        // Crear la solicitud
        $solicitud = SolicitudAcceso::create([
            'NumeroDocumento' => $usuario->NumeroDocumento,
            'IdModulo' => $validated['IdModulo'],
            'Justificacion' => $validated['Justificacion']
        ]);

        // Notificar a todos los administradores
        $admins = Usuario::where('Role', 'admin')->get();
        foreach ($admins as $admin) {
            Notificacion::create([
                'NumeroDocumento' => $admin->NumeroDocumento,
                'Tipo' => 'solicitud_acceso',
                'Titulo' => 'Nueva Solicitud de Acceso',
                'Mensaje' => "El usuario {$usuario->Email} ha solicitado acceso a un módulo.",
                'IdReferencia' => $solicitud->IdSolicitud
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Solicitud enviada correctamente. El administrador será notificado.',
            'data' => $solicitud
        ], 201);
    }

    // Obtener solicitudes del usuario actual
    public function misSolicitudes(Request $request)
    {
        $usuario = $request->user();
        
        $solicitudes = SolicitudAcceso::with('modulo')
            ->where('NumeroDocumento', $usuario->NumeroDocumento)
            ->orderBy('FechaSolicitud', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $solicitudes
        ]);
    }

    // Admin: Obtener todas las solicitudes pendientes
    public function solicitudesPendientes(Request $request)
    {
        $admin = $request->user();
        
        if ($admin->Role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver las solicitudes'
            ], 403);
        }

        $solicitudes = SolicitudAcceso::with(['usuario', 'modulo'])
            ->where('Estado', 'pendiente')
            ->orderBy('FechaSolicitud', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $solicitudes,
            'total' => $solicitudes->count()
        ]);
    }

    // Admin: Obtener todas las solicitudes (historial completo)
    public function todasLasSolicitudes(Request $request)
    {
        $admin = $request->user();
        
        if ($admin->Role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos'
            ], 403);
        }

        $solicitudes = SolicitudAcceso::with(['usuario', 'modulo'])
            ->orderBy('FechaSolicitud', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $solicitudes
        ]);
    }

    // Admin: Responder solicitud (aprobar o rechazar)
    public function responderSolicitud(Request $request, $idSolicitud)
    {
        $admin = $request->user();
        
        if ($admin->Role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para responder solicitudes'
            ], 403);
        }

        $validated = $request->validate([
            'Estado' => 'required|in:aprobada,rechazada',
            'ComentarioAdmin' => 'nullable|string|max:500'
        ]);

        $solicitud = SolicitudAcceso::with('modulo')->findOrFail($idSolicitud);

        if ($solicitud->Estado !== 'pendiente') {
            return response()->json([
                'success' => false,
                'message' => 'Esta solicitud ya fue respondida'
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Actualizar solicitud
            $solicitud->update([
                'Estado' => $validated['Estado'],
                'FechaRespuesta' => now(),
                'RespondidoPor' => $admin->NumeroDocumento,
                'ComentarioAdmin' => $validated['ComentarioAdmin'] ?? null
            ]);

            // Si se aprueba, crear o actualizar el permiso
            if ($validated['Estado'] === 'aprobada') {
                PermisoUsuario::updateOrCreate(
                    [
                        'NumeroDocumento' => $solicitud->NumeroDocumento,
                        'IdModulo' => $solicitud->IdModulo
                    ],
                    [
                        'TieneAcceso' => true,
                        'AsignadoPor' => $admin->NumeroDocumento
                    ]
                );
            }

            // Notificar al usuario
            $mensaje = $validated['Estado'] === 'aprobada' 
                ? "Tu solicitud de acceso al módulo '{$solicitud->modulo->NombreModulo}' ha sido APROBADA ✓. Ya puedes acceder al módulo."
                : "Tu solicitud de acceso al módulo '{$solicitud->modulo->NombreModulo}' ha sido RECHAZADA.";
            
            if (!empty($validated['ComentarioAdmin'])) {
                $mensaje .= "\n\nComentario del administrador: " . $validated['ComentarioAdmin'];
            }

            Notificacion::create([
                'NumeroDocumento' => $solicitud->NumeroDocumento,
                'Tipo' => 'respuesta_solicitud',
                'Titulo' => $validated['Estado'] === 'aprobada' 
                    ? '✓ Solicitud Aprobada' 
                    : 'Solicitud Rechazada',
                'Mensaje' => $mensaje,
                'IdReferencia' => $solicitud->IdSolicitud
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Solicitud respondida correctamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al responder solicitud: ' . $e->getMessage()
            ], 500);
        }
    }}