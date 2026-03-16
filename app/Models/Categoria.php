<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $table = 'categorias';
    protected $primaryKey = 'IdCategoria';
    public $timestamps = false;

    protected $fillable = [
        'Nombre',
        'Descripcion'
    ];

    // Relación: una categoría tiene muchos productos
    public function productos()
    {
        return $this->hasMany(Producto::class, 'IdCategoria', 'IdCategoria');
    }
}
