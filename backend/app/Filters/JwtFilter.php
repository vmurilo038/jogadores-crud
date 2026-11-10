<?php

namespace App\Filters;

use App\Models\TokenRevogadoModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

class JwtFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $resposta = service('response');

        $cabecalho = $request->getHeaderLine('Authorization');

        if (!preg_match('/Bearer\s+(\S+)/', $cabecalho, $matches)) {
            return $resposta
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'Token não informado.'
                ]);
        }

        $token = $matches[1];

        $segredo = env('JWT_SECRET');

        if (!$segredo || strlen($segredo) < 32) {
            return $resposta
                ->setStatusCode(500)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'Chave JWT não configurada corretamente.'
                ]);
        }

        try {
            $dados = JWT::decode(
                $token,
                new Key($segredo, 'HS256')
            );

            if (
                !isset($dados->iss) ||
                $dados->iss !== 'jogadores-api' ||
                !isset($dados->aud) ||
                $dados->aud !== 'jogadores-frontend' ||
                !isset($dados->sub) ||
                !isset($dados->jti)
            ) {
                throw new \Exception('Token inválido.');
            }

            $tokens = new TokenRevogadoModel();

            if ($tokens->tokenFoiRevogado($dados->jti)) {
                throw new \Exception('Token revogado.');
            }

        } catch (Throwable $e) {
            return $resposta
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'Token inválido ou expirado. Faça login novamente.'
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