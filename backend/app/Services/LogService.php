<?php

namespace App\Services;

use App\Models\LogModel;
use Throwable;

class LogService
{
    private LogModel $logs;

    public function __construct()
    {
        $this->logs = new LogModel();
    }

    public function registrar(
        string $nivel,
        string $acao,
        array $contexto = []
    ): void {
        $niveisPermitidos = [
            'emergency',
            'alert',
            'critical',
            'error',
            'warning',
            'notice',
            'info',
            'debug'
        ];

        $nivel = strtolower($nivel);

        if (!in_array($nivel, $niveisPermitidos, true)) {
            throw new \InvalidArgumentException('Nível de log inválido.');
        }

        $contexto = $this->removerDadosSensíveis($contexto);

        $dadosContexto = json_encode(
            $contexto,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $request = service('request');

        $usuarioId = $contexto['usuario_id'] ?? null;
        $usuarioNome = $contexto['usuario_nome'] ?? null;

        $dados = [
            'usuario_id' => $usuarioId,
            'usuario_nome' => $usuarioNome,
            'nivel' => $nivel,
            'acao' => $acao,
            'contexto' => $dadosContexto !== false ? $dadosContexto : null,
            'ip' => $request->getIPAddress(),
            'metodo' => $request->getMethod(),
            'rota' => $request->getUri()->getPath(),
            'user_agent' => $request->getUserAgent()->getAgentString(),
            'criado_em' => date('Y-m-d H:i:s')
        ];

        try {
            $resultado = $this->logs->insert($dados);

            if ($resultado === false) {
                throw new \RuntimeException('Não foi possível salvar o log no banco.');
            }
        } catch (Throwable $erro) {
            log_message(
                'error',
                '[LOG SERVICE] Falha ao salvar log no banco: ' . $erro->getMessage()
            );
        }

        $mensagem = '[JOGADORES] ' . $acao;

        if ($dadosContexto !== false) {
            $mensagem .= ' | Contexto: ' . $dadosContexto;
        }

        log_message($nivel, $mensagem);
    }

    private function removerDadosSensíveis(array $contexto): array
    {
        $camposProibidos = [
            'senha',
            'password',
            'token',
            'authorization',
            'authorization_header',
            'access_token',
            'refresh_token'
        ];

        foreach ($contexto as $chave => $valor) {
            if (in_array(strtolower((string) $chave), $camposProibidos, true)) {
                $contexto[$chave] = '[REMOVIDO]';
            } elseif (is_array($valor)) {
                $contexto[$chave] = $this->removerDadosSensíveis($valor);
            }
        }

        return $contexto;
    }
}
