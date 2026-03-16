<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Modulo extends Model
{
    protected $table = 'modulos';
    protected $primaryKey = 'IdModulo';
    public $timestamps = false;

    protected $fillable = [
        'NombreModulo',
        'Descripcion',
        'Icono',
        'Ruta',
        'Orden',
        'Activo'
    ];

    protected $casts = [
        'Activo' => 'boolean',
        'Orden' => 'integer'
    ];

    // Relación con permisos
    public function permisos()
    {
        return $this->hasMany(Permiso::class, 'IdModulo', 'IdModulo');
    }

    // Relación con solicitudes
    public function solicitudes()
    {
        return $this->hasMany(SolicitudAcceso::class, 'IdModulo', 'IdModulo');
    }
}