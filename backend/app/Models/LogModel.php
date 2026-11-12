<?php

namespace App\Models;

use CodeIgniter\Model;

class LogModel extends Model
{
    protected $table = 'logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    protected $useAutoIncrement = true;
    protected $useTimestamps = false;

    protected $allowedFields = [
        'usuario_id',
        'usuario_nome',
        'nivel',
        'acao',
        'contexto',
        'ip',
        'metodo',
        'rota',
        'user_agent',
        'criado_em'
    ];
}
