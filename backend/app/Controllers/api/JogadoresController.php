<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\JogadorModel;
use CodeIgniter\HTTP\ResponseInterface;

class JogadoresController extends BaseController
{
    private function resposta($mensagem, $status = 200, $dados = [])
    {
        return $this->response
            ->setStatusCode($status)
            ->setJSON(array_merge([
                'status' => $status < 400,
                'mensagem' => $mensagem
            ], $dados));
    }

    private function salvarImagem($arquivo)
    {
        if (!$arquivo || !$arquivo->isValid() || $arquivo->hasMoved()) {
            return null;
        }

        $tiposPermitidos = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (!in_array($arquivo->getMimeType(), $tiposPermitidos, true)) {
            throw new \RuntimeException('Envie uma imagem JPG, PNG ou WEBP.');
        }

        if ($arquivo->getSizeByUnit('mb') > 5) {
            throw new \RuntimeException('A imagem deve ter no máximo 5 MB.');
        }

        $diretorio = FCPATH . 'uploads/jogadores';

        if (!is_dir($diretorio)) {
            mkdir($diretorio, 0755, true);
        }

        $nomeArquivo = $arquivo->getRandomName();
        $arquivo->move($diretorio, $nomeArquivo);

        return 'uploads/jogadores/' . $nomeArquivo;
    }

    private function removerImagem($caminho)
    {
        if (!$caminho) {
            return;
        }

        $arquivo = FCPATH . $caminho;

        if (is_file($arquivo)) {
            unlink($arquivo);
        }
    }

    public function index()
    {
        $jogadores = new JogadorModel();

        return $this->resposta(
            'Jogadores encontrados.',
            200,
            ['jogadores' => $jogadores->orderBy('nome', 'ASC')->findAll()]
        );
    }

    public function show($id = null)
    {
        $jogadores = new JogadorModel();
        $jogador = $jogadores->find($id);

        if (!$jogador) {
            return $this->resposta('Jogador não encontrado.', 404);
        }

        return $this->resposta(
            'Jogador encontrado.',
            200,
            ['jogador' => $jogador]
        );
    }

    public function criar()
    {
        $nome = trim((string) $this->request->getPost('nome'));
        $idade = $this->request->getPost('idade');
        $posicao = trim((string) $this->request->getPost('posicao'));
        $numeroCamisa = $this->request->getPost('numero_camisa');

        if ($nome === '' || $idade === null || $posicao === '') {
            return $this->resposta(
                'Preencha nome, idade e posição.',
                400
            );
        }

        if (!is_numeric($idade) || (int) $idade < 1 || (int) $idade > 100) {
            return $this->resposta('Informe uma idade válida.', 400);
        }

        if ($numeroCamisa !== null && $numeroCamisa !== '' &&
            (!is_numeric($numeroCamisa) || (int) $numeroCamisa < 1 || (int) $numeroCamisa > 99)) {
            return $this->resposta('Informe um número de camisa entre 1 e 99.', 400);
        }

        try {
            $imagem = $this->salvarImagem($this->request->getFile('imagem'));

            $jogadores = new JogadorModel();

            $id = $jogadores->insert([
                'nome' => $nome,
                'idade' => (int) $idade,
                'posicao' => $posicao,
                'numero_camisa' => $numeroCamisa !== '' ? $numeroCamisa : null,
                'imagem' => $imagem
            ], true);

            if (!$id) {
                $this->removerImagem($imagem);
                return $this->resposta('Não foi possível cadastrar o jogador.', 500);
            }

            return $this->resposta(
                'Jogador cadastrado com sucesso.',
                201,
                ['jogador' => $jogadores->find($id)]
            );
        } catch (\Throwable $e) {
            return $this->resposta($e->getMessage(), 400);
        }
    }

    public function atualizar($id = null)
    {
        $jogadores = new JogadorModel();
        $jogador = $jogadores->find($id);

        if (!$jogador) {
            return $this->resposta('Jogador não encontrado.', 404);
        }

        $nome = trim((string) $this->request->getPost('nome'));
        $idade = $this->request->getPost('idade');
        $posicao = trim((string) $this->request->getPost('posicao'));
        $numeroCamisa = $this->request->getPost('numero_camisa');

        if ($nome === '' || $idade === null || $posicao === '') {
            return $this->resposta(
                'Preencha nome, idade e posição.',
                400
            );
        }

        if (!is_numeric($idade) || (int) $idade < 1 || (int) $idade > 100) {
            return $this->resposta('Informe uma idade válida.', 400);
        }

        if ($numeroCamisa !== null && $numeroCamisa !== '' &&
            (!is_numeric($numeroCamisa) || (int) $numeroCamisa < 1 || (int) $numeroCamisa > 99)) {
            return $this->resposta('Informe um número de camisa entre 1 e 99.', 400);
        }

        try {
            $dados = [
                'nome' => $nome,
                'idade' => (int) $idade,
                'posicao' => $posicao,
                'numero_camisa' => $numeroCamisa !== '' ? $numeroCamisa : null
            ];

            $arquivo = $this->request->getFile('imagem');

            if ($arquivo && $arquivo->isValid()) {
                $novaImagem = $this->salvarImagem($arquivo);
                $dados['imagem'] = $novaImagem;
            }

            $jogadores->update($id, $dados);

            if (isset($novaImagem)) {
                $this->removerImagem($jogador['imagem']);
            }

            return $this->resposta(
                'Jogador atualizado com sucesso.',
                200,
                ['jogador' => $jogadores->find($id)]
            );
        } catch (\Throwable $e) {
            return $this->resposta($e->getMessage(), 400);
        }
    }

    public function excluir($id = null)
    {
        $jogadores = new JogadorModel();
        $jogador = $jogadores->find($id);

        if (!$jogador) {
            return $this->resposta('Jogador não encontrado.', 404);
        }

        $jogadores->delete($id);
        $this->removerImagem($jogador['imagem']);

        return $this->resposta('Jogador excluído com sucesso.');
    }
}
