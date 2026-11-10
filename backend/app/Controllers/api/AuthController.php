<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UsuarioModel;
use App\Models\TokenRevogadoModel;
use App\Services\LogService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

class AuthController extends BaseController
{
    private UsuarioModel $usuarios;
    private TokenRevogadoModel $tokens;
    private LogService $log;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->usuarios = new UsuarioModel();
        $this->tokens = new TokenRevogadoModel();
        $this->log = new LogService();
    }

    private function segredo()
    {
        $segredo = env('JWT_SECRET');

        if (!$segredo || strlen($segredo) < 32) {
            throw new \RuntimeException(
                'JWT_SECRET não configurado corretamente.'
            );
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

        $validacao = $this->usuarios->validarCadastro($dados);

        if (!$validacao['valido']) {
            return $this->response
                ->setStatusCode($validacao['status'])
                ->setJSON([
                    'status' => false,
                    'mensagem' => $validacao['mensagem']
                ]);
        }

        $id = $this->usuarios->cadastrarUsuario($validacao['dados']);

        if (!$id) {
            return $this->response
                ->setStatusCode(500)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'Não foi possível cadastrar o usuário.'
                ]);
        }

        $usuario = $this->usuarios->find($id);

        $this->log->registrar('info', 'Usuário cadastrado com sucesso.', [
            'usuario_id' => $id
        ]);

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

        $usuario = $this->usuarios->autenticar($email, $senha);

        if (!$usuario) {
            $this->log->registrar('warning', 'Tentativa de login inválida.', [
                'email' => $email
            ]);

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

        $this->log->registrar('info', 'Login realizado com sucesso.', [
            'usuario_id' => $usuario['id']
        ]);

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

            $usuario = $this->usuarios->find((int) $dados->sub);

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

            $resultado = $this->tokens->revogarToken(
                $dados->jti,
                (int) $dados->exp
            );

            if (!$resultado) {
                throw new \RuntimeException(
                    'Não foi possível revogar o token.'
                );
            }

            $this->log->registrar('info', 'Logout realizado com sucesso.', [
                'usuario_id' => $dados->sub
            ]);

            return $this->response->setJSON([
                'status' => true,
                'mensagem' => 'Logout realizado com sucesso.'
            ]);

        } catch (Throwable $e) {
            $this->log->registrar('error', 'Erro ao realizar logout.', [
                'erro' => $e->getMessage()
            ]);

            return $this->response
                ->setStatusCode(401)
                ->setJSON([
                    'status' => false,
                    'mensagem' => 'Não foi possível encerrar esta sessão.'
                ]);
        }
    }
}