<?php

namespace App\Http\Controllers;
use App\Models\Notificacion;
use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    // Obtener notificaciones del usuario actual
    public function misNotificaciones(Request $request)
    {
        $usuario = $request->user();
        
        $notificaciones = Notificacion::where('NumeroDocumento', $usuario->NumeroDocumento)
            ->orderBy('FechaCreacion', 'desc')
            ->get();

        $noLeidas = $notificaciones->where('Leida', false)->count();

        return response()->json([
            'success' => true,
            'data' => $notificaciones,
            'noLeidas' => $noLeidas
        ]);
    }

    // Marcar notificación como leída
    public function marcarLeida($idNotificacion)
    {
        $notificacion = Notificacion::findOrFail($idNotificacion);
        $notificacion->update(['Leida' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída'
        ]);
    }

    // Marcar todas como leídas
    public function marcarTodasLeidas(Request $request)
    {
        $usuario = $request->user();
        
        Notificacion::where('NumeroDocumento', $usuario->NumeroDocumento)
            ->where('Leida', false)
            ->update(['Leida' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones marcadas como leídas'
        ]);
    }

    // Eliminar notificación
    public function eliminar($idNotificacion)
    {
        $notificacion = Notificacion::findOrFail($idNotificacion);
        $notificacion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notificación eliminada'
        ]);
    }
}