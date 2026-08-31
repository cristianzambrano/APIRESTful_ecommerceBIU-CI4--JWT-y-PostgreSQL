<?php
namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $header = $request->getHeaderLine('Authorization');
        if (!preg_match('/^Bearer\s+(.*)$/i', $header, $matches)) {
            return service('response')->setStatusCode(401)
                ->setJSON(['error' => 'Debe enviar Authorization: Bearer token.']);
        }

        try {
            $decoded = JWT::decode($matches[1], new Key(env('jwt.secret'), 'HS256'));
            $request->user = (array) $decoded;
        } catch (\Throwable $e) {
            return service('response')->setStatusCode(401)
                ->setJSON(['error' => 'JWT inválido, vencido o alterado.']);
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null) {}
}

