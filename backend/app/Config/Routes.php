<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->get('/', 'Home::index');

$routes->group('api', ['filter' => 'cors'], static function ($routes) {

    // Autenticação com limite de requisições

    $routes->post(
        'auth/registrar',
        'Api\AuthController::registrar',
        ['filter' => 'ratelimit:3,60,registrar']
    );

    $routes->post(
        'auth/login',
        'Api\AuthController::login',
        ['filter' => 'ratelimit:5,60,login']
    );

    // Requisições OPTIONS (CORS)

    $routes->options('(:any)', static function () {
        return service('response')->setStatusCode(204);
    });

    // Rotas protegidas por Rate Limit e JWT

    $routes->group('', [
        'filter' => [
            'ratelimit:120,60,api',
            'jwt'
        ]
    ], static function ($routes) {

        // Autenticação

        $routes->get(
            'auth/me',
            'Api\AuthController::me'
        );

        $routes->post(
            'auth/logout',
            'Api\AuthController::logout'
        );

        // Jogadores

        $routes->get(
            'jogadores',
            'Api\JogadoresController::index'
        );

        $routes->get(
            'jogadores/(:num)',
            'Api\JogadoresController::show/$1'
        );

        $routes->post(
            'jogadores/criar',
            'Api\JogadoresController::criar'
        );

        $routes->post(
            'jogadores/atualizar/(:num)',
            'Api\JogadoresController::atualizar/$1'
        );

        $routes->delete(
            'jogadores/(:num)',
            'Api\JogadoresController::excluir/$1'
        );

        // Logs

        $routes->get(
            'logs',
            'Api\LogsController::index'
        );
    });
});
