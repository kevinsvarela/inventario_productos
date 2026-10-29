<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoModel extends Model {
    protected $table = 'productos';

    public $timestamps = false;

    protected $fillable = [
        'sku',
        'nombre_producto',
        'descripcion',
        'precio_unitario',
        'stock_actual',
        'categoria',
    ];

    protected $casts = [
        'id' => 'integer',
        'precio_unitario' => 'float',
        'stock_actual' => 'integer',
    ];
}
