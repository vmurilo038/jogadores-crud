<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\JogadorModel;
use App\Services\LogService;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

class JogadoresController extends BaseController
{
    private JogadorModel $jogadores;
    private LogService $log;

    public function initController(
        RequestInterface $request,
        ResponseInterface $response,
        LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->jogadores = new JogadorModel();
        $this->log = new LogService();
    }

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
            throw new \RuntimeException(
                'Envie uma imagem JPG, PNG ou WEBP.'
            );
        }

        if ($arquivo->getSizeByUnit('mb') > 5) {
            throw new \RuntimeException(
                'A imagem deve ter no máximo 5 MB.'
            );
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
        return $this->resposta(
            'Jogadores encontrados.',
            200,
            [
                'jogadores' => $this->jogadores->listarJogadores()
            ]
        );
    }

    public function show($id = null)
    {
        $jogador = $this->jogadores->buscarJogador((int) $id);

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
        $validacao = $this->jogadores->validarDados([
            'nome' => $this->request->getPost('nome'),
            'idade' => $this->request->getPost('idade'),
            'posicao' => $this->request->getPost('posicao'),
            'numero_camisa' => $this->request->getPost('numero_camisa')
        ]);

        if (!$validacao['valido']) {
            return $this->resposta($validacao['mensagem'], 400);
        }

        $imagem = null;

        try {
            $imagem = $this->salvarImagem(
                $this->request->getFile('imagem')
            );

            $dados = $validacao['dados'];
            $dados['imagem'] = $imagem;

            $id = $this->jogadores->cadastrarJogador($dados);

            if (!$id) {
                $this->removerImagem($imagem);

                return $this->resposta(
                    'Não foi possível cadastrar o jogador.',
                    500
                );
            }

            $this->log->registrar('info', 'Jogador cadastrado com sucesso.', [
                'jogador_id' => $id
            ]);

            return $this->resposta(
                'Jogador cadastrado com sucesso.',
                201,
                ['jogador' => $this->jogadores->buscarJogador((int) $id)]
            );

        } catch (\Throwable $e) {
            $this->log->registrar('error', 'Erro ao cadastrar jogador.', [
                'erro' => $e->getMessage()
            ]);

            $this->removerImagem($imagem);

            return $this->resposta($e->getMessage(), 400);
        }
    }

    public function atualizar($id = null)
    {
        $jogador = $this->jogadores->buscarJogador((int) $id);

        if (!$jogador) {
            return $this->resposta('Jogador não encontrado.', 404);
        }

        $validacao = $this->jogadores->validarDados([
            'nome' => $this->request->getPost('nome'),
            'idade' => $this->request->getPost('idade'),
            'posicao' => $this->request->getPost('posicao'),
            'numero_camisa' => $this->request->getPost('numero_camisa')
        ]);

        if (!$validacao['valido']) {
            return $this->resposta($validacao['mensagem'], 400);
        }

        $novaImagem = null;

        try {
            $dados = $validacao['dados'];

            $arquivo = $this->request->getFile('imagem');

            if ($arquivo && $arquivo->isValid()) {
                $novaImagem = $this->salvarImagem($arquivo);
                $dados['imagem'] = $novaImagem;
            }

            $resultado = $this->jogadores->atualizarJogador(
                (int) $id,
                $dados
            );

            if (!$resultado) {
                $this->removerImagem($novaImagem);

                return $this->resposta(
                    'Não foi possível atualizar o jogador.',
                    500
                );
            }

            if ($novaImagem) {
                $this->removerImagem($jogador['imagem']);
            }

            $this->log->registrar('info', 'Jogador atualizado com sucesso.', [
                'jogador_id' => $id
            ]);

            return $this->resposta(
                'Jogador atualizado com sucesso.',
                200,
                ['jogador' => $this->jogadores->buscarJogador((int) $id)]
            );

        } catch (\Throwable $e) {
            $this->log->registrar('error', 'Erro ao atualizar jogador.', [
                'jogador_id' => $id,
                'erro' => $e->getMessage()
            ]);

            $this->removerImagem($novaImagem);

            return $this->resposta($e->getMessage(), 400);
        }
    }

    public function excluir($id = null)
    {
        $jogador = $this->jogadores->buscarJogador((int) $id);

        if (!$jogador) {
            return $this->resposta('Jogador não encontrado.', 404);
        }

        $resultado = $this->jogadores->excluirJogador((int) $id);

        if (!$resultado) {
            return $this->resposta(
                'Não foi possível excluir o jogador.',
                500
            );
        }

        $this->removerImagem($jogador['imagem']);

        $this->log->registrar('info', 'Jogador excluído com sucesso.', [
            'jogador_id' => $id
        ]);

        return $this->resposta('Jogador excluído com sucesso.');
    }
}