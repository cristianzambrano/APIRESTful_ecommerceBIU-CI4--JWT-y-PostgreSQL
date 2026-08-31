<?php
namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'perfil_id', 'email', 'password_hash', 'rol_id', 'activo', 'ultimo_login'
    ];
}

