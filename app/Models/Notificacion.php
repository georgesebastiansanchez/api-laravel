<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';
    protected $primaryKey = 'IdNotificacion';
    public $timestamps = false;

    protected $fillable = [
        'NumeroDocumento',
        'Tipo',
        'Titulo',
        'Mensaje',
        'Leida',
        'FechaCreacion',  // ⬅️ AGREGA ESTA LÍNEA
        'IdReferencia'
    ];

    protected $casts = [
        'Leida' => 'boolean',
        'FechaCreacion' => 'datetime',
    ];

    protected $attributes = [
        'Leida' => false
    ];

    // Agregar esto para que auto-asigne la fecha
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($notificacion) {
            if (!$notificacion->FechaCreacion) {
                $notificacion->FechaCreacion = now();
            }
        });
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'NumeroDocumento', 'NumeroDocumento');
    }

    public function scopeNoLeidas($query)
    {
        return $query->where('Leida', false);
    }

    public function marcarComoLeida()
    {
        $this->update(['Leida' => true]);
    }
}