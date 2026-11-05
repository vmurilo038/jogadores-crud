<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UsuarioModel;
use App\Models\TokenRevogadoModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

class AuthController extends BaseController
{
    private function segredo()
    {
        $segredo = env('JWT_SECRET');

        if (!$segredo || strlen($segredo) < 32) {
            throw new \RuntimeException('JWT_SECRET não configurado corretamente.');
        }

        return $segredo;
    }

    private function usuarioPublico(array $usuario): array
    {
        return [
            'id' => $usuario['id'],
            'nome' => $usuario['nome'],
            'email' => $usuario['email'],
            'imagem' => $usuario['imagem'] ?? null
        ];
    }

    private function obterToken()
    {
        $cabecalho = $this->request->getHeaderLine('Authorization');

        if (!preg_match('/Bearer\s+(\S+)/', $cabecalho, $matches)) {
            return null;
        }

        return $matches[1];
    }

    public function registrar()
    {
        $dados = $this->request->getJSON(true) ?? [];

        $nome = trim($dados['nome'] ?? '');
        $email = trim($dados['email'] ?? '');
        $senha = $dados['senha'] ?? '';

        if ($nome === '' || $email === '' || $senha === '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'Preencha todos os campos.'
                ]);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'Informe um e-mail válido.'
                ]);
        }

        if (strlen($senha) < 6) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'A senha precisa ter pelo menos 6 caracteres.'
                ]);
        }

        $usuarios = new UsuarioModel();

        $existente = $usuarios
            ->where('email', $email)
            ->first();

        if ($existente) {
            return $this->response
                ->setStatusCode(409)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'Este e-mail já está cadastrado.'
                ]);
        }

        $id = $usuarios->insert([
            'nome' => $nome,
            'email' => $email,
            'senha' => password_hash($senha, PASSWORD_DEFAULT)
        ], true);

        if (!$id) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'Não foi possível cadastrar o usuário.'
                ]);
        }

        $usuario = $usuarios->find($id);

        return $this->response
            ->setStatusCode(201)
            ->setJSON([
                'status' => true,
                'mensagem' => 'Usuário cadastrado com sucesso.',
                'usuario' => $this->usuarioPublico($usuario)
            ]);
    }

    public function login()
    {
        $dados = $this->request->getJSON(true) ?? [];

        $email = trim($dados['email'] ?? '');
        $senha = $dados['senha'] ?? '';

        if ($email === '' || $senha === '') {
            return $this->response
                ->setStatusCode(400)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'Informe seu e-mail e sua senha.'
                ]);
        }

        $usuarios = new UsuarioModel();

        $usuario = $usuarios
            ->where('email', $email)
            ->first();

        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'E-mail ou senha incorretos.'
                ]);
        }

        $agora = time();
        $expiracao = $agora + (int) env('JWT_EXPIRATION', 3600);

        $payload = [
            'iss' => 'jogadores-api',
            'aud' => 'jogadores-frontend',
            'iat' => $agora,
            'nbf' => $agora,
            'exp' => $expiracao,
            'sub' => (string) $usuario['id'],
            'jti' => bin2hex(random_bytes(16))
        ];

        $token = JWT::encode(
            $payload,
            $this->segredo(),
            'HS256'
        );

        return $this->response->setJSON([
            'status' => true,
            'mensagem' => 'Login realizado com sucesso.',
            'token' => $token,
            'usuario' => $this->usuarioPublico($usuario)
        ]);
    }

    public function me()
    {
        $token = $this->obterToken();

        if (!$token) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'Token não informado.'
                ]);
        }

        try {
            $dados = JWT::decode(
                $token,
                new Key($this->segredo(), 'HS256')
            );

            $usuarios = new UsuarioModel();
            $usuario = $usuarios->find((int) $dados->sub);

            if (!$usuario) {
                return $this->response
                    ->setStatusCode(404)
                    ->setJSON([
                        'status' => false,
                        'mensagem' => 'Usuário não encontrado.'
                    ]);
            }

            return $this->response->setJSON([
                'status' => true,
                'usuario' => $this->usuarioPublico($usuario)
            ]);

        } catch (Throwable $e) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'Sessão inválida.'
                ]);
        }
    }

    public function logout()
    {
        $token = $this->obterToken();

        if (!$token) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'Token não informado.'
                ]);
        }

        try {
            $dados = JWT::decode(
                $token,
                new Key($this->segredo(), 'HS256')
            );

            $tokens = new TokenRevogadoModel();

            $tokens->insert([
                'jti' => $dados->jti,
                'expira_em' => date('Y-m-d H:i:s', (int) $dados->exp)
            ]);

            return $this->response->setJSON([
                'status' => true,
                'mensagem' => 'Logout realizado com sucesso.'
            ]);

        } catch (Throwable $e) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'Não foi possível encerrar esta sessão.'
                ]);
        }
    }
}
