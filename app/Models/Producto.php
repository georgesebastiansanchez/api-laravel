<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use App\Models\Categoria; // 👈 IMPORTA tu modelo Categoria

class Producto extends Model
{
    protected $table = 'productos';
    protected $primaryKey = 'IdProducto';
    public $timestamps = false;

    protected $fillable = [
        'Nombre',
        'Descripcion',
        'Marca',
        'Color',
        'Talla',
        'PrecioBase',
        'IdCategoria',
        'Stock',
        'Activo',
        'FechaCreacion'
    ];

    // Relación: un producto pertenece a una categoría
    public function categoria()
    {
        return $this->belongsTo(Categoria::class, 'IdCategoria', 'IdCategoria');
    }
}
