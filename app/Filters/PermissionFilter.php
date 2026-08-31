<?php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $action = $arguments[0] ?? null;

        if (!$action) {
            return service('response')->setStatusCode(403)
                ->setJSON(['error' => 'No se definió la acción solicitada.']);
        }

        // JwtFilter ya validó el token en las rutas protegidas.
        $user = $request->user ?? null;
        // Si no existe JWT, se usa el rol lógico público.
        $rol = $user['rol'] ?? 'publico';

        $db = db_connect();
        $allowed = $db->table('rol_accion ra')
            ->select('ra.permitido')
            ->join('roles r', 'r.id = ra.rol_id')
            ->join('acciones a', 'a.id = ra.accion_id')
            ->where('r.nombre', $rol)
            ->where('a.codigo', $action)
            ->where('r.activo', true)
            ->where('a.activo', true)
            ->where('ra.permitido', true)
            ->get()->getRowArray();

        if (!$allowed) {
            return service('response')->setStatusCode(403)
                ->setJSON([
                    'error' => 'El rol no tiene permiso para esta acción.',
                    'rol' => $rol,
                    'accion' => $action
                ]);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}

