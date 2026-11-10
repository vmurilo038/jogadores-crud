<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class RateLimitFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $response = service('response');

        $limite = isset($arguments[0])
            ? (int) $arguments[0]
            : 60;

        $segundos = isset($arguments[1])
            ? (int) $arguments[1]
            : 60;

        $grupo = isset($arguments[2])
            ? $arguments[2]
            : 'api';

        if ($limite < 1 || $segundos < 1) {
            return $response
                ->setStatusCode(500)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'Configuração de limite de requisições inválida.'
                ]);
        }

        $ip = $request->getIPAddress();

        $chave = hash(
            'sha256',
            $grupo . '|' . $ip
        );

        $throttler = service('throttler');

        $permitido = $throttler->check(
            $chave,
            $limite,
            $segundos
        );

        if (!$permitido) {
            $tempoEspera = $throttler->getTokenTime();

            return $response
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) $tempoEspera)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'Limite de requisições excedido. Tente novamente mais tarde.',
                    'limite' => $limite,
                    'intervalo_segundos' => $segundos,
                    'tente_novamente_em' => $tempoEspera
                ]);
        }
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
    }
}