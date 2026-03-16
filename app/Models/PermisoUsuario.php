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

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'NumeroDocumento', 'NumeroDocumento');
    }

    public function modulo()
    {
        return $this->belongsTo(Modulo::class, 'IdModulo', 'IdModulo');
    }
}