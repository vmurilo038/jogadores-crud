<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('/', 'Home::index');

$routes->group('api', ['filter' => 'cors'], static function ($routes) {

    // Autenticação
    $routes->post('auth/registrar', 'Api\AuthController::registrar');
    $routes->post('auth/login', 'Api\AuthController::login');

    // Requisições OPTIONS (CORS)
    $routes->options('(:any)', static function () {
        return service('response')->setStatusCode(204);
    });

    // Rotas protegidas por JWT
    $routes->group('', ['filter' => 'jwt'], static function ($routes) {

        // Autenticação
        $routes->get('auth/me', 'Api\AuthController::me');
        $routes->post('auth/logout', 'Api\AuthController::logout');

        // Jogadores
        $routes->get('jogadores', 'Api\JogadoresController::index');
        $routes->get('jogadores/(:num)', 'Api\JogadoresController::show/$1');

        $routes->post('jogadores/criar', 'Api\JogadoresController::criar');
        $routes->post('jogadores/atualizar/(:num)', 'Api\JogadoresController::atualizar/$1');

        $routes->delete('jogadores/(:num)', 'Api\JogadoresController::excluir/$1');
    });
});
