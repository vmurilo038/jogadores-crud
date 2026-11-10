<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Cors extends BaseConfig
{
    public array $default = [
        'allowedOrigins' => [
            'http://localhost:5173',
            'http://127.0.0.1:5173'
        ],

        'allowedOriginsPatterns' => [],

        // Não utilizamos autenticação por cookies.
        // O sistema utiliza JWT pelo cabeçalho Authorization.
        'supportsCredentials' => false,

        'allowedHeaders' => [
            'Content-Type',
            'Authorization',
            'X-Requested-With',
            'Accept'
        ],

        // Permite ao React acessar o cabeçalho do Rate Limit.
        'exposedHeaders' => [
            'Retry-After'
        ],

        'allowedMethods' => [
            'GET',
            'POST',
            'PUT',
            'PATCH',
            'DELETE',
            'OPTIONS'
        ],

        'maxAge' => 3600
    ];
}
