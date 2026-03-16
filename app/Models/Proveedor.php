<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Proveedor extends Model
{
    use HasFactory;

    protected $table = 'Proveedores';
    protected $primaryKey = 'IdProveedor';

    protected $fillable = [
        'NombreProveedor',
        'Contacto',
        'Telefono',
        'Email',
        'Direccion',
        'Activo'
    ];

    protected $casts = [
        'Activo' => 'boolean'
    ];

    public $timestamps = false;

    // Relación: Un proveedor tiene muchas compras
    public function compras()
    {
        return $this->hasMany(Compra::class, 'IdProveedor');
    }
}