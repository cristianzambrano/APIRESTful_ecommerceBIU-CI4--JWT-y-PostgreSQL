<?php
namespace App\Models;

use CodeIgniter\Model;

class ProductoModel extends Model
{
    protected $table = 'productos';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'nombre', 'descripcion', 'categoria', 'precio', 'stock',
        'imagen_url', 'activo', 'creado_por'
    ];
}

