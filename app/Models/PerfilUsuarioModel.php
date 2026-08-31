<?php
namespace App\Models;

use CodeIgniter\Model;

class PerfilUsuarioModel extends Model
{
    protected $table = 'perfiles_usuario';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['nombre', 'apellido', 'telefono', 'direccion'];
}

