<?php
namespace App\Controllers\API;

use App\Controllers\BaseController;
use App\Models\PerfilUsuarioModel;
use App\Models\UsuarioModel;
use Firebase\JWT\JWT;
use CodeIgniter\API\ResponseTrait;

class AuthController extends BaseController
{
    use ResponseTrait;

    public function register()
    {
        $data = $this->request->getJSON(true);
        $required = ['nombre', 'email', 'password'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                return $this->failValidationErrors("El campo {$field} es obligatorio.");
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || strlen($data['password']) < 8) {
            return $this->failValidationErrors('Correo o contraseña no válidos.');
        }

        $db = db_connect();
        $role = $db->table('roles')->where('nombre', 'supervisor')->get()->getRowArray();

        if (!$role) {
            return $this->failServerError('No existe el rol inicial.');
        }

        $perfilModel = new PerfilUsuarioModel();
        $perfilId = $perfilModel->insert([
            'nombre' => trim($data['nombre']),
            'apellido' => $data['apellido'] ?? null,
            'telefono' => $data['telefono'] ?? null,
            'direccion' => $data['direccion'] ?? null,
        ], true);

        $usuarioModel = new UsuarioModel();
        try {
            $usuarioId = $usuarioModel->insert([
                'perfil_id' => $perfilId,
                'email' => strtolower(trim($data['email'])),
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
                'rol_id' => $role['id'],
            ], true);
        } catch (\Throwable $e) {
            $perfilModel->delete($perfilId);
            return $this->failValidationErrors('El correo ya está registrado.');
        }

        return $this->respondCreated([
            'mensaje' => 'Usuario registrado correctamente.',
            'usuario_id' => $usuarioId,
            'rol_inicial' => 'supervisor'
        ]);
    }

    public function login()
    {
        $data = $this->request->getJSON(true);
        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        $db = db_connect();
        $usuario = $db->table('usuarios u')
            ->select('u.*, r.nombre AS rol, p.nombre, p.apellido')
            ->join('roles r', 'r.id = u.rol_id')
            ->join('perfiles_usuario p', 'p.id = u.perfil_id')
            ->where('u.email', $email)
            ->where('u.activo', true)
            ->get()->getRowArray();

        if (!$usuario || !password_verify($password, $usuario['password_hash'])) {
            return $this->failUnauthorized('Credenciales incorrectas.');
        }

        $now = time();
        $payload = [
            'iss' => 'ecommerce-api',
            'aud' => 'ecommerce-client',
            'iat' => $now,
            'exp' => $now + (int) env('jwt.ttl', 3600),
            'sub' => (string) $usuario['id'],
            'rol' => $usuario['rol'],
            'email' => $usuario['email'],
        ];

        $token = JWT::encode($payload, env('jwt.secret'), 'HS256');
        (new UsuarioModel())->update($usuario['id'], ['ultimo_login' => date('Y-m-d H:i:s')]);

        return $this->respond([
            'token_type' => 'Bearer',
            'access_token' => $token,
            'expires_in' => $payload['exp'] - $now,
            'usuario' => [
                'id' => $usuario['id'], 'email' => $usuario['email'],
                'nombre' => trim($usuario['nombre'].' '.$usuario['apellido']),
                'rol' => $usuario['rol']
            ]
        ]);
    }
}

