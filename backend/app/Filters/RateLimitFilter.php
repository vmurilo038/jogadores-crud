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

        $identificador = hash('sha256', $grupo . '|' . $ip);

        $chaveTentativas = 'rate_limit_tentativas_' . $identificador;
        $chaveBloqueio = 'rate_limit_bloqueio_' . $identificador;

        $cache = cache();

        $agora = time();

        // Verifica se já existe um bloqueio ativo.
        $bloqueioAte = $cache->get($chaveBloqueio);

        if ($bloqueioAte !== null) {
            $tempoEspera = (int) $bloqueioAte - $agora;

            if ($tempoEspera > 0) {
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

            $cache->delete($chaveBloqueio);
            $cache->delete($chaveTentativas);
        }

        // Recupera a quantidade de tentativas.
        $tentativas = $cache->get($chaveTentativas);

        if ($tentativas === null) {
            $tentativas = 1;

            $cache->save(
                $chaveTentativas,
                $tentativas,
                $segundos
            );

            return;
        }

        $tentativas++;

        // Se ultrapassou o limite, inicia um bloqueio de 60 segundos.
        if ($tentativas > $limite) {
            $bloqueioAte = $agora + $segundos;

            $cache->save(
                $chaveBloqueio,
                $bloqueioAte,
                $segundos + 1
            );

            return $response
                ->setStatusCode(429)
                ->setHeader('Retry-After', (string) $segundos)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'Limite de requisições excedido. Tente novamente mais tarde.',
                    'limite' => $limite,
                    'intervalo_segundos' => $segundos,
                    'tente_novamente_em' => $segundos
                ]);
        }

        $cache->save(
            $chaveTentativas,
            $tentativas,
            $segundos
        );
    }

    public function after(
        RequestInterface $request,
        ResponseInterface $response,
        $arguments = null
    ) {
    }
}
