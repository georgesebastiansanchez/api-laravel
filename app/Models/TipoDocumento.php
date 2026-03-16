<?php

// ============================================
// MODELO: TipoDocumento
// ============================================

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    use HasFactory;

    protected $table = 'TipoDocumento';
    protected $primaryKey = 'IdTipoDocumento';

    protected $fillable = [
        'Nombre'
    ];

    public $timestamps = false;

    // Relación: Un tipo de documento tiene muchos usuarios
    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'IdTipoDocumento');
    }
}