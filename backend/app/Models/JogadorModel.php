<?php

namespace App\Models;

use CodeIgniter\Model;

class JogadorModel extends Model
{
    protected $table = 'jogadores';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useAutoIncrement = true;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'nome',
        'idade',
        'posicao',
        'numero_camisa',
        'imagem'
    ];

    public function listarJogadores(): array
    {
        return $this->orderBy('id', 'ASC')->findAll();
    }

    public function buscarJogador(int $id): ?array
    {
        return $this->find($id);
    }

    public function validarDados(array $dados): array
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $idade = $dados['idade'] ?? null;
        $posicao = trim((string) ($dados['posicao'] ?? ''));
        $numeroCamisa = $dados['numero_camisa'] ?? null;

        if ($nome === '' || $idade === null || $idade === '' || $posicao === '') {
            return [
                'valido' => false,
                'mensagem' => 'Preencha nome, idade e posição.'
            ];
        }

        if (!is_numeric($idade) || (int) $idade < 1 || (int) $idade > 100) {
            return [
                'valido' => false,
                'mensagem' => 'Informe uma idade válida.'
            ];
        }

        if (
            $numeroCamisa !== null &&
            $numeroCamisa !== '' &&
            (
                !is_numeric($numeroCamisa) ||
                (int) $numeroCamisa < 1 ||
                (int) $numeroCamisa > 99
            )
        ) {
            return [
                'valido' => false,
                'mensagem' => 'Informe um número de camisa entre 1 e 99.'
            ];
        }

        return [
            'valido' => true,
            'mensagem' => 'Dados válidos.',
            'dados' => [
                'nome' => $nome,
                'idade' => (int) $idade,
                'posicao' => $posicao,
                'numero_camisa' => (
                    $numeroCamisa !== null && $numeroCamisa !== ''
                ) ? (int) $numeroCamisa : null
            ]
        ];
    }

    public function cadastrarJogador(array $dados)
    {
        return $this->insert($dados, true);
    }

    public function atualizarJogador(int $id, array $dados): bool
    {
        return $this->update($id, $dados);
    }

    public function excluirJogador(int $id): bool
    {
        return $this->delete($id);
    }
}
