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

        'supportsCredentials' => false,

        'allowedHeaders' => [
            'Content-Type',
            'Authorization',
            'X-Requested-With',
            'Accept'
        ],

        'exposedHeaders' => [],

        'allowedMethods' => [
            'GET',
            'POST',
            'PUT',
            'PATCH',
            'DELETE',
            'OPTIONS'
        ],

        'maxAge' => 7200
    ];
}
