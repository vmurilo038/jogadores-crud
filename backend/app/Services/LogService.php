<?php

namespace App\Services;

class LogService
{
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

        $mensagem = '[JOGADORES] ' . $acao;

        if (!empty($contexto)) {
            $dados = json_encode(
                $contexto,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            if ($dados !== false) {
                $mensagem .= ' | Contexto: ' . $dados;
            }
        }

        log_message($nivel, $mensagem);
    }
}