<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\LogModel;

class LogsController extends BaseController
{
    private LogModel $logs;

    public function initController(
        \CodeIgniter\HTTP\RequestInterface $request,
        \CodeIgniter\HTTP\ResponseInterface $response,
        \Psr\Log\LoggerInterface $logger
    ) {
        parent::initController($request, $response, $logger);

        $this->logs = new LogModel();
    }

    public function index()
    {
        $pagina = max(1, (int) $this->request->getGet('pagina'));
        $porPagina = 15;

        $nivel = strtolower(trim($this->request->getGet('nivel') ?? ''));
        $pesquisa = trim($this->request->getGet('pesquisa') ?? '');
        $dataInicio = $this->request->getGet('data_inicio');
        $dataFim = $this->request->getGet('data_fim');

        $consulta = $this->logs;

        if ($nivel !== '' && $nivel !== 'todos') {
            $consulta = $consulta->where('nivel', $nivel);
        }

        if ($pesquisa !== '') {
            $consulta = $consulta->groupStart()
                ->like('acao', $pesquisa)
                ->orLike('usuario_nome', $pesquisa)
                ->orLike('usuario_id', $pesquisa)
                ->groupEnd();
        }

        if ($dataInicio) {
            $consulta = $consulta->where(
                'criado_em >=',
                $dataInicio . ' 00:00:00'
            );
        }

        if ($dataFim) {
            $consulta = $consulta->where(
                'criado_em <=',
                $dataFim . ' 23:59:59'
            );
        }

        $total = $consulta->countAllResults(false);

        $registros = $consulta
            ->orderBy('id', 'DESC')
            ->findAll($porPagina, ($pagina - 1) * $porPagina);

        return $this->response->setJSON([
            'status' => true,
            'logs' => $registros,
            'paginacao' => [
                'pagina_atual' => $pagina,
                'por_pagina' => $porPagina,
                'total_registros' => $total,
                'total_paginas' => (int) ceil($total / $porPagina)
            ]
        ]);
    }
}
