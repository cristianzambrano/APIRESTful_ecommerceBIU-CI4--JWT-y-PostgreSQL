<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

$routes->group('api', function ($routes) {
    $routes->post('auth/register', 'API\AuthController::register');
    $routes->post('auth/login', 'API\AuthController::login');

    // Público: no requiere JWT.
    $routes->get('productos', 'API\ProductosController::index');

    // Operador y supervisor: primero JWT, luego permiso en BD.
    $routes->post('productos', 'API\ProductosController::create', [
        'filter' => ['jwt', 'permission:productos.agregar']
    ]);
    $routes->put('productos/(:num)', 'API\ProductosController::update/$1', [
        'filter' => ['jwt', 'permission:productos.editar']
    ]);
    $routes->delete('productos/(:num)', 'API\ProductosController::delete/$1', [
        'filter' => ['jwt', 'permission:productos.eliminar']
    ]);

});

