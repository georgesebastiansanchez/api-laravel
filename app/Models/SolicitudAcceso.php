<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SolicitudAcceso extends Model
{
    protected $table = 'solicitudes_acceso';
    protected $primaryKey = 'IdSolicitud';
    public $timestamps = false;

    protected $fillable = [
        'NumeroDocumento',
        'IdModulo',
        'Justificacion',
        'Estado',
        'FechaSolicitud',  // ⬅️ AGREGA ESTA LÍNEA
        'FechaRespuesta',
        'RespondidoPor',
        'ComentarioAdmin'
    ];

    protected $casts = [
        'FechaSolicitud' => 'datetime',
        'FechaRespuesta' => 'datetime',
    ];

    protected $attributes = [
        'Estado' => 'pendiente'
    ];

    // Agregar esto para que auto-asigne la fecha
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($solicitud) {
            if (!$solicitud->FechaSolicitud) {
                $solicitud->FechaSolicitud = now();
            }
        });
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'NumeroDocumento', 'NumeroDocumento');
    }

    public function modulo()
    {
        return $this->belongsTo(Modulo::class, 'IdModulo', 'IdModulo');
    }

    public function scopePendientes($query)
    {
        return $query->where('Estado', 'pendiente');
    }

    public function scopeAprobadas($query)
    {
        return $query->where('Estado', 'aprobada');
    }

    public function scopeRechazadas($query)
    {
        return $query->where('Estado', 'rechazada');
    }
}