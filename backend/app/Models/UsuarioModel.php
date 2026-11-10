<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'nome',
        'email',
        'senha',
        'imagem'
    ];

    public function buscarPorEmail(string $email): ?array
    {
        return $this->where('email', $email)->first();
    }

    public function validarCadastro(array $dados): array
    {
        $nome = trim($dados['nome'] ?? '');
        $email = trim($dados['email'] ?? '');
        $senha = $dados['senha'] ?? '';

        if ($nome === '' || $email === '' || $senha === '') {
            return [
                'valido' => false,
                'mensagem' => 'Preencha todos os campos.',
                'status' => 400
            ];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'valido' => false,
                'mensagem' => 'Informe um e-mail válido.',
                'status' => 400
            ];
        }

        if (strlen($senha) < 6) {
            return [
                'valido' => false,
                'mensagem' => 'A senha precisa ter pelo menos 6 caracteres.',
                'status' => 400
            ];
        }

        if ($this->buscarPorEmail($email)) {
            return [
                'valido' => false,
                'mensagem' => 'Este e-mail já está cadastrado.',
                'status' => 409
            ];
        }

        return [
            'valido' => true,
            'mensagem' => 'Dados válidos.',
            'status' => 200,
            'dados' => [
                'nome' => $nome,
                'email' => $email,
                'senha' => $senha
            ]
        ];
    }

    public function cadastrarUsuario(array $dados)
    {
        return $this->insert([
            'nome' => $dados['nome'],
            'email' => $dados['email'],
            'senha' => password_hash($dados['senha'], PASSWORD_DEFAULT)
        ], true);
    }

    public function autenticar(string $email, string $senha): ?array
    {
        $usuario = $this->buscarPorEmail($email);

        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            return null;
        }

        return $usuario;
    }
}